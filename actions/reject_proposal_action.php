<?php
require_once '../includes/db.php';
session_start();

// 1. Check user authentication and type
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

// 2. Validate input
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: ../dashboard.php?error=invalid_proposal");
    exit();
}
$proposal_id = (int)$_GET['id'];
$client_id = $_SESSION['user_id'];

// Enable mysqli exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$project_id = 0; // Initialize project_id

try {
    // Get project_id from proposal and verify ownership in one go
    $sql_verify = "SELECT p.id FROM projects p JOIN proposals pr ON p.id = pr.project_id WHERE pr.id = ? AND p.client_id = ?";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bind_param("ii", $proposal_id, $client_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    
    if ($result_verify->num_rows == 0) {
        throw new Exception("Proposal not found or you do not have permission to modify it.");
    }
    
    $project_id = $result_verify->fetch_assoc()['id'];
    $stmt_verify->close();

    // Update proposal status to rejected
    $sql_reject_proposal = "UPDATE proposals SET status = 'rejected' WHERE id = ?";
    $stmt_reject_proposal = $conn->prepare($sql_reject_proposal);
    $stmt_reject_proposal->bind_param("i", $proposal_id);
    $stmt_reject_proposal->execute();
    $stmt_reject_proposal->close();
    
    $conn->close();

    // Redirect to the proposals page with a success message
    header("Location: ../view_proposals.php?id=" . $project_id . "&success=rejected");
    exit();

} catch (Exception $e) {
    // In a real app, log the specific error
    error_log("Reject Proposal Error: " . $e->getMessage());
    
    // Redirect with a generic error message
    $redirect_url = $project_id ? "../view_proposals.php?id=" . $project_id : "../dashboard.php";
    header("Location: " . $redirect_url . "&error=reject_failed");
    exit();
}
?>