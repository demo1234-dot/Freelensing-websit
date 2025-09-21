<?php
include 'header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header("Location: index.php");
    exit();
}

// Fetch project details
$sql_project = "SELECT p.*, u.email as client_email, u.first_name, u.last_name, u.company_name FROM projects p JOIN users u ON p.client_id = u.id WHERE p.id = ?";
$stmt_project = $conn->prepare($sql_project);
$stmt_project->bind_param("i", $project_id);
$stmt_project->execute();
$result_project = $stmt_project->get_result();
$project = $result_project->fetch_assoc();

if (!$project) {
    echo "<div class='alert alert-danger'>Project not found.</div>";
    include 'footer.php';
    exit();
}

?>

<div class="container">
    <h2>Project Details: <?php echo htmlspecialchars($project['title']); ?></h2>

    <div class="card mb-3">
        <div class="card-header">
            Project Information
        </div>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
            <p class="card-text"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
            <p class="card-text"><strong>Budget:</strong> $<?php echo htmlspecialchars(number_format($project['budget'], 2)); ?></p>
            <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($project['status']); ?></p>
            <p class="card-text"><strong>Posted Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($project['created_at']))); ?></p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Client Information
        </div>
        <div class="card-body">
            <p class="card-text"><strong>Client Email:</strong> <?php echo htmlspecialchars($project['client_email']); ?></p>
            <?php if ($project['user_type'] == 'freelancer'): // Assuming client can also be a freelancer type in users table, adjust if needed ?>
                <p class="card-text"><strong>Client Name:</strong> <?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></p>
            <?php else: ?>
                <p class="card-text"><strong>Company Name:</strong> <?php echo htmlspecialchars($project['company_name']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
</div>

<?php include 'footer.php'; ?>