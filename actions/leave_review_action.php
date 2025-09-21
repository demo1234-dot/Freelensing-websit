<?php
require_once '../includes/db.php';
session_start();

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

// --- Input Validation ---
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$reviewee_id = filter_input(INPUT_POST, 'reviewee_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review_text = trim($_POST['review_text'] ?? '');
$reviewer_id = $_SESSION['user_id'];

// Redirect if any required integer fields are missing or invalid
if (!$project_id || !$reviewee_id || !$rating) {
    header("Location: ../dashboard.php?error=invalid_input");
    exit();
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    header("Location: ../leave_review.php?project_id=$project_id&error=invalid_rating");
    exit();
}

// Enable mysqli exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // --- Security Check: Verify user was part of this project ---
    $sql_verify = "SELECT p.client_id, pr.freelancer_id FROM projects p 
                   JOIN proposals pr ON p.id = pr.project_id 
                   WHERE p.id = ? AND pr.status = 'accepted'";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bind_param("i", $project_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows == 0) {
        throw new Exception("Project not found or no accepted proposal exists.");
    }
    $project_participants = $result_verify->fetch_assoc();
    $stmt_verify->close();

    // Check if the reviewer was either the client or the freelancer on the project
    if ($reviewer_id != $project_participants['client_id'] && $reviewer_id != $project_participants['freelancer_id']) {
        throw new Exception("You are not authorized to review this project.");
    }
    // Check if the reviewee was the other party
    if ($reviewee_id != $project_participants['client_id'] && $reviewee_id != $project_participants['freelancer_id']) {
         throw new Exception("The user you are trying to review was not part of this project.");
    }


    // --- Check if user has already reviewed this project for this user ---
    $sql_check = "SELECT id FROM reviews WHERE project_id = ? AND reviewer_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $project_id, $reviewer_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: ../dashboard.php?error=already_reviewed");
        exit();
    }
    $stmt_check->close();

    // --- Insert the review ---
    $sql_insert = "INSERT INTO reviews (project_id, reviewer_id, reviewee_id, rating, review_text) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiiis", $project_id, $reviewer_id, $reviewee_id, $rating, $review_text);
    $stmt_insert->execute();
    $stmt_insert->close();

    // --- Redirect on Success ---
    header("Location: ../dashboard.php?success=review_submitted");
    exit();

} catch (Exception $e) {
    // In a real app, log the error message
    error_log("Leave Review Error: " . $e->getMessage());
    // Redirect with a generic error
    header("Location: ../dashboard.php?error=review_failed");
    exit();
}

?>