<?php
include 'header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Fetch user details
$sql_user = "SELECT id, email, user_type, first_name, last_name, company_name, is_admin FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    include 'footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];
    $first_name = $_POST['first_name'] ?? null;
    $last_name = $_POST['last_name'] ?? null;
    $company_name = $_POST['company_name'] ?? null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Basic validation
    if (empty($email) || empty($user_type)) {
        $error = "Email and User Type are required.";
    } else {
        // Update user details
        $sql_update = "UPDATE users SET email = ?, user_type = ?, first_name = ?, last_name = ?, company_name = ?, is_admin = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssii", $email, $user_type, $first_name, $last_name, $company_name, $is_admin, $user_id);

        if ($stmt_update->execute()) {
            $success = "User updated successfully!";
            // Re-fetch user data to display updated info
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            $user = $result_user->fetch_assoc();
        } else {
            $error = "Error updating user: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}
?>

<div class="container">
    <h2>Edit User: <?php echo htmlspecialchars($user['email']); ?></h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="user_type" class="form-label">User Type</label>
            <select class="form-select" id="user_type" name="user_type" required>
                <option value="client" <?php echo ($user['user_type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                <option value="freelancer" <?php echo ($user['user_type'] == 'freelancer') ? 'selected' : ''; ?>>Freelancer</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="company_name" class="form-label">Company Name</label>
            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo ($user['is_admin'] == 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_admin">Is Admin</label>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'footer.php'; ?>