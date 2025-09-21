<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Enable mysqli exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Check user authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated.', 401);
    }

    // 2. Check for POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.', 405);
    }

    // 3. Input Validation
    $sender_id = $_SESSION['user_id'];
    $project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');

    if (!$project_id || !$receiver_id || empty($message)) {
        throw new Exception('Missing required fields.', 400);
    }

    // 4. Security Check: Verify the sender is authorized to message on this project
    $sql_verify = "SELECT p.client_id, pr.freelancer_id FROM projects p 
                   JOIN proposals pr ON p.id = pr.project_id 
                   WHERE p.id = ? AND pr.status = 'accepted'";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bind_param("i", $project_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows == 0) {
        throw new Exception("Project not found or no accepted proposal exists.", 404);
    }
    $project_participants = $result_verify->fetch_assoc();
    $stmt_verify->close();

    // Check if the sender and receiver are the two parties involved in the project
    $valid_users = [$project_participants['client_id'], $project_participants['freelancer_id']];
    if (!in_array($sender_id, $valid_users) || !in_array($receiver_id, $valid_users) || $sender_id == $receiver_id) {
        throw new Exception("You are not authorized to send a message on this project.", 403);
    }

    // 5. Database Insertion
    $sql_insert = "INSERT INTO messages (project_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiis", $project_id, $sender_id, $receiver_id, $message);
    $stmt_insert->execute();
    $stmt_insert->close();
    $conn->close();

    // 6. Send Success Response
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Set HTTP response code based on exception code, if available
    $code = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
    http_response_code($code);
    
    // In a real app, you might want to log the error message
    error_log("Send Message Error: " . $e->getMessage());

    // Send a generic error response
    echo json_encode(['success' => false, 'error' => 'An error occurred while sending the message.']);
}
?>