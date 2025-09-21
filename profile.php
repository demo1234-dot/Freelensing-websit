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

// Fetch reviews
$sql_reviews = "SELECT r.*, u.first_name, u.last_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $user_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();

$total_rating = 0;
$review_count = $result_reviews->num_rows;
$average_rating = 0;
if ($review_count > 0) {
    while ($review = $result_reviews->fetch_assoc()) {
        $total_rating += $review['rating'];
    }
    $average_rating = round($total_rating / $review_count, 1);
    $result_reviews->data_seek(0); // Reset pointer
}

function display_stars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html;
}

?>

<div class="container mt-5 fade-in">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Sidebar -->
            <div class="card text-center">
                <div class="card-body">
                    <img src="uploads/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default-avatar.png'); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3 profile-picture">
                    <h4 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted"><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></p>
                    <hr>
                    <?php if ($user['user_type'] == 'freelancer'): ?>
                        <p><strong>Hourly Rate:</strong> $<?php echo htmlspecialchars($user['hourly_rate']); ?></p>
                        <div class="star-rating">
                            <?php echo display_stars($average_rating); ?>
                            <span><?php echo $average_rating; ?> (<?php echo $review_count; ?> reviews)</span>
                        </div>
                    <?php else: // client ?>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($user['company_name']); ?></p>
                    <?php endif; ?>
                    <a href="edit_profile.php" class="btn btn-primary mt-3">Edit Profile</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Profile Content -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab" aria-controls="about" aria-selected="true">About</button>
                        </li>
                        <?php if ($user['user_type'] == 'freelancer'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="portfolio-tab" data-bs-toggle="tab" data-bs-target="#portfolio" type="button" role="tab" aria-controls="portfolio" aria-selected="false">Portfolio</button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reviews</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- About Tab -->
                        <div class="tab-pane fade show active" id="about" role="tabpanel" aria-labelledby="about-tab">
                            <h5 class="card-title">About Me</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <?php if ($user['user_type'] == 'freelancer'): ?>
                            <hr>
                            <h5 class="card-title">Skills</h5>
                            <div>
                                <?php 
                                $skills = explode(',', $user['skills']);
                                foreach ($skills as $skill) {
                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($skill)) . '</span>';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Portfolio Tab -->
                        <?php if ($user['user_type'] == 'freelancer'): ?>
                        <div class="tab-pane fade" id="portfolio" role="tabpanel" aria-labelledby="portfolio-tab">
                            <h5 class="card-title">Portfolio</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($user['portfolio'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                            <h5 class="card-title">Reviews</h5>
                            <?php if ($review_count > 0): ?>
                                <?php while($review = $result_reviews->fetch_assoc()): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                            <div class="star-rating">
                                                <?php echo display_stars($review['rating']); ?>
                                            </div>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    </div>
                                    <hr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No reviews yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>