<?php
include 'config.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info if logged in
$user_name = "Guest";
$user_role = "guest";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user_data = $result->fetch_assoc()) {
        $user_name = $user_data['name'];
        $user_role = $user_data['role'];
    }
    $stmt->close();
}
?>
<header class="header">
    <div class="breadcrumb">
        <span class="app-name">LibraFlow</span>
        <span>/</span>
        <span class="current">
            <?php 
            $page_names = [
                'index.php' => 'Home',
                'roadmap.php' => 'Project',
                'catalog.php' => 'Book Catalog',
                'auth.php' => 'Authentication',
                'user-dashboard.php' => 'My Dashboard',
                'admin-dashboard.php' => 'Admin Dashboard'
            ];
            echo $page_names[$current_page] ?? ucfirst(str_replace('.php', '', $current_page));
            ?>
        </span>
    </div>
    
    <div class="header-actions">
        <button class="settings-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </button>
        
        <div class="user-profile">
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="user-role"><?php echo ucfirst($user_role); ?></p>
            </div>
            <img src="https://picsum.photos/seed/admin/100/100" alt="User" class="user-avatar">
        </div>
    </div>
</header>