<?php
include 'config.php';
$page_title = "LibraFlow - Book Catalog";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Handle book borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = intval($_POST['book_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if book is available
    $stmt = $conn->prepare("SELECT status FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if ($book && $book['status'] === 'Available') {
        // Create loan record
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $conn->prepare("INSERT INTO loans (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $book_id, $borrow_date, $due_date);
        
        if ($stmt->execute()) {
            // Update book status
            $stmt = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            
            $success = "Book borrowed successfully! Due date: " . $due_date;
        }
        $stmt->close();
    } else {
        $error = "Book is not available for borrowing";
    }
}

// Handle file download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_file'])) {
    $book_id = intval($_POST['book_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user has permission to download (has borrowed the book or book is available)
    $stmt = $conn->prepare("
        SELECT b.*, l.user_id as borrowed_by 
        FROM books b 
        LEFT JOIN loans l ON b.id = l.book_id AND l.user_id = ? 
        WHERE b.id = ?
    ");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if ($book && ($book['borrowed_by'] == $user_id || $book['status'] === 'Available')) {
        // Check if book has downloadable files
        if (!empty($book['file_path']) && file_exists($book['file_path'])) {
            // Log download
            try {
                $log_stmt = $conn->prepare("INSERT INTO file_downloads (book_id, user_id, ip_address, action_type) VALUES (?, ?, ?, 'download')");
                $log_stmt->bind_param("iis", $book_id, $user_id, $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
                $log_stmt->close();
            } catch (Exception $e) {
                // Continue if logging fails
            }
            
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $book['file_name'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($book['file_path']));
            readfile($book['file_path']);
            exit;
        } else {
            $error = "No downloadable files available for this book";
        }
    } else {
        $error = "You need to borrow this book first to download files";
    }
}

// Handle multiple file downloads
if (isset($_GET['download_all']) && $_GET['download_all'] === 'true') {
    $user_id = $_SESSION['user_id'];
    
    // Get all downloadable books for user
    $stmt = $conn->prepare("
        SELECT b.* 
        FROM books b 
        LEFT JOIN loans l ON b.id = l.book_id AND l.user_id = ? 
        WHERE b.file_path IS NOT NULL AND (l.user_id = ? OR b.status = 'Available')
        AND b.file_path != ''
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $downloadable_books = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (!empty($downloadable_books)) {
        // Create zip file
        $zip = new ZipArchive();
        $zip_filename = 'libraflow_books_' . date('Y-m-d') . '.zip';
        $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
        
        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            foreach ($downloadable_books as $book) {
                if (file_exists($book['file_path'])) {
                    $zip->addFile($book['file_path'], $book['file_name']);
                    
                    // Log each download
                    $log_stmt = $conn->prepare("INSERT INTO file_downloads (book_id, user_id, ip_address, action_type) VALUES (?, ?, ?, 'bulk_download')");
                    $log_stmt->bind_param("iis", $book['id'], $user_id, $_SERVER['REMOTE_ADDR']);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            }
            $zip->close();
            
            // Download zip file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            
            // Clean up
            unlink($zip_path);
            exit;
        } else {
            $error = "Failed to create download package";
        }
    } else {
        $error = "No downloadable books available";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$file_type = $_GET['file_type'] ?? '';
$sort = $_GET['sort'] ?? 'title';
$view = $_GET['view'] ?? 'grid';
$show_downloadable = $_GET['downloadable'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if (!empty($category)) {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($file_type)) {
    $where .= " AND file_type = ?";
    $params[] = $file_type;
    $types .= "s";
}

if (!empty($show_downloadable)) {
    $where .= " AND file_name IS NOT NULL AND file_path != ''";
}

// Get categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get file types for filter
$file_types_result = $conn->query("SELECT DISTINCT file_type FROM books WHERE file_type IS NOT NULL ORDER BY file_type");
$file_types = $file_types_result->fetch_all(MYSQLI_ASSOC);

// Sort options
$sort_options = [
    'title' => 'Title A-Z',
    'title_desc' => 'Title Z-A',
    'author' => 'Author A-Z',
    'author_desc' => 'Author Z-A',
    'newest' => 'Newest First',
    'oldest' => 'Oldest First',
    'downloads' => 'Most Downloaded'
];

// Get books from database
try {
    $order_by = "ORDER BY ";
    switch ($sort) {
        case 'title_desc':
            $order_by .= "title DESC";
            break;
        case 'author':
            $order_by .= "author ASC";
            break;
        case 'author_desc':
            $order_by .= "author DESC";
            break;
        case 'newest':
            $order_by .= "id DESC";
            break;
        case 'oldest':
            $order_by .= "id ASC";
            break;
        case 'downloads':
            $order_by .= "(SELECT COUNT(*) FROM file_downloads WHERE book_id = books.id) DESC";
            break;
        default:
            $order_by .= "title ASC";
    }
    
    $sql = "SELECT *, 
            (SELECT COUNT(*) FROM loans WHERE book_id = books.id AND user_id = ?) as is_borrowed_by_user,
            (SELECT COUNT(*) FROM file_downloads WHERE book_id = books.id) as download_count
            FROM books $where $order_by";
    
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($params)) {
        $stmt->bind_param("i" . $types, $user_id, ...$params);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $books_result = $stmt->get_result();
    $books = $books_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
} catch (Exception $e) {
    // Fallback if columns don't exist
    $sql = "SELECT * FROM books $where $order_by";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $books_result = $stmt->get_result();
    $books = $books_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Add default values for missing fields
    foreach ($books as &$book) {
        $book['is_borrowed_by_user'] = 0;
        $book['download_count'] = 0;
    }
}

// Get download stats for user
$download_stats_stmt = $conn->prepare("
    SELECT COUNT(*) as total_downloads, 
           COUNT(DISTINCT book_id) as unique_books 
    FROM file_downloads 
    WHERE user_id = ?
");
$download_stats_stmt->bind_param("i", $_SESSION['user_id']);
$download_stats_stmt->execute();
$download_stats = $download_stats_stmt->get_result()->fetch_assoc();
$download_stats_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Clean Catalog Design */
        .catalog-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Simple Header */
        .catalog-header {
            background: white;
            padding: 2rem 0 1rem 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--slate-200);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--slate-800);
            margin: 0;
        }

        .book-count {
            font-size: 1rem;
            color: var(--slate-600);
            font-weight: 500;
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--slate-200);
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-group {
            display: flex;
            flex-direction: column;
        }

        .search-label {
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--blue-500);
        }

        /* View Controls */
        .view-controls-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--slate-200);
        }

        .view-options {
            display: flex;
            gap: 0.5rem;
        }

        .view-btn {
            padding: 0.5rem;
            border: 1px solid var(--slate-300);
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-btn.active {
            background: var(--blue-600);
            color: white;
            border-color: var(--blue-600);
        }

        /* Books Grid - Clean Design */
        .books-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .books-grid.grid-view {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .books-grid.list-view {
            grid-template-columns: 1fr;
        }

        .books-grid.compact-view {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Book Item - Clean Card Design */
        .book-item {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--slate-200);
        }

        .book-media {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--slate-100);
        }

        .book-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-badges {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            right: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .status-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: var(--emerald-500);
            color: white;
        }

        .status-borrowed {
            background: var(--rose-500);
            color: white;
        }

        .status-hold {
            background: var(--amber-500);
            color: white;
        }

        .file-badge {
            background: white;
            color: var(--purple-600);
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .book-content {
            padding: 1.5rem;
        }

        .book-category {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--blue-600);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }

        .book-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--slate-900);
            line-height: 1.3;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 1rem;
            color: var(--slate-600);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .book-description {
            font-size: 0.875rem;
            color: var(--slate-500);
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--slate-100);
        }

        .book-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--amber-400);
        }

        .rating-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--slate-600);
        }

        .book-isbn {
            font-size: 0.75rem;
            color: var(--slate-400);
            font-family: 'Courier New', monospace;
        }

        .book-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-primary {
            flex: 1;
            background: var(--blue-600);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary:hover {
            background: var(--blue-700);
        }

        .btn-success {
            background: var(--emerald-600);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-success:hover {
            background: var(--emerald-700);
        }

        .btn-outline {
            background: transparent;
            color: var(--blue-600);
            border: 1px solid var(--blue-600);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-outline:hover {
            background: var(--blue-600);
            color: white;
        }

        /* List View Specific */
        .books-grid.list-view .book-item {
            display: flex;
            height: 180px;
        }

        .books-grid.list-view .book-media {
            width: 120px;
            height: 100%;
            flex-shrink: 0;
        }

        .books-grid.list-view .book-content {
            flex: 1;
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }

        .books-grid.list-view .book-actions {
            margin-top: auto;
        }

        /* Compact View Specific */
        .books-grid.compact-view .book-item {
            text-align: center;
            padding: 1rem;
        }

        .books-grid.compact-view .book-media {
            height: 140px;
            margin-bottom: 1rem;
        }

        .books-grid.compact-view .book-title {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .books-grid.compact-view .book-author {
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .books-grid.compact-view .book-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .books-grid.compact-view .btn-primary,
        .books-grid.compact-view .btn-success,
        .books-grid.compact-view .btn-outline {
            padding: 0.5rem;
            font-size: 0.75rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--slate-500);
            grid-column: 1 / -1;
            background: white;
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            font-size: 1rem;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        /* Filter Tags */
        .filter-tags {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .filter-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--blue-50);
            color: var(--blue-700);
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--blue-200);
        }

        .filter-tag a {
            background: none;
            border: none;
            color: var(--blue-500);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            text-decoration: none;
        }

        .filter-tag a:hover {
            background: var(--blue-100);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .books-grid.grid-view {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .catalog-header {
                padding: 1.5rem 0 1rem 0;
            }

            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .view-controls-panel {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .books-grid.grid-view {
                grid-template-columns: 1fr;
            }

            .books-grid.list-view .book-item {
                flex-direction: column;
                height: auto;
            }

            .books-grid.list-view .book-media {
                width: 100%;
                height: 200px;
            }

            .search-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .books-grid.compact-view {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .book-actions {
                flex-direction: column;
            }

            .book-content {
                padding: 1rem;
            }

            .search-section {
                padding: 1rem;
            }
        }

        /* Simple Download Button */
        .download-all-btn {
            background: var(--emerald-600);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .download-all-btn:hover {
            background: var(--emerald-700);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="catalog-container">
                <!-- Simple Header -->
                <div class="catalog-header">
                    <div class="header-content">
                        <div>
                            <h1 class="page-title">Book Catalog</h1>
                            <p class="book-count"><?php echo count($books); ?> books available</p>
                        </div>
                        <a href="?download_all=true" class="download-all-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download All
                        </a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Search Section -->
                <div class="search-section">
                    <form method="GET" class="search-grid">
                        <div class="search-group">
                            <label class="search-label">Search</label>
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Title, author, ISBN..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="search-group">
                            <label class="search-label">Category</label>
                            <select name="category" class="search-input">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label class="search-label">File Type</label>
                            <select name="file_type" class="search-input">
                                <option value="">All File Types</option>
                                <?php foreach ($file_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['file_type']); ?>" 
                                        <?php echo $file_type === $type['file_type'] ? 'selected' : ''; ?>>
                                        <?php echo strtoupper(htmlspecialchars($type['file_type'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label class="search-label">Sort By</label>
                            <select name="sort" class="search-input">
                                <?php foreach ($sort_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label class="search-label">View</label>
                            <select name="view" class="search-input">
                                <option value="grid" <?php echo $view === 'grid' ? 'selected' : ''; ?>>Grid</option>
                                <option value="list" <?php echo $view === 'list' ? 'selected' : ''; ?>>List</option>
                                <option value="compact" <?php echo $view === 'compact' ? 'selected' : ''; ?>>Compact</option>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label class="search-label">&nbsp;</label>
                            <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">
                                Apply
                            </button>
                        </div>
                    </form>

                    <div class="filter-tags">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500; color: var(--slate-700);">
                            <input type="checkbox" name="downloadable" value="1" 
                                   <?php echo $show_downloadable ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <span>Downloadable Only</span>
                        </label>
                        
                        <?php if (!empty($search) || !empty($category) || !empty($file_type)): ?>
                            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                <span style="font-size: 0.875rem; color: var(--slate-600);">Filters:</span>
                                <?php if (!empty($search)): ?>
                                    <span class="filter-tag">
                                        Search: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>">×</a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($category)): ?>
                                    <span class="filter-tag">
                                        <?php echo htmlspecialchars($category); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>">×</a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($file_type)): ?>
                                    <span class="filter-tag">
                                        <?php echo htmlspecialchars($file_type); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['file_type' => ''])); ?>">×</a>
                                    </span>
                                <?php endif; ?>
                                <a href="?" class="btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    Clear All
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- View Controls -->
                <div class="view-controls-panel">
                    <div class="view-options">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'grid'])); ?>" 
                           class="view-btn <?php echo $view === 'grid' ? 'active' : ''; ?>" title="Grid View">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'list'])); ?>" 
                           class="view-btn <?php echo $view === 'list' ? 'active' : ''; ?>" title="List View">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'compact'])); ?>" 
                           class="view-btn <?php echo $view === 'compact' ? 'active' : ''; ?>" title="Compact View">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="2" y="2" width="20" height="20" rx="2"></rect>
                                <line x1="8" y1="2" x2="8" y2="22"></line>
                                <line x1="16" y1="2" x2="16" y2="22"></line>
                            </svg>
                        </a>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 0.875rem; color: var(--slate-600);">
                            <?php echo count($books); ?> books
                        </span>
                        <span style="font-size: 0.875rem; color: var(--slate-500);">
                            Sorted by <?php echo strtolower($sort_options[$sort]); ?>
                        </span>
                    </div>
                </div>

                <!-- Books Grid -->
                <div class="books-grid <?php echo $view; ?>-view">
                    <?php if (empty($books)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📚</div>
                            <h3 class="empty-title">No Books Found</h3>
                            <p class="empty-description">
                                <?php if (!empty($search) || !empty($category) || !empty($file_type)): ?>
                                    No books match your current filters. Try adjusting your search criteria.
                                <?php else: ?>
                                    The library catalog is currently empty.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search) || !empty($category) || !empty($file_type)): ?>
                                <a href="?" class="btn-primary" style="text-decoration: none;">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($books as $book): 
                            $status_class = [
                                'Available' => 'status-available',
                                'Borrowed' => 'status-borrowed',
                                'Hold' => 'status-hold'
                            ][$book['status'] ?? 'Available'];
                            
                            $has_files = !empty($book['file_path']) && file_exists($book['file_path']);
                            $can_download = ($book['is_borrowed_by_user'] ?? 0) > 0 || ($book['status'] ?? 'Available') === 'Available';
                            $cover_url = $book['cover_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4=';
                        ?>
                        <div class="book-item" data-book-id="<?php echo $book['id']; ?>">
                            <div class="book-media">
                                <img src="<?php echo htmlspecialchars($cover_url); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     class="book-cover"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4='">
                                
                                <div class="book-badges">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $book['status'] ?? 'Available'; ?>
                                    </span>
                                    <?php if ($has_files): ?>
                                        <span class="file-badge">
                                            <?php echo strtoupper($book['file_type'] ?? 'FILE'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="book-content">
                                <span class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span>
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                
                                <?php if ($view !== 'compact' && !empty($book['description'])): ?>
                                    <p class="book-description"><?php echo htmlspecialchars($book['description']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($view !== 'compact'): ?>
                                <div class="book-meta">
                                    <div class="book-rating">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                        <span class="rating-text">4.5</span>
                                    </div>
                                    <?php if (!empty($book['isbn'])): ?>
                                        <div class="book-isbn">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="book-actions">
                                    <?php if (($book['status'] ?? 'Available') === 'Available'): ?>
                                        <form method="POST" class="borrow-form" style="margin: 0; flex: 1;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="borrow_book" class="btn-primary">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                                </svg>
                                                Borrow
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($has_files && $can_download): ?>
                                        <form method="POST" class="download-form" style="margin: 0;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="download_file" class="btn-success">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                                Download
                                            </button>
                                        </form>
                                    <?php elseif ($has_files): ?>
                                        <button class="btn-outline" onclick="showBookDetails(<?php echo $book['id']; ?>)">
                                            Details
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-outline" onclick="showBookDetails(<?php echo $book['id']; ?>)">
                                            View
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Simple download functionality
        function downloadBookFile(bookId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="book_id" value="${bookId}">
                             <input type="hidden" name="download_file" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }

        // Simple book details
        function showBookDetails(bookId) {
            console.log('Show details for book:', bookId);
        }

        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const filters = ['sort', 'view', 'category', 'file_type'];
            filters.forEach(filter => {
                const element = document.querySelector(`[name="${filter}"]`);
                if (element) {
                    element.addEventListener('change', function() {
                        this.form.submit();
                    });
                }
            });
        });
    </script>
</body>
</html>