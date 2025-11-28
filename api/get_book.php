<?php
include '../config.php';

if (isset($_GET['id'])) {
    $book_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($book);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Book ID required']);
}
?>