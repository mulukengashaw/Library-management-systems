<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        handleLogin();
    } elseif ($action === 'register') {
        handleRegister();
    }
}

function handleLogin() {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        header('Location: ../auth.php?error=Please fill in all required fields.');
        exit;
    }
    
    // Simulate successful login
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Alex Librarian',
        'email' => $email,
        'role' => 'member'
    ];
    
    // Redirect to user dashboard
    header('Location: ../user-dashboard.php');
    exit;
}

function handleRegister() {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../auth.php?error=Please fill in all required fields.');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: ../auth.php?error=Passwords do not match.');
        exit;
    }
    
    // Simulate successful registration
    $_SESSION['user'] = [
        'id' => 1,
        'name' => $name,
        'email' => $email,
        'role' => 'member'
    ];
    
    // Redirect to user dashboard
    header('Location: ../user-dashboard.php');
    exit;
}