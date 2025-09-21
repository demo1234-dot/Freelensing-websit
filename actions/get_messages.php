<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}

if (!isset($_GET['project_id'])) {
    echo json_encode(['error' => 'Project ID not provided.']);
    exit();
}

$project_id = (int)$_GET['project_id'];
$user_id = $_SESSION['user_id'];

// Verify user is part of the project
$sql_verify = "SELECT id FROM projects WHERE id = ? AND (client_id = ? OR id IN (SELECT project_id FROM proposals WHERE freelancer_id = ? AND status = 'accepted'))";
$stmt_verify = $conn->prepare($sql_verify);
if ($stmt_verify === false) {
    echo json_encode(['error' => 'DB verify prepare failed.']);
    exit();
}
$stmt_verify->bind_param("iii", $project_id, $user_id, $user_id);
$stmt_verify->execute();
if ($stmt_verify->get_result()->num_rows == 0) {
    echo json_encode(['error' => 'You are not authorized to view messages for this project.']);
    exit(); // User is not part of this project
}
$stmt_verify->close();


$sql = "SELECT * FROM messages WHERE project_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['error' => 'DB message prepare failed.']);
    exit();
}
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

$stmt->close();
$conn->close();
?>