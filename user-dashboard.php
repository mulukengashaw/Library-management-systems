<?php
include 'config.php';
$page_title = "LibraFlow - My Dashboard";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's current loans
$stmt = $conn->prepare("
    SELECT l.*, b.title, b.author, b.cover_url 
    FROM loans l 
    JOIN books b ON l.book_id = b.id 
    WHERE l.user_id = ? AND l.status = 'Active'
    ORDER BY l.due_date
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loans_result = $stmt->get_result();
$current_loans = $loans_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_loans,
        SUM(CASE WHEN l.status = 'Active' THEN 1 ELSE 0 END) as active_loans,
        COALESCE(SUM(f.amount), 0) as total_fines
    FROM loans l 
    LEFT JOIN fines f ON l.id = f.loan_id AND f.status = 'Pending'
    WHERE l.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$user_stats = $stats_result->fetch_assoc();
$stmt->close();

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $loan_id = intval($_POST['loan_id']);
    $return_date = date('Y-m-d');
    
    // Update loan record
    $stmt = $conn->prepare("UPDATE loans SET return_date = ?, status = 'Returned' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $return_date, $loan_id, $user_id);
    
    if ($stmt->execute()) {
        // Get book ID to update its status
        $stmt = $conn->prepare("SELECT book_id FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        
        // Update book status
        $stmt = $conn->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
        $stmt->bind_param("i", $loan['book_id']);
        $stmt->execute();
        
        $success = "Book returned successfully!";
        header("Location: user-dashboard.php");
        exit;
    }
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
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="user-dashboard">
                <!-- Header -->
                <div class="dashboard-header">
                    <h1>My Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. 
                    You have <?php echo count($current_loans); ?> item(s) due soon.</p>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <!-- Status Overview -->
                <div class="stats-grid">
                    <div class="stat-card user-stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Books Borrowed</p>
                            <h3 class="stat-value"><?php echo $user_stats['active_loans']; ?></h3>
                            <p class="stat-status text-emerald">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                                Good standing
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card user-stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Total Loans</p>
                            <h3 class="stat-value"><?php echo $user_stats['total_loans']; ?></h3>
                            <p class="stat-status text-blue">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                All time
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card user-stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Unpaid Fines</p>
                            <h3 class="stat-value">$<?php echo number_format($user_stats['total_fines'], 2); ?></h3>
                            <p class="stat-status <?php echo $user_stats['total_fines'] > 0 ? 'text-rose' : 'text-slate-400'; ?>">
                                <?php echo $user_stats['total_fines'] > 0 ? 'Payment due' : 'No outstanding fees'; ?>
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Loans Section -->
                <div class="loans-section">
                    <div class="section-card">
                        <div class="section-header">
                            <h3>Current Loans</h3>
                            <a href="loan-history.php" class="view-history-btn">View History</a>
                        </div>
                        
                        <div class="loans-list">
                            <?php if (empty($current_loans)): ?>
                                <div class="no-loans">
                                    <p>You don't have any active loans.</p>
                                    <a href="catalog.php" class="btn btn-primary">Browse Catalog</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($current_loans as $loan): 
                                    $due_date = new DateTime($loan['due_date']);
                                    $today = new DateTime();
                                    $days_remaining = $today->diff($due_date)->days;
                                    $is_overdue = $today > $due_date;
                                    
                                    $status_class = $is_overdue ? 'status-warning' : 'status-ok';
                                    $status_text = $is_overdue ? 'Overdue' : ($days_remaining <= 7 ? 'Due Soon' : 'On Track');
                                ?>
                                <div class="loan-item">
                                    <div class="loan-cover">
                                        <img src="<?php echo htmlspecialchars($loan['cover_url']); ?>" alt="<?php echo htmlspecialchars($loan['title']); ?>">
                                    </div>
                                    <div class="loan-info">
                                        <h4><?php echo htmlspecialchars($loan['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($loan['author']); ?></p>
                                    </div>
                                    <div class="loan-due">
                                        <div class="due-date">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                            <span>Due: <strong><?php echo $due_date->format('M j, Y'); ?></strong></span>
                                        </div>
                                        <span class="loan-status <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                            <?php if ($is_overdue): ?>
                                                (<?php echo $days_remaining; ?> days overdue)
                                            <?php else: ?>
                                                (<?php echo $days_remaining; ?> days left)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <form method="POST" class="return-form">
                                        <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                        <button type="submit" name="return_book" class="renew-btn">Return</button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>