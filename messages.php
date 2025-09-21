<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch projects user is involved in
if ($user_type == 'client') {
    $sql_projects = "SELECT p.id, p.title, u.first_name, u.last_name, pr.freelancer_id as other_user_id FROM projects p JOIN proposals pr ON p.id = pr.project_id JOIN users u ON pr.freelancer_id = u.id WHERE p.client_id = ? AND pr.status = 'accepted'";
    $stmt_projects = $conn->prepare($sql_projects);
    $stmt_projects->bind_param("i", $user_id);
} else { // freelancer
    $sql_projects = "SELECT p.id, p.title, u.first_name, u.last_name, p.client_id as other_user_id FROM projects p JOIN users u ON p.client_id = u.id JOIN proposals pr ON p.id = pr.project_id WHERE pr.freelancer_id = ? AND pr.status = 'accepted'";
    $stmt_projects = $conn->prepare($sql_projects);
    $stmt_projects->bind_param("i", $user_id);
}
$stmt_projects->execute();
$result_projects = $stmt_projects->get_result();

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$other_user_id = 0;
if ($project_id) {
    // This is a bit inefficient, we could get this from the first query, but for simplicity...
    $sql_other_user = "SELECT if(p.client_id = ?, pr.freelancer_id, p.client_id) as other_user_id FROM projects p JOIN proposals pr ON p.id = pr.project_id WHERE p.id = ? AND pr.status = 'accepted'";
    $stmt_other_user = $conn->prepare($sql_other_user);
    $stmt_other_user->bind_param("ii", $user_id, $project_id);
    $stmt_other_user->execute();
    $result_other_user = $stmt_other_user->get_result();
    if($row = $result_other_user->fetch_assoc()){
        $other_user_id = $row['other_user_id'];
    }
}

?>

<div class="container mt-5 fade-in">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Projects</h3>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($result_projects->num_rows > 0): ?>
                        <?php while ($project = $result_projects->fetch_assoc()): ?>
                            <a href="messages.php?project_id=<?php echo $project['id']; ?>" class="list-group-item list-group-item-action <?php echo ($project['id'] == $project_id) ? 'active' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h5>
                                </div>
                                <p class="mb-1">with <?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></p>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item">No active projects with messages.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Chat</h3>
                </div>
                <div class="card-body" id="chat-box" style="height: 500px; overflow-y: auto;">
                    <?php if ($project_id): ?>
                        <!-- Messages will be loaded here by chat.js -->
                    <?php else: ?>
                        <div class="text-center h-100 d-flex align-items-center justify-content-center">
                            <p class="text-muted">Please select a project to view messages.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($project_id): ?>
                <div class="card-footer">
                    <form id="message-form">
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo $project_id; ?>">
                        <input type="hidden" name="receiver_id" id="receiver_id" value="<?php echo $other_user_id; ?>">
                        <div class="input-group">
                            <textarea class="form-control" name="message" id="message-input" placeholder="Type your message..." rows="1"></textarea>
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.message {
    margin-bottom: 15px;
}
.message .bubble {
    padding: 10px 15px;
    border-radius: 20px;
    max-width: 70%;
    display: inline-block;
}
.message.sent .bubble {
    background-color: #007bff;
    color: white;
}
.message.received .bubble {
    background-color: #f1f1f1;
}
.message.sent {
    text-align: right;
}
</style>

<script>
    const myUserId = <?php echo $_SESSION['user_id']; ?>;
</script>
<script src="js/chat.js"></script>

<?php include 'includes/footer.php'; ?>