<?php
require_once '../includes/db.php';
session_start();

// Redirect if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

// --- Input Validation ---
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($email) || empty($password)) {
    header("Location: ../login.php?error=empty_fields");
    exit();
}

// Enable mysqli exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT id, password, user_type FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // --- Login Success ---
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Handle "Remember Me"
            if ($remember_me) {
                setcookie('remember_email', $email, time() + (86400 * 30), "/"); // 30 days
            } else {
                setcookie('remember_email', '', time() - 3600, "/"); // Clear cookie
            }
            
            // Redirect to the dashboard
            header("Location: ../dashboard.php");
            exit();
        }
    }

    // --- Login Failure ---
    // Clear remember_email cookie if login fails
    setcookie('remember_email', '', time() - 3600, "/");
    // For security, use a generic error message for both user not found and invalid password
    header("Location: ../login.php?error=invalid_credentials");
    exit();

} catch (Exception $e) {
    // In a real app, log the specific error
    error_log("Login Action Error: " . $e->getMessage());
    // Redirect with a generic error
    header("Location: ../login.php?error=db_error");
    exit();
}
?>