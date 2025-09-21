<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>

<div class="container mt-5 fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Edit Profile</h2>
                </div>
                <div class="card-body">
                    <form action="actions/edit_profile_action.php" method="POST" enctype="multipart/form-data">
                        
                        <?php if ($user['user_type'] == 'freelancer'): ?>
                            <!-- Fields for Freelancer -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Hourly Rate ($)</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" step="0.01" value="<?php echo htmlspecialchars($user['hourly_rate']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills (comma-separated)</label>
                                <input type="text" class="form-control" id="skills" name="skills" value="<?php echo htmlspecialchars($user['skills']); ?>" placeholder="e.g., PHP, React, Graphic Design">
                            </div>
                            <div class="mb-3">
                                <label for="portfolio" class="form-label">Portfolio / Website Link</label>
                                <input type="text" class="form-control" id="portfolio" name="portfolio" value="<?php echo htmlspecialchars($user['portfolio']); ?>" placeholder="https://your-portfolio.com">
                            </div>

                        <?php else: // client ?>
                            <!-- Fields for Client -->
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name']); ?>">
                            </div>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                            </div>

                        <?php endif; ?>

                        <!-- Common Fields -->
                        <hr>
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Update Profile Picture</label>
                            <input class="form-control" type="file" id="profile_picture" name="profile_picture">
                            <small class="form-text text-muted">Leave blank to keep your current picture.</small>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="profile.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>