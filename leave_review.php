<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['project_id'])) {
    header("Location: dashboard.php");
    exit();
}

$project_id = (int)$_GET['project_id'];
$user_id = $_SESSION['user_id'];

// Get project details and verify user is part of the project and it is completed
$sql_project = "SELECT * FROM projects WHERE id = ? AND status = 'completed' AND (client_id = ? OR id IN (SELECT project_id FROM proposals WHERE freelancer_id = ? AND status = 'accepted'))";
$stmt_project = $conn->prepare($sql_project);
$stmt_project->bind_param("iii", $project_id, $user_id, $user_id);
$stmt_project->execute();
$result_project = $stmt_project->get_result();
if ($result_project->num_rows == 0) {
    echo "Project not found, not completed, or you are not part of it.";
    exit();
}
$project = $result_project->fetch_assoc();

// Determine reviewee
$reviewee_id = 0;
if ($_SESSION['user_type'] == 'client') {
    $sql_freelancer = "SELECT freelancer_id FROM proposals WHERE project_id = ? AND status = 'accepted'";
    $stmt_freelancer = $conn->prepare($sql_freelancer);
    $stmt_freelancer->bind_param("i", $project_id);
    $stmt_freelancer->execute();
    $result_freelancer = $stmt_freelancer->get_result();
    if ($result_freelancer->num_rows > 0) {
        $reviewee_id = $result_freelancer->fetch_assoc()['freelancer_id'];
    }
} else { // freelancer
    $reviewee_id = $project['client_id'];
}

if ($reviewee_id == 0) {
    echo "Could not determine the user to review.";
    exit();
}

?>

<div class="container">
    <h2>Leave a Review for "<?php echo htmlspecialchars($project['title']); ?>"</h2>
    <form action="actions/leave_review_action.php" method="POST">
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
        <input type="hidden" name="reviewee_id" value="<?php echo $reviewee_id; ?>">
        <div>
            <label for="rating">Rating (1-5)</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>
        </div>
        <div>
            <label for="review_text">Review</label>
            <textarea id="review_text" name="review_text" required></textarea>
        </div>
        <button type="submit">Submit Review</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>