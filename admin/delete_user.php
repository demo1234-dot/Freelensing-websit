<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Optional: Add a confirmation step before deleting
// For now, direct deletion for simplicity

$sql_delete = "DELETE FROM users WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $user_id);

if ($stmt_delete->execute()) {
    // Redirect back to the admin dashboard with a success message
    header("Location: index.php?message=User deleted successfully!");
    exit();
} else {
    // Redirect back to the admin dashboard with an error message
    header("Location: index.php?error=Error deleting user: " . $stmt_delete->error);
    exit();
}

$stmt_delete->close();
$conn->close();
?>