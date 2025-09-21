<?php
require_once 'includes/db.php';

// Set mysqli to throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h3>Attempting to fix database...</h3>";

try {
    // SQL to add the is_admin column to the users table
    $sql = "ALTER TABLE `users` ADD `is_admin` BOOLEAN NOT NULL DEFAULT FALSE AFTER `profile_picture`;";
    $conn->query($sql);
    echo "<p style='color:green;'>Success! The 'is_admin' column was added to the 'users' table.</p>";
    echo "<p>You can now delete this file (fix_database.php).</p>";
} catch (mysqli_sql_exception $e) {
    // Check if the column already exists (error code 1060)
    if ($e->getCode() == 1060) {
        echo "<p style='color:orange;'>Warning: The 'is_admin' column already exists.</p>";
        echo "<p>Your database appears to be up to date. You can now safely delete this file (fix_database.php).</p>";
    } else {
        // For other errors, display the error message
        echo "<p style='color:red;'>Error altering table: " . $e->getMessage() . "</p>";
    }
}

$conn->close();
?>