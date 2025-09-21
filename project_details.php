<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: browse_projects.php");
    exit();
}

$project_id = $_GET['id'];
// Also fetch client info
$sql = "SELECT p.*, u.first_name, u.last_name, u.company_name FROM projects p JOIN users u ON p.client_id = u.id WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // A bit nicer "not found" page
    echo "<div class='container mt-5 text-center'>
            <h2>Project Not Found</h2>
            <p>Sorry, the project you are looking for does not exist.</p>
            <a href='browse_projects.php' class='btn btn-primary'>Back to Browse Projects</a>
          </div>";
    include 'includes/footer.php';
    exit();
}

$project = $result->fetch_assoc();
?>

<div class="container mt-5 fade-in">
    <div class="row">
        <div class="col-lg-8">
            <!-- Project Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h2>
                    <p class="card-text text-muted">Posted by <?php echo htmlspecialchars($project['company_name'] ?: $project['first_name'] . ' ' . $project['last_name']); ?></p>
                    <hr>
                    <h5 class="card-subtitle mb-2">Project Description</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                </div>
            </div>

            <!-- Proposal Submission Form for Freelancers -->
            <?php if ($_SESSION['user_type'] == 'freelancer'): ?>
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Submit Your Proposal</h4>
                    </div>
                    <div class="card-body">
                        <form action="actions/submit_proposal_action.php" method="POST">
                            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                            <div class="mb-3">
                                <label for="proposal_text" class="form-label">Your Proposal</label>
                                <textarea class="form-control" id="proposal_text" name="proposal_text" rows="5" required placeholder="Explain why you are the best fit for this project..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="bid_amount" class="form-label">Your Bid Amount ($)</label>
                                <input type="number" class="form-control" id="bid_amount" name="bid_amount" step="0.01" required placeholder="e.g., 500.00">
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Proposal</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <!-- Project Meta Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Project Details</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Budget:</strong>
                            <span class="float-end">$<?php echo htmlspecialchars($project['budget']); ?></span>
                        </li>
                        <li class="list-group-item">
                            <strong>Deadline:</strong>
                            <span class="float-end"><?php echo date("M d, Y", strtotime(htmlspecialchars($project['deadline']))); ?></span>
                        </li>
                        <li class="list-group-item">
                            <strong>Status:</strong>
                            <span class="float-end"><span class="badge bg-success"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>