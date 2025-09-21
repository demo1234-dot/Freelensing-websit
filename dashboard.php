<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

?>

<div class="container mt-5 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Dashboard</h1>
        <?php if ($user_type == 'client'): ?>
            <a href="post_project.php" class="btn btn-primary">Post a New Project</a>
        <?php else: ?>
            <a href="browse_projects.php" class="btn btn-primary">Browse Projects</a>
        <?php endif; ?>
    </div>

    <?php if ($user_type == 'freelancer'): ?>
        <!-- Freelancer Dashboard -->
        <h2>Welcome, Freelancer!</h2>
        <p>Here are your awarded projects.</p>

        <div class="card">
            <div class="card-header">Your Awarded Projects</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.* FROM projects p JOIN proposals pr ON p.id = pr.project_id WHERE pr.freelancer_id = ? AND pr.status = 'accepted' ORDER BY p.created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0): while($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><span class="badge bg-success"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span></td>
                                    <td>
                                        <a href="project_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View Project</a>
                                        <?php if ($row['status'] == 'completed'): ?>
                                            <a href="leave_review.php?project_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary">Leave Review</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="3">You have not been awarded any projects yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Client Dashboard -->
        <h2>Welcome, Client!</h2>
        <p>Here are the projects you have posted.</p>

        <div class="card">
            <div class="card-header">Your Posted Projects</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Status</th>
                                <th>Proposals</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*, COUNT(pr.id) as proposal_count FROM projects p LEFT JOIN proposals pr ON p.id = pr.project_id WHERE p.client_id = ? GROUP BY p.id ORDER BY p.created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0): while($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span></td>
                                    <td><?php echo $row['proposal_count']; ?></td>
                                    <td>
                                        <a href="view_proposals.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">View Proposals</a>
                                        <?php if ($row['status'] == 'completed'): ?>
                                            <a href="leave_review.php?project_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary">Leave Review</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="4">You have not posted any projects yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>