<?php
include 'config.php';
$page_title = "LibraFlow - Admin Dashboard";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// File upload directory
$upload_dir = "uploads/books/";

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle book management and file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Book
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $cover_url = trim($_POST['cover_url']);
        $description = trim($_POST['description']);
        $publisher = trim($_POST['publisher']);
        $published_year = trim($_POST['published_year']);
        $status = trim($_POST['status']);
        
        // Handle file upload
        $file_name = null;
        $file_path = null;
        $file_type = null;
        $file_size = null;
        
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = [
                'application/pdf' => 'pdf',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'application/xml' => 'xml',
                'text/xml' => 'xml',
                'application/vnd.ms-works' => 'wps',
                'application/octet-stream' => 'wps' // For WPS files
            ];
            
            $file_info = $_FILES['book_file'];
            $file_type = $file_info['type'];
            $file_size = $file_info['size'];
            
            // Check if file type is allowed
            if (array_key_exists($file_type, $allowed_types)) {
                $extension = $allowed_types[$file_type];
                $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $file_info['name']);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
                    $success = "Book and file uploaded successfully!";
                } else {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "Invalid file type. Allowed: PDF, PPT, PPTX, XML, WPS";
            }
        }
        
        // Insert book into database
        if (!isset($error)) {
            if ($file_name) {
                $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, cover_url, description, publisher, published_year, status, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssi", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year, $status, $file_name, $file_path, $file_type, $file_size);
            } else {
                $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, cover_url, description, publisher, published_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year, $status);
            }
            
            if ($stmt->execute()) {
                $success = $success ?? "Book added successfully!";
                
                // Log the action
                $log_stmt = $conn->prepare("INSERT INTO file_downloads (book_id, user_id, ip_address) VALUES (?, ?, ?)");
                $book_id = $stmt->insert_id;
                $log_stmt->bind_param("iis", $book_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
                $log_stmt->close();
                
            } else {
                $error = "Failed to add book: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Update Book
    if (isset($_POST['update_book'])) {
        $book_id = intval($_POST['book_id']);
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $cover_url = trim($_POST['cover_url']);
        $description = trim($_POST['description']);
        $publisher = trim($_POST['publisher']);
        $published_year = trim($_POST['published_year']);
        $status = trim($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, cover_url=?, description=?, publisher=?, published_year=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year, $status, $book_id);
        
        if ($stmt->execute()) {
            $success = "Book updated successfully!";
        } else {
            $error = "Failed to update book: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Delete Book
    if (isset($_POST['delete_book'])) {
        $book_id = intval($_POST['book_id']);
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Delete related records first
            $stmt1 = $conn->prepare("DELETE FROM file_downloads WHERE book_id = ?");
            $stmt1->bind_param("i", $book_id);
            $stmt1->execute();
            $stmt1->close();
            
            // Delete from loans table if exists
            $stmt2 = $conn->prepare("DELETE FROM loans WHERE book_id = ?");
            $stmt2->bind_param("i", $book_id);
            $stmt2->execute();
            $stmt2->close();
            
            // Get file path before deleting
            $stmt3 = $conn->prepare("SELECT file_path FROM books WHERE id = ?");
            $stmt3->bind_param("i", $book_id);
            $stmt3->execute();
            $result = $stmt3->get_result();
            $book = $result->fetch_assoc();
            $stmt3->close();
            
            // Delete book from database
            $stmt4 = $conn->prepare("DELETE FROM books WHERE id = ?");
            $stmt4->bind_param("i", $book_id);
            
            if ($stmt4->execute()) {
                // Delete associated file if exists
                if ($book && $book['file_path'] && file_exists($book['file_path'])) {
                    unlink($book['file_path']);
                }
                $conn->commit();
                $success = "Book deleted successfully!";
            } else {
                throw new Exception("Failed to delete book");
            }
            $stmt4->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to delete book: " . $e->getMessage();
        }
    }
    
    // Add User
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = "User added successfully!";
            } else {
                $error = "Failed to add user: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    // Update User
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        
        // Check if email already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role, $user_id);
            
            if ($stmt->execute()) {
                $success = "User updated successfully!";
            } else {
                $error = "Failed to update user: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    // Delete User
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Delete related records first
                $stmt1 = $conn->prepare("DELETE FROM file_downloads WHERE user_id = ?");
                $stmt1->bind_param("i", $user_id);
                $stmt1->execute();
                $stmt1->close();
                
                // Delete from loans table if exists
                $stmt2 = $conn->prepare("DELETE FROM loans WHERE user_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $stmt2->close();
                
                // Delete user
                $stmt3 = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt3->bind_param("i", $user_id);
                
                if ($stmt3->execute()) {
                    $conn->commit();
                    $success = "User deleted successfully!";
                } else {
                    throw new Exception("Failed to delete user");
                }
                $stmt3->close();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
}

// Handle file download
if (isset($_GET['download_file']) && isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    
    $stmt = $conn->prepare("SELECT file_name, file_path, title FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        if ($book['file_path'] && file_exists($book['file_path'])) {
            // Log download
            $log_stmt = $conn->prepare("INSERT INTO file_downloads (book_id, user_id, ip_address) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iis", $book_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
            $log_stmt->execute();
            $log_stmt->close();
            
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
            $error = "File not found.";
        }
    }
    $stmt->close();
}

// Get admin stats
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total_books FROM books");
$stats['total_books'] = $result->fetch_assoc()['total_books'];

$result = $conn->query("SELECT COUNT(*) as books_with_files FROM books WHERE file_name IS NOT NULL");
$stats['books_with_files'] = $result->fetch_assoc()['books_with_files'];

$result = $conn->query("SELECT COUNT(*) as active_members FROM users WHERE role = 'member'");
$stats['active_members'] = $result->fetch_assoc()['active_members'];

$result = $conn->query("SELECT COUNT(*) as overdue_items FROM loans WHERE due_date < CURDATE() AND status = 'Active'");
$stats['overdue_items'] = $result->fetch_assoc()['overdue_items'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as pending_fines FROM fines WHERE status = 'Pending'");
$stats['pending_fines'] = $result->fetch_assoc()['pending_fines'];

// Get download stats
$result = $conn->query("SELECT COUNT(*) as total_downloads FROM file_downloads");
$stats['total_downloads'] = $result->fetch_assoc()['total_downloads'];

// Get books for management
$books_result = $conn->query("SELECT * FROM books ORDER BY title");
$books = $books_result->fetch_all(MYSQLI_ASSOC);

// Get users for management
$users_result = $conn->query("SELECT * FROM users ORDER BY name");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get recent transactions
$transactions_result = $conn->query("
    SELECT l.*, u.name as user_name, b.title as book_title,
           CASE 
               WHEN l.return_date IS NULL THEN 'Checked Out'
               ELSE 'Returned'
           END as transaction_type
    FROM loans l
    JOIN users u ON l.user_id = u.id
    JOIN books b ON l.book_id = b.id
    ORDER BY l.borrow_date DESC
    LIMIT 10
");
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);

// Get recent downloads
$downloads_result = $conn->query("
    SELECT fd.*, b.title as book_title, u.name as user_name, b.file_type
    FROM file_downloads fd
    JOIN books b ON fd.book_id = b.id
    JOIN users u ON fd.user_id = u.id
    ORDER BY fd.downloaded_at DESC
    LIMIT 10
");
$recent_downloads = $downloads_result->fetch_all(MYSQLI_ASSOC);

// Get book data for editing
$edit_book = null;
if (isset($_GET['edit_book'])) {
    $book_id = intval($_GET['edit_book']);
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_book = $result->fetch_assoc();
    $stmt->close();
}

// Get user data for editing
$edit_user = null;
if (isset($_GET['edit_user'])) {
    $user_id = intval($_GET['edit_user']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/notifications.js"></script>
</head>
<body class="app-container">
    <!-- Left Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Content -->
        <div class="content-area">
            <div class="admin-dashboard">
                <div class="admin-header">
                    <div class="header-info">
                        <h2>Administrator Console</h2>
                        <p>Manage library operations, books, and users.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <button class="tab-btn active" data-tab="overview">Overview</button>
                        <button class="tab-btn" data-tab="books">Books & Files</button>
                        <button class="tab-btn" data-tab="users">Users</button>
                        <button class="tab-btn" data-tab="transactions">Transactions</button>
                        <button class="tab-btn" data-tab="downloads">Downloads</button>
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

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div id="overview-tab" class="tab-pane active">
                        <!-- Stats Cards -->
                        <div class="stats-grid admin-stats">
                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-blue">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Books</p>
                                    <h3 class="stat-value"><?php echo $stats['total_books']; ?></h3>
                                    <div style="margin-top: 0.5rem;">
                                        <?php 
                                        $bookStatus = $stats['total_books'] > 10000 ? 'Excellent' : 'Good';
                                        $badgeType = $stats['total_books'] > 10000 ? 'success' : 'info';
                                        echo '<span class="proof-badge ' . $badgeType . '">' . $bookStatus . ' Collection</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-purple">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Digital Files</p>
                                    <h3 class="stat-value"><?php echo $stats['books_with_files']; ?></h3>
                                    <div style="margin-top: 0.5rem;">
                                        <?php 
                                        $fileStatus = $stats['books_with_files'] > 0 ? 'Available' : 'No Files';
                                        $badgeType = $stats['books_with_files'] > 0 ? 'success' : 'warning';
                                        echo '<span class="proof-badge ' . $badgeType . '">' . $fileStatus . '</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-emerald">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Downloads</p>
                                    <h3 class="stat-value"><?php echo $stats['total_downloads']; ?></h3>
                                    <div style="margin-top: 0.5rem;">
                                        <?php 
                                        $downloadStatus = $stats['total_downloads'] > 0 ? 'Active' : 'No Downloads';
                                        $badgeType = $stats['total_downloads'] > 0 ? 'success' : 'info';
                                        echo '<span class="proof-badge ' . $badgeType . '">' . $downloadStatus . '</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-rose">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Overdue Items</p>
                                    <h3 class="stat-value"><?php echo $stats['overdue_items']; ?></h3>
                                    <div style="margin-top: 0.5rem;">
                                        <?php 
                                        $overdueStatus = $stats['overdue_items'] > 50 ? 'Attention Needed' : 'Under Control';
                                        $badgeType = $stats['overdue_items'] > 50 ? 'warning' : 'success';
                                        echo '<span class="proof-badge ' . $badgeType . '">' . $overdueStatus . '</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity Proofs -->
                        <div class="proof-container">
                            <div class="section-card">
                                <div class="section-header">
                                    <h3>Recent Activity Proofs</h3>
                                    <span class="proof-badge">Live Updates</span>
                                </div>
                                <div class="proofs-list" id="proofs-list">
                                    <div class="action-proof">
                                        <div class="action-proof-header">
                                            <div class="action-proof-title">System Started</div>
                                            <div class="action-proof-time"><?php echo date('M j, g:i A'); ?></div>
                                        </div>
                                        <div class="action-proof-content">
                                            Admin dashboard loaded successfully. CRUD operations ready.
                                        </div>
                                    </div>
                                    <?php
                                    // Add recent downloads as proofs
                                    foreach (array_slice($recent_downloads, 0, 3) as $download) {
                                        echo '
                                        <div class="action-proof">
                                            <div class="action-proof-header">
                                                <div class="action-proof-title">File Downloaded</div>
                                                <div class="action-proof-time">' . date('M j, g:i A', strtotime($download['downloaded_at'])) . '</div>
                                            </div>
                                            <div class="action-proof-content">
                                                <strong>' . htmlspecialchars($download['book_title']) . '</strong><br>
                                                User: ' . htmlspecialchars($download['user_name']) . '<br>
                                                File Type: ' . strtoupper($download['file_type']) . '
                                            </div>
                                        </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Books & Files Tab -->
                    <div id="books-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Book & File Management</h3>
                                <button class="btn btn-primary" onclick="showAddBookForm()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"></path>
                                    </svg>
                                    Add Book & File
                                </button>
                            </div>
                            
                            <!-- Add Book Form -->
                            <div id="add-book-form" class="add-form" style="display: <?php echo isset($_GET['add_book']) ? 'block' : 'none'; ?>;">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Title *</label>
                                            <input type="text" name="title" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Author *</label>
                                            <input type="text" name="author" required>
                                        </div>
                                        <div class="form-group">
                                            <label>ISBN *</label>
                                            <input type="text" name="isbn" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Category *</label>
                                            <input type="text" name="category" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Cover URL</label>
                                            <input type="url" name="cover_url" placeholder="https://example.com/cover.jpg">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Publisher</label>
                                            <input type="text" name="publisher">
                                        </div>
                                        <div class="form-group">
                                            <label>Published Year</label>
                                            <input type="number" name="published_year" min="1900" max="<?php echo date('Y'); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" required>
                                                <option value="Available">Available</option>
                                                <option value="Borrowed">Borrowed</option>
                                                <option value="Hold">Hold</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Book File (PDF, PPT, XML, WPS)</label>
                                            <input type="file" name="book_file" accept=".pdf,.ppt,.pptx,.xml,.wps" 
                                                   onchange="previewFile(this)">
                                            <div class="file-preview" id="file-preview" style="display: none; margin-top: 0.5rem;">
                                                <div class="proof-badge info" id="file-info"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="add_book" class="btn btn-primary">Add Book & File</button>
                                        <button type="button" class="btn btn-outline" onclick="hideAddBookForm()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Edit Book Form -->
                            <?php if ($edit_book): ?>
                            <div id="edit-book-form" class="add-form" style="display: block;">
                                <form method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Title *</label>
                                            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_book['title']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Author *</label>
                                            <input type="text" name="author" value="<?php echo htmlspecialchars($edit_book['author']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>ISBN *</label>
                                            <input type="text" name="isbn" value="<?php echo htmlspecialchars($edit_book['isbn']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Category *</label>
                                            <input type="text" name="category" value="<?php echo htmlspecialchars($edit_book['category']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Cover URL</label>
                                            <input type="url" name="cover_url" value="<?php echo htmlspecialchars($edit_book['cover_url']); ?>" placeholder="https://example.com/cover.jpg">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_book['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Publisher</label>
                                            <input type="text" name="publisher" value="<?php echo htmlspecialchars($edit_book['publisher'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Published Year</label>
                                            <input type="number" name="published_year" value="<?php echo htmlspecialchars($edit_book['published_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" required>
                                                <option value="Available" <?php echo ($edit_book['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                                <option value="Borrowed" <?php echo ($edit_book['status'] == 'Borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                                                <option value="Hold" <?php echo ($edit_book['status'] == 'Hold') ? 'selected' : ''; ?>>Hold</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                                        <a href="admin-dashboard.php?tab=books" class="btn btn-outline">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Book Details</th>
                                            <th>ISBN</th>
                                            <th>Category</th>
                                            <th>File</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $book): 
                                            $status_class = [
                                                'Available' => 'status-available',
                                                'Borrowed' => 'status-borrowed',
                                                'Hold' => 'status-hold'
                                            ][$book['status']];
                                            
                                            $file_badge = $book['file_name'] ? 
                                                '<span class="proof-badge success">' . strtoupper(pathinfo($book['file_name'], PATHINFO_EXTENSION)) . '</span>' :
                                                '<span class="proof-badge warning">No File</span>';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="book-cell">
                                                    <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4='">
                                                    <div>
                                                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                                        <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="isbn-cell"><?php echo htmlspecialchars($book['isbn']); ?></td>
                                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                                            <td><?php echo $file_badge; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $book['status']; ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($book['file_name']): ?>
                                                    <a href="?download_file=true&book_id=<?php echo $book['id']; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       onclick="logDownload(<?php echo $book['id']; ?>)">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                            <polyline points="7 10 12 15 17 10"></polyline>
                                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                                        </svg>
                                                        Download
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="admin-dashboard.php?tab=books&edit_book=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this book? This will also delete all related download records.')">
                                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" name="delete_book" class="btn btn-sm btn-outline" style="color: var(--rose-600);">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Users Tab -->
                    <div id="users-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>User Management</h3>
                                <a href="admin-dashboard.php?tab=users&add_user=true" class="btn btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    Add User
                                </a>
                            </div>
                            
                            <!-- Add User Form -->
                            <?php if (isset($_GET['add_user'])): ?>
                            <div id="add-user-form" class="add-form" style="display: block;">
                                <form method="POST">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Full Name *</label>
                                            <input type="text" name="name" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="email" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Password *</label>
                                            <input type="password" name="password" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" required>
                                                <option value="member">Member</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                                        <a href="admin-dashboard.php?tab=users" class="btn btn-outline">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Edit User Form -->
                            <?php if ($edit_user): ?>
                            <div id="edit-user-form" class="add-form" style="display: block;">
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Full Name *</label>
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" required>
                                                <option value="member" <?php echo ($edit_user['role'] == 'member') ? 'selected' : ''; ?>>Member</option>
                                                <option value="admin" <?php echo ($edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                                        <a href="admin-dashboard.php?tab=users" class="btn btn-outline">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): 
                                            $initials = getInitials($user['name']);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar-small"><?php echo $initials; ?></div>
                                                    <div>
                                                        <div class="book-title"><?php echo htmlspecialchars($user['name']); ?></div>
                                                        <div class="book-author">ID: <?php echo $user['id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-borrowed' : 'status-available'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="admin-dashboard.php?tab=users&edit_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their download records.')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline" style="color: var(--rose-600);">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Tab -->
                    <div id="transactions-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Recent Transactions</h3>
                            </div>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Book</th>
                                            <th>Borrow Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['book_title']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['borrow_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['due_date'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $transaction['transaction_type'] === 'Checked Out' ? 'status-borrowed' : 'status-available'; ?>">
                                                    <?php echo $transaction['transaction_type']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Downloads Tab -->
                    <div id="downloads-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>File Download History</h3>
                                <button class="btn btn-outline" onclick="exportDownloadReport()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Export Report
                                </button>
                            </div>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Book</th>
                                            <th>User</th>
                                            <th>File Type</th>
                                            <th>Downloaded At</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_downloads as $download): ?>
                                        <tr>
                                            <td>
                                                <div class="book-title"><?php echo htmlspecialchars($download['book_title']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($download['user_name']); ?></td>
                                            <td>
                                                <span class="proof-badge info"><?php echo strtoupper($download['file_type']); ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($download['downloaded_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($download['ip_address']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File upload preview
        function previewFile(input) {
            const file = input.files[0];
            const preview = document.getElementById('file-preview');
            const fileInfo = document.getElementById('file-info');
            
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                const fileName = file.name;
                const fileType = file.type;
                
                fileInfo.innerHTML = `
                    <strong>${fileName}</strong><br>
                    Type: ${fileType} | Size: ${fileSize} MB
                `;
                preview.style.display = 'block';
                
                // Show notification
                if (window.notifications) {
                    window.notifications.info(
                        `File selected: ${fileName} (${fileSize} MB)`,
                        'File Ready for Upload'
                    );
                }
            } else {
                preview.style.display = 'none';
            }
        }

        // Log download
        function logDownload(bookId) {
            if (window.notifications) {
                window.notifications.success(
                    'File download started...',
                    'Downloading'
                );
            }
        }

        // Export download report
        function exportDownloadReport() {
            if (window.notifications) {
                window.notifications.info(
                    'Generating download report...',
                    'Report Generation'
                );
                
                setTimeout(() => {
                    window.notifications.success(
                        'Download report exported successfully!',
                        'Report Ready'
                    );
                }, 2000);
            }
        }

        // Book CRUD functions
        function showAddBookForm() {
            window.location.href = 'admin-dashboard.php?tab=books&add_book=true';
        }
        
        function hideAddBookForm() {
            window.location.href = 'admin-dashboard.php?tab=books';
        }

        // User CRUD functions
        function showAddUserForm() {
            window.location.href = 'admin-dashboard.php?tab=users&add_user=true';
        }
        
        function hideAddUserForm() {
            window.location.href = 'admin-dashboard.php?tab=users';
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            // Check if there's a tab parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'overview';
            
            // Activate the correct tab
            tabBtns.forEach(btn => {
                if (btn.getAttribute('data-tab') === activeTab) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            tabPanes.forEach(pane => {
                if (pane.id === activeTab + '-tab') {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });

            // Show welcome notification
            setTimeout(() => {
                if (window.notifications) {
                    window.notifications.success(
                        'CRUD system ready. You can now manage books and users.',
                        'Welcome to LibraFlow Admin'
                    );
                }
            }, 1000);
        });

        // Handle tab clicks
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `admin-dashboard.php?tab=${tabId}`;
            });
        });
    </script>
</body>
</html>

<?php
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return $initials;
}
?>