<?php
session_start();
include 'config.php';
$page_title = "LibraFlow - Discover Your Next Great Read";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
     <link rel="shortcut icon" href="./img/download.jfif" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="landing-page">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-bg-overlay"></div>
                    <div class="hero-content">
                        <h1>Discover Your Next <span class="text-accent">Great Read</span></h1>
                        <p>Access thousands of books, journals, and digital resources from the comfort of your home. Join our community of learners today.</p>
                        
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                <input type="text" placeholder="Search by title, author, or ISBN...">
                            </div>
                            <a href="catalog.php" class="btn btn-primary">Browse Catalog</a>
                        </div>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats-section">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3> 
                                <?php
                                $book_count = "SELECT COUNT(*) AS total_books FROM books";
                                $book_result = mysqli_query($conn, $book_count);
                                if ($book_result) {
                                    $row = mysqli_fetch_assoc($book_result);
                                    echo $row['total_books'];
                                } else {
                                    echo "0";
                                }
                                ?>+
                            </h3>
                            <p>Books Available</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-emerald">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3> 
                                <?php
                                $user_count = "SELECT COUNT(*) AS total_users FROM users";
                                $user_result = mysqli_query($conn, $user_count);
                                if ($user_result) {
                                    $row = mysqli_fetch_assoc($user_result);
                                    echo $row['total_users'];
                                } else {
                                    echo "0";
                                }
                                ?>+
                            </h3>
                            <p>Active Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-purple">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>24/7</h3>
                            <p>Digital Access</p>
                        </div>
                    </div>
                </div>

                <!-- New Arrivals Section -->
                <div class="featured-section">
                    <div class="section-header">
                        <h2>New Arrivals</h2>
                        <a href="catalog.php" class="view-all-link">View All 
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="featured-grid">
                        <?php
                        // Get the 3 newest books from the database
                        $new_books_query = "SELECT * FROM books ORDER BY id DESC LIMIT 3";
                        $new_books_result = mysqli_query($conn, $new_books_query);
                        
                        if ($new_books_result && mysqli_num_rows($new_books_result) > 0) {
                            while ($book = mysqli_fetch_assoc($new_books_result)) {
                                $cover_url = $book['cover_url'] ?? 'https://picsum.photos/seed/book' . $book['id'] . '/400/200';
                                $author = $book['author'] ?? 'Unknown Author';
                                $category = $book['category'] ?? 'General';
                                
                                echo '
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="' . $cover_url . '" alt="' . htmlspecialchars($book['title']) . '">
                                        <div class="book-status status-available">New</div>
                                    </div>
                                    <div class="book-info">
                                        <span class="book-category">' . htmlspecialchars($category) . '</span>
                                        <h3>' . htmlspecialchars($book['title']) . '</h3>
                                        <p>by ' . htmlspecialchars($author) . '</p>
                                        <div class="book-actions" style="margin-top: 1rem;">
                                            <a href="catalog.php" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            // Fallback if no books in database
                            $fallback_books = [
                                ['title' => 'The Future of AI', 'author' => 'Tech Visionary', 'cover' => 'https://picsum.photos/seed/book1/400/200', 'category' => 'Technology'],
                                ['title' => 'Modern Web Development', 'author' => 'Web Expert', 'cover' => 'https://picsum.photos/seed/book2/400/200', 'category' => 'Programming'],
                                ['title' => 'Data Science Essentials', 'author' => 'Data Scientist', 'cover' => 'https://picsum.photos/seed/book3/400/200', 'category' => 'Data Science']
                            ];
                            
                            foreach ($fallback_books as $book) {
                                echo '
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="' . $book['cover'] . '" alt="' . $book['title'] . '">
                                        <div class="book-status status-available">New</div>
                                    </div>
                                    <div class="book-info">
                                        <span class="book-category">' . $book['category'] . '</span>
                                        <h3>' . $book['title'] . '</h3>
                                        <p>by ' . $book['author'] . '</p>
                                        <div class="book-actions" style="margin-top: 1rem;">
                                            <a href="catalog.php" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
                                        </div>
                                    </div>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Recently Added Books Section -->
                <div class="featured-section">
                    <div class="section-header">
                        <h2>Recently Added</h2>
                        <a href="catalog.php?sort=newest" class="view-all-link">View All New Books
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="featured-grid">
                        <?php
                        // Get 6 most recently added books
                        $recent_books_query = "SELECT * FROM books ORDER BY id DESC LIMIT 6";
                        $recent_books_result = mysqli_query($conn, $recent_books_query);
                        
                        if ($recent_books_result && mysqli_num_rows($recent_books_result) > 0) {
                            while ($book = mysqli_fetch_assoc($recent_books_result)) {
                                $cover_url = $book['cover_url'] ?? 'https://picsum.photos/seed/book' . $book['id'] . '/400/200';
                                $author = $book['author'] ?? 'Unknown Author';
                                $category = $book['category'] ?? 'General';
                                $status = $book['status'] ?? 'Available';
                                $status_class = $status === 'Available' ? 'status-available' : 'status-borrowed';
                                
                                echo '
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="' . $cover_url . '" alt="' . htmlspecialchars($book['title']) . '">
                                        <div class="book-status ' . $status_class . '">' . $status . '</div>
                                    </div>
                                    <div class="book-info">
                                        <span class="book-category">' . htmlspecialchars($category) . '</span>
                                        <h3>' . htmlspecialchars($book['title']) . '</h3>
                                        <p>by ' . htmlspecialchars($author) . '</p>
                                        ' . (!empty($book['description']) ? '<p class="book-description" style="font-size: 0.875rem; color: #666; margin-bottom: 1rem;">' . substr(htmlspecialchars($book['description']), 0, 100) . '...</p>' : '') . '
                                        <div class="book-actions" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                            <a href="catalog.php" class="btn btn-outline" style="flex: 1; text-align: center;">View</a>
                                            ' . ($status === 'Available' ? '<button class="btn btn-primary" style="flex: 1;">Borrow</button>' : '') . '
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '<p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 2rem;">No books available yet. Check back soon for new additions!</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
        .book-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .book-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .book-cover {
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-status {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #10b981;
            color: white;
        }

        .status-borrowed {
            background: #ef4444;
            color: white;
        }

        .book-info {
            padding: 1.5rem;
        }

        .book-category {
            font-size: 0.75rem;
            font-weight: 600;
            color: #3b82f6;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }

        .book-info h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .book-info p {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .book-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .featured-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="js/main.js"></script>
</body>
</html>