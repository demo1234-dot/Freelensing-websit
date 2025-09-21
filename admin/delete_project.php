<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header("Location: index.php");
    exit();
}

// Optional: Add a confirmation step before deleting
// For now, direct deletion for simplicity

$sql_delete = "DELETE FROM projects WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $project_id);

if ($stmt_delete->execute()) {
    // Redirect back to the admin dashboard with a success message
    header("Location: index.php?message=Project deleted successfully!");
    exit();
} else {
    // Redirect back to the admin dashboard with an error message
    header("Location: index.php?error=Error deleting project: " . $stmt_delete->error);
    exit();
}

$stmt_delete->close();
$conn->close();
?>