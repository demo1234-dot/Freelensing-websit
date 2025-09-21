<?php
require_once 'includes/db.php';

$email = "admin@gmail.com";
$password = "admin";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if the user already exists
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    // User exists, update their password and admin status
    echo "User with email " . htmlspecialchars($email) . " already exists. Updating password and setting as admin...<br>";
    $sql_update = "UPDATE users SET password = ?, is_admin = 1, user_type = 'freelancer' WHERE email = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ss", $hashed_password, $email);
    if ($stmt_update->execute()) {
        echo "User updated successfully!<br>";
    } else {
        echo "Error updating user: " . $stmt_update->error . "<br>";
    }
    $stmt_update->close();
} else {
    // User does not exist, insert new user
    echo "Creating new admin user with email " . htmlspecialchars($email) . "...<br>";
    // Assuming 'freelancer' as a default user_type, as it's NOT NULL
    $sql_insert = "INSERT INTO users (email, password, user_type, is_admin) VALUES (?, ?, 'freelancer', 1)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ss", $email, $hashed_password);
    if ($stmt_insert->execute()) {
        echo "Admin user created successfully!<br>";
    } else {
        echo "Error creating admin user: " . $stmt_insert->error . "<br>";
    }
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();
echo "Script finished. You can now try logging in with admin@gmail.com and password 'admin'.";
?>