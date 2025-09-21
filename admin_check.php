<?php
require_once 'includes/db.php';

// --- CONFIGURATION ---
// Put the email and password you are trying to log in with here
$email_to_check = "admin@test.com"; 
$password_to_check = "password123";
// ---------------------


echo "<h2>Admin User Diagnostic Tool</h2>";
echo "<p>Checking for user with email: <strong>" . htmlspecialchars($email_to_check) . "</strong></p>";
echo "<hr>";

// 1. Check if user exists
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("<p style='color:red;'>Error preparing statement: " . $conn->error . "</p>");
}
$stmt->bind_param("s", $email_to_check);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("<p style='color:red;'><strong>FAIL:</strong> No user found with that email address.</p>");
}

echo "<p style='color:green;'><strong>OK:</strong> User found.</p>";
$user = $result->fetch_assoc();

// 2. Check if user is an admin
echo "<p>Checking if user is an admin...</p>";
if ($user['is_admin'] == 1) {
    echo "<p style='color:green;'><strong>OK:</strong> User has the 'is_admin' flag set to 1.</p>";
} else {
    echo "<p style='color:red;'><strong>FAIL:</strong> User is not an admin. The 'is_admin' flag is 0.</p>";
    echo "<p>Please go to phpMyAdmin, find this user in the 'users' table, and change the value of the 'is_admin' column to 1.</p>";
}

// 3. Check if password matches
echo "<p>Checking if password is correct...</p>";
if (password_verify($password_to_check, $user['password'])) {
    echo "<p style='color:green;'><strong>OK:</strong> Password is correct.</p>";
} else {
    echo "<p style='color:red;'><strong>FAIL:</strong> Password does not match.</p>";
    echo "<p>The password you entered in this script ('" . htmlspecialchars($password_to_check) . "') does not match the one in the database. Make sure you are using the correct password.</p>";
}

echo "<hr>";

if ($user['is_admin'] == 1 && password_verify($password_to_check, $user['password'])) {
    echo "<h3><strong style='color:green;'>SUCCESS!</strong></h3>";
    echo "<p>According to this check, your login should work on the admin page.</p>";
    echo "<p>Please try logging in at: <a href='./admin/login.php'>./admin/login.php</a></p>";
} else {
    echo "<h3><strong style='color:red;'>DIAGNOSIS FAILED</strong></h3>";
    echo "<p>One or more checks above failed. Please fix the issues and try again.</p>";
}

$stmt->close();
$conn->close();
?>