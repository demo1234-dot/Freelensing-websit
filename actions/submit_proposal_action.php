<?php
require_once '../includes/db.php';
require_once '../includes/notifications.php';
session_start();

// 1. Check user authentication and type
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'freelancer') {
    header("Location: ../login.php");
    exit();
}

// 2. Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

// 3. Input Validation
$freelancer_id = $_SESSION['user_id'];
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
$proposal_text = trim($_POST['proposal_text'] ?? '');

// Redirect if any required fields are missing or invalid
if (!$project_id || $bid_amount === false || empty($proposal_text) || $bid_amount < 0) {
    header("Location: ../project_details.php?id=$project_id&error=invalid_input");
    exit();
}

// 4. Database Logic
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // --- Security & Logic Checks ---
    // Check if project is open for proposals
    $sql_check_project = "SELECT status, client_id FROM projects WHERE id = ?";
    $stmt_check_project = $conn->prepare($sql_check_project);
    $stmt_check_project->bind_param("i", $project_id);
    $stmt_check_project->execute();
    $result_project = $stmt_check_project->get_result();
    if ($result_project->num_rows == 0) {
        throw new Exception("Project not found.");
    }
    $project = $result_project->fetch_assoc();
    $stmt_check_project->close();

    if ($project['status'] !== 'open') {
        header("Location: ../project_details.php?id=$project_id&error=project_not_open");
        exit();
    }
    
    // Check if freelancer is trying to bid on their own project
    if ($project['client_id'] == $freelancer_id) {
        header("Location: ../project_details.php?id=$project_id&error=own_project");
        exit();
    }

    // Check if freelancer has already submitted a proposal
    $sql_check_proposal = "SELECT id FROM proposals WHERE project_id = ? AND freelancer_id = ?";
    $stmt_check_proposal = $conn->prepare($sql_check_proposal);
    $stmt_check_proposal->bind_param("ii", $project_id, $freelancer_id);
    $stmt_check_proposal->execute();
    if ($stmt_check_proposal->get_result()->num_rows > 0) {
        header("Location: ../project_details.php?id=$project_id&error=already_submitted");
        exit();
    }
    $stmt_check_proposal->close();

    // --- Insert the proposal ---
    $sql_insert = "INSERT INTO proposals (project_id, freelancer_id, proposal_text, bid_amount) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iisd", $project_id, $freelancer_id, $proposal_text, $bid_amount);
    $stmt_insert->execute();
    $stmt_insert->close();

    // --- Send notification to client ---
    $sql_client = "SELECT u.email, p.title FROM users u JOIN projects p ON u.id = p.client_id WHERE p.id = ?";
    $stmt_client = $conn->prepare($sql_client);
    $stmt_client->bind_param("i", $project_id);
    $stmt_client->execute();
    $result_client = $stmt_client->get_result();
    if ($result_client->num_rows > 0) {
        $client = $result_client->fetch_assoc();
        $to = $client['email'];
        $subject = "New Proposal for your project: " . $client['title'];
        $message = "You have received a new proposal for your project. Please log in to view it.";
        send_notification($to, $subject, $message);
    }
    $stmt_client->close();
    $conn->close();

    // --- Redirect on Success ---
    header("Location: ../project_details.php?id=$project_id&success=proposal_submitted");
    exit();

} catch (Exception $e) {
    error_log("Submit Proposal Error: " . $e->getMessage());
    header("Location: ../project_details.php?id=$project_id&error=submit_failed");
    exit();
}
?>