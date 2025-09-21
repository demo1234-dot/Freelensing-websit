<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../edit_profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$upload_error = '';

// Handle profile picture upload first
$profile_picture_filename = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
    $target_dir = "../uploads/";
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    // Check file size
    if ($_FILES['profile_picture']['size'] > $max_file_size) {
        $upload_error = 'Sorry, your file is too large. Maximum size is 5MB.';
    } else {
        // Verify it's a real image
        $check = getimagesize($_FILES['profile_picture']['tmp_name']);
        if ($check === false) {
            $upload_error = 'File is not a valid image.';
        } else {
            $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
            // Generate a unique filename
            $profile_picture_filename = 'user_' . $user_id . '_' . time() . '.' . $imageFileType;
            $target_file = $target_dir . $profile_picture_filename;

            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $upload_error = 'Sorry, there was an error uploading your file.';
                $profile_picture_filename = null; // Reset on failure
            }
        }
    }
}

// Now, update the text fields
$params = [];
$types = '';
$sql_parts = [];

if ($user_type == 'freelancer') {
    $sql_parts = [
        'first_name = ?', 'last_name = ?', 'bio = ?', 
        'hourly_rate = ?', 'skills = ?', 'portfolio = ?'
    ];
    $types = 'sssdss';
    $params = [
        $_POST['first_name'] ?? '',
        $_POST['last_name'] ?? '',
        $_POST['bio'] ?? '',
        // Ensure hourly_rate is a valid float
        (float)($_POST['hourly_rate'] ?? 0.0),
        $_POST['skills'] ?? '',
        $_POST['portfolio'] ?? ''
    ];
} else { // client
    $sql_parts = ['company_name = ?', 'first_name = ?', 'last_name = ?'];
    $types = 'sss';
    $params = [
        $_POST['company_name'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['last_name'] ?? ''
    ];
}

// Add profile picture to the update if it was uploaded successfully
if ($profile_picture_filename) {
    $sql_parts[] = 'profile_picture = ?';
    $types .= 's';
    $params[] = $profile_picture_filename;
}

if (!empty($sql_parts)) {
    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        // In a real app, you'd log this error
        $upload_error = 'There was an error updating your profile.';
    }
    $stmt->close();
}

$conn->close();

// Redirect back to the profile page with a status message
if ($upload_error) {
    header("Location: ../edit_profile.php?error=" . urlencode($upload_error));
} else {
    header("Location: ../profile.php?success=updated");
}
exit();
?>