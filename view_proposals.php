<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$project_id = $_GET['id'];

// Fetch project details to ensure the client owns this project
$sql_project = "SELECT * FROM projects WHERE id = ? AND client_id = ?";
$stmt_project = $conn->prepare($sql_project);
$stmt_project->bind_param("ii", $project_id, $_SESSION['user_id']);
$stmt_project->execute();
$result_project = $stmt_project->get_result();
if ($result_project->num_rows == 0) {
    echo "<div class='container mt-5 text-center'>\n            <h2>Project Not Found</h2>\n            <p>Sorry, the project you are looking for does not exist or you do not have permission to view it.</p>\n            <a href='dashboard.php' class='btn btn-primary'>Back to Dashboard</a>\n          </div>";
    include 'includes/footer.php';
    exit();
}
$project = $result_project->fetch_assoc();

// Fetch proposals for the project
$sql_proposals = "SELECT pr.*, u.first_name, u.last_name, u.profile_picture FROM proposals pr JOIN users u ON pr.freelancer_id = u.id WHERE pr.project_id = ? ORDER BY pr.created_at DESC";
$stmt_proposals = $conn->prepare($sql_proposals);
$stmt_proposals->bind_param("i", $project_id);
$stmt_proposals->execute();
$result_proposals = $stmt_proposals->get_result();
?>

<div class="container mt-5 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Proposals for \"<?php echo htmlspecialchars($project['title']); ?>\"</h2>
            <a href="project_details.php?id=<?php echo $project_id; ?>" class="text-muted">Back to Project</a>
        </div>
        <span class="badge bg-primary rounded-pill fs-6"><?php echo $result_proposals->num_rows; ?> Proposals</span>
    </div>


    <?php if ($result_proposals->num_rows > 0): ?>
        <div class="row">
            <?php while($proposal = $result_proposals->fetch_assoc()): ?>
                <div class="col-md-12 mb-4">
                    <div class="card card-hover">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 text-center">
                                    <img src="uploads/<?php echo htmlspecialchars($proposal['profile_picture'] ?: 'default-avatar.png'); ?>" alt="Freelancer" class="img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                    <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']); ?></h5>
                                </div>
                                <div class="col-md-10">
                                    <div class="d-flex justify-content-between">
                                        <h4 class="mb-0">Bid: <span class="text-success fw-bold">$<?php echo htmlspecialchars($proposal['bid_amount']); ?></span></h4>
                                        <small class="text-muted">Submitted: <?php echo date("M d, Y", strtotime($proposal['created_at'])); ?></small>
                                    </div>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?></p>
                                    
                                    <div class="mt-3">
                                    <?php if ($project['status'] == 'open'): ?>
                                        <a href="actions/accept_proposal_action.php?id=<?php echo $proposal['id']; ?>" class="btn btn-success me-2">Accept</a>
                                        <a href="actions/reject_proposal_action.php?id=<?php echo $proposal['id']; ?>" class="btn btn-danger">Reject</a>
                                    <?php else: ?>
                                        <p class="mb-0"><strong>Status:</strong> 
                                            <?php 
                                            $status_class = 'secondary';
                                            if ($proposal['status'] == 'accepted') $status_class = 'success';
                                            if ($proposal['status'] == 'rejected') $status_class = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">No Proposals Yet</h5>
                <p class="card-text">No proposals have been submitted for this project yet. Check back later!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
