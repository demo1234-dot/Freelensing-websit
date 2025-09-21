<?php
require_once '../includes/db.php';
require_once '../includes/notifications.php';
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

// Enable mysqli exceptions for cleaner error handling within the transaction
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn->begin_transaction();

try {
    // Get project_id and freelancer_id from proposal
    $sql_get_ids = "SELECT project_id, freelancer_id FROM proposals WHERE id = ?";
    $stmt_get_ids = $conn->prepare($sql_get_ids);
    $stmt_get_ids->bind_param("i", $proposal_id);
    $stmt_get_ids->execute();
    $result_get_ids = $stmt_get_ids->get_result();
    if ($result_get_ids->num_rows == 0) {
        throw new Exception("Proposal not found.", 404);
    }
    $ids = $result_get_ids->fetch_assoc();
    $project_id = $ids['project_id'];
    $freelancer_id = $ids['freelancer_id'];
    $stmt_get_ids->close();

    // Verify client owns the project
    $sql_verify_owner = "SELECT id, title FROM projects WHERE id = ? AND client_id = ?";
    $stmt_verify_owner = $conn->prepare($sql_verify_owner);
    $stmt_verify_owner->bind_param("ii", $project_id, $client_id);
    $stmt_verify_owner->execute();
    $result_verify_owner = $stmt_verify_owner->get_result();
    if ($result_verify_owner->num_rows == 0) {
        throw new Exception("You do not have permission to modify this project.", 403);
    }
    $project = $result_verify_owner->fetch_assoc();
    $stmt_verify_owner->close();

    // Update this proposal status to accepted
    $sql_accept_proposal = "UPDATE proposals SET status = 'accepted' WHERE id = ?";
    $stmt_accept_proposal = $conn->prepare($sql_accept_proposal);
    $stmt_accept_proposal->bind_param("i", $proposal_id);
    $stmt_accept_proposal->execute();
    $stmt_accept_proposal->close();

    // Update project status to in_progress
    $sql_update_project = "UPDATE projects SET status = 'in_progress' WHERE id = ?";
    $stmt_update_project = $conn->prepare($sql_update_project);
    $stmt_update_project->bind_param("i", $project_id);
    $stmt_update_project->execute();
    $stmt_update_project->close();

    // Reject all other proposals for this project
    $sql_reject_others = "UPDATE proposals SET status = 'rejected' WHERE project_id = ? AND id != ?";
    $stmt_reject_others = $conn->prepare($sql_reject_others);
    $stmt_reject_others->bind_param("ii", $project_id, $proposal_id);
    $stmt_reject_others->execute();
    $stmt_reject_others->close();

    // Send notification to freelancer
    $sql_freelancer = "SELECT email FROM users WHERE id = ?";
    $stmt_freelancer = $conn->prepare($sql_freelancer);
    $stmt_freelancer->bind_param("i", $freelancer_id);
    $stmt_freelancer->execute();
    $result_freelancer = $stmt_freelancer->get_result();
    if ($result_freelancer->num_rows > 0) {
        $freelancer = $result_freelancer->fetch_assoc();
        $to = $freelancer['email'];
        $subject = "Congratulations! Your proposal for '" . $project['title'] . "' has been accepted.";
        $message = "Your proposal has been accepted. You can now start working on the project and send messages via the platform.";
        send_notification($to, $subject, $message);
    }
    $stmt_freelancer->close();

    // Everything was successful, commit the transaction
    $conn->commit();

    // Redirect to the proposals page with a success message
    header("Location: ../view_proposals.php?id=" . $project_id . "&success=accepted");
    exit();

} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    $conn->rollback();
    
    // Redirect with a generic error message. We can log the specific error.
    error_log("Accept Proposal Error: " . $e->getMessage());
    header("Location: ../view_proposals.php?id=" . ($project_id ?? 0) . "&error=accept_failed");
    exit();
}

// The connection is closed automatically when the script ends
?>