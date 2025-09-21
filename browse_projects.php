<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT p.*, u.company_name FROM projects p JOIN users u ON p.client_id = u.id";
$params = [];
$types = '';

// Keyword search
if (!empty($_GET['keywords'])) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $keywords = '%' . $_GET['keywords'] . '%';
    $params[] = &$keywords;
    $params[] = &$keywords;
    $types .= 'ss';
}

// Skills search
if (!empty($_GET['skills'])) {
    $sql .= " AND p.required_skills LIKE ?"; // Assuming a new column for required_skills in projects table
    $skills = '%' . $_GET['skills'] . '%';
    $params[] = &$skills;
    $types .= 's';
}

// Budget range
if (!empty($_GET['min_budget'])) {
    $sql .= " AND p.budget >= ?";
    $params[] = &$_GET['min_budget'];
    $types .= 'd';
}

if (!empty($_GET['max_budget'])) {
    $sql .= " AND p.budget <= ?";
    $params[] = &$_GET['max_budget'];
    $types .= 'd';
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container mt-5 fade-in">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <h4>Filters</h4>
            <div class="card">
                <div class="card-body">
                    <form action="browse_projects.php" method="GET">
                        <div class="mb-3">
                            <label for="keywords" class="form-label">Keywords</label>
                            <input type="text" class="form-control" id="keywords" name="keywords" value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills</label>
                            <input type="text" class="form-control" id="skills" name="skills" placeholder="e.g., php, css" value="<?php echo isset($_GET['skills']) ? htmlspecialchars($_GET['skills']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="min_budget" class="form-label">Min Budget</label>
                            <input type="number" class="form-control" id="min_budget" name="min_budget" value="<?php echo isset($_GET['min_budget']) ? htmlspecialchars($_GET['min_budget']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="max_budget" class="form-label">Max Budget</label>
                            <input type="number" class="form-control" id="max_budget" name="max_budget" value="<?php echo isset($_GET['max_budget']) ? htmlspecialchars($_GET['max_budget']) : ''; ?>">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Project Listings -->
        <div class="col-lg-9">
            <h2 class="mb-4">Browse Open Projects</h2>
            <?php if ($result->num_rows > 0): ?>
                <div class="row">
                    <?php while($project = $result->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 card-hover">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                    <p class="card-text text-muted">by <?php echo htmlspecialchars($project['company_name']); ?></p>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($project['description']), 0, 100); ?>...</p>
                                    
                                    <div class="mt-auto">
                                        <p class="fw-bold mb-2">Budget: $<?php echo htmlspecialchars($project['budget']); ?></p>
                                        <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    No open projects found matching your criteria. Try broadening your search.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>