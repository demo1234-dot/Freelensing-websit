<?php
require_once '../includes/db.php';
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$remembered_admin_email = $_COOKIE['remember_admin_email'] ?? '';
$remember_admin_checked = isset($_COOKIE['remember_admin_email']) ? 'checked' : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);

        $sql = "SELECT id, password FROM users WHERE email = ? AND is_admin = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                
                // Handle "Remember Me" for admin
                if ($remember_me) {
                    setcookie('remember_admin_email', $email, time() + (86400 * 30), "/"); // 30 days
                } else {
                    setcookie('remember_admin_email', '', time() - 3600, "/"); // Clear cookie
                }

                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid credentials. Please try again.";
                setcookie('remember_admin_email', '', time() - 3600, "/"); // Clear cookie on failed login
            }
        } else {
            $error = "Invalid credentials. Please try again.";
            setcookie('remember_admin_email', '', time() - 3600, "/"); // Clear cookie on failed login
        }
    } else {
        $error = "Email and password are required.";
        setcookie('remember_admin_email', '', time() - 3600, "/"); // Clear cookie if fields are missing
    }
} else {
    // If not a POST request, ensure cookie is cleared if remember_me was not checked
    if (!isset($_COOKIE['remember_admin_email'])) {
        setcookie('remember_admin_email', '', time() - 3600, "/");
    }
}

include 'header.php';
?>

<div class="login-container">
    <div class="card login-card shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Admin Login</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($remembered_admin_email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" <?php echo $remember_admin_checked; ?>>
                    <label class="form-check-label" for="remember_me">Remember me</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>