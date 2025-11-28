<?php
// Notification helper functions
function showSuccessNotification($message, $title = "Success!") {
    echo "<script>notifications.success('" . addslashes($message) . "', '" . addslashes($title) . "');</script>";
}

function showErrorNotification($message, $title = "Error!") {
    echo "<script>notifications.error('" . addslashes($message) . "', '" . addslashes($title) . "');</script>";
}

function showWarningNotification($message, $title = "Warning!") {
    echo "<script>notifications.warning('" . addslashes($message) . "', '" . addslashes($title) . "');</script>";
}

function showInfoNotification($message, $title = "Information") {
    echo "<script>notifications.info('" . addslashes($message) . "', '" . addslashes($title) . "');</script>";
}

// Proof generation functions
function generateTransactionProof($type, $bookTitle, $userId, $additionalData = []) {
    $proof = [
        'id' => 'PROOF-' . time() . '-' . rand(1000, 9999),
        'type' => $type,
        'book' => $bookTitle,
        'user_id' => $userId,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $additionalData
    ];
    
    // In a real application, you might want to store this in the database
    return $proof;
}

function displayTransactionProof($proof) {
    $proofHTML = "
    <div class='action-proof'>
        <div class='action-proof-header'>
            <div class='action-proof-title'>Transaction #{$proof['id']}</div>
            <div class='action-proof-time'>{$proof['timestamp']}</div>
        </div>
        <div class='action-proof-content'>
            <strong>" . ucfirst($proof['type']) . ":</strong> {$proof['book']}<br>
            User ID: {$proof['user_id']}<br>
            " . (!empty($proof['data']['due_date']) ? "Due: {$proof['data']['due_date']}" : "") . "
        </div>
    </div>";
    
    return $proofHTML;
}
?>