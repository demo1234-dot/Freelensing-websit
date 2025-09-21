<?php
require_once '../includes/db.php';
session_start();

// 1. Check user authentication and type
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

// 2. Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../post_project.php");
    exit();
}

// 3. Input Validation
$client_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$budget = filter_input(INPUT_POST, 'budget', FILTER_VALIDATE_FLOAT);
$deadline = $_POST['deadline'] ?? ''; // Basic validation, can be improved

$errors = [];
if (empty($title)) {
    $errors[] = "Title is required.";
}
if (empty($description)) {
    $errors[] = "Description is required.";
}
if ($budget === false || $budget < 0) {
    $errors[] = "A valid, non-negative budget is required.";
}
// A simple check to see if deadline is in a valid YYYY-MM-DD format
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $deadline)) {
    $errors[] = "A valid deadline is required.";
}

if (!empty($errors)) {
    // In a real app, you'd pass these errors back to the form
    // For now, we'll just redirect with a generic message
    header("Location: ../post_project.php?error=invalid_input");
    exit();
}

// 4. Database Insertion
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "INSERT INTO projects (client_id, title, description, budget, deadline) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Note: 'd' for double (for the DECIMAL budget)
    $stmt->bind_param("issds", $client_id, $title, $description, $budget, $deadline);

    $stmt->execute();
    $new_project_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    // 5. Redirect on Success
    // Redirect to the new project's detail page
    header("Location: ../project_details.php?id=" . $new_project_id . "&success=created");
    exit();

} catch (Exception $e) {
    // In a real app, log the specific error
    error_log("Post Project Error: " . $e->getMessage());
    // Redirect with a generic error
    header("Location: ../post_project.php?error=db_error");
    exit();
}
?>