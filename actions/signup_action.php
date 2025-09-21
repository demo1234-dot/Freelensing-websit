<?php
require_once '../includes/db.php';

// Redirect if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../signup.php");
    exit();
}

// --- Input Validation ---
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$full_name = $_POST['full_name'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

$errors = [];
if (empty($full_name)) {
    $errors[] = "empty_full_name";
}
if ($email === false) {
    $errors[] = "invalid_email";
}
if (strlen($password) < 8) {
    $errors[] = "password_too_short";
}
if ($password !== $confirm_password) {
    $errors[] = "passwords_do_not_match";
}
if (!in_array($user_type, ['freelancer', 'client'])) {
    $errors[] = "invalid_user_type";
}

// --- File Upload Handling ---
$uploaded_photo_paths = [];
$upload_dir = '../uploads/profile_photos/';

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['profile_photos'])) {
    $total_files = count($_FILES['profile_photos']['name']);
    for ($i = 0; $i < $total_files; $i++) {
        $file_name = $_FILES['profile_photos']['name'][$i];
        $file_tmp_name = $_FILES['profile_photos']['tmp_name'][$i];
        $file_size = $_FILES['profile_photos']['size'][$i];
        $file_error = $_FILES['profile_photos']['error'][$i];
        $file_type = $_FILES['profile_photos']['type'][$i];

        if ($file_error === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size < 5000000) { // Max 5MB
                    $new_file_name = uniqid('', true) . '.' . $file_ext;
                    $file_destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $file_destination)) {
                        $uploaded_photo_paths[] = 'uploads/profile_photos/' . $new_file_name;
                    } else {
                        $errors[] = "file_upload_failed";
                    }
                } else {
                    $errors[] = "file_size_exceeded";
                }
            } else {
                $errors[] = "invalid_file_type";
            }
        } elseif ($file_error !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "file_upload_error";
        }
    }
}

// Ensure we have exactly 5 photo paths, filling with NULL if fewer were uploaded
while (count($uploaded_photo_paths) < 5) {
    $uploaded_photo_paths[] = NULL;
}

if (!empty($errors)) {
    // Redirect back to the signup page with the first error found
    header("Location: ../signup.php?error=" . $errors[0]);
    exit();
}

// --- Input Validation ---
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$full_name = $_POST['full_name'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

$errors = [];
if (empty($full_name)) {
    $errors[] = "empty_full_name";
}
if ($email === false) {
    $errors[] = "invalid_email";
}
if (strlen($password) < 8) {
    $errors[] = "password_too_short";
}
if ($password !== $confirm_password) {
    $errors[] = "passwords_do_not_match";
}
if (!in_array($user_type, ['freelancer', 'client'])) {
    $errors[] = "invalid_user_type";
}

// --- File Upload Handling ---
$uploaded_photo_path = NULL;
$upload_dir = '../uploads/profile_photos/';

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['profile_photos']) && $_FILES['profile_photos']['error'][0] === UPLOAD_ERR_OK) {
    $file_name = $_FILES['profile_photos']['name'][0];
    $file_tmp_name = $_FILES['profile_photos']['tmp_name'][0];
    $file_size = $_FILES['profile_photos']['size'][0];
    $file_error = $_FILES['profile_photos']['error'][0];
    $file_type = $_FILES['profile_photos']['type'][0];

    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
        if ($file_size < 5000000) { // Max 5MB
            $new_file_name = uniqid('', true) . '.' . $file_ext;
            $file_destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $file_destination)) {
                $uploaded_photo_path = 'uploads/profile_photos/' . $new_file_name;
            } else {
                $errors[] = "file_upload_failed";
            }
        } else {
            $errors[] = "file_size_exceeded";
        }
    } else {
        $errors[] = "invalid_file_type";
    }
} elseif (isset($_FILES['profile_photos']) && $_FILES['profile_photos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
    $errors[] = "file_upload_error";
}

if (!empty($errors)) {
    // Redirect back to the signup page with the first error found
    header("Location: ../signup.php?error=" . $errors[0]);
    exit();
}

// --- Database Insertion ---
// Enable mysqli exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (email, full_name, password, user_type, profile_photo_1) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $email, $full_name, $hashed_password, $user_type, $uploaded_photo_path);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();

    // --- Redirect on Success ---
    header("Location: ../login.php?success=signup");
    exit();

} catch (mysqli_sql_exception $e) {
    // Check for duplicate entry error
    if ($e->getCode() == 1062) {
        header("Location: ../signup.php?error=email_taken");
    } else {
        // In a real app, log the specific error
        error_log("Signup Action Error: " . $e->getMessage());
        // Redirect with a generic error
        header("Location: ../signup.php?error=db_error");
    }
    exit();
} catch (Exception $e) {
    error_log("Signup Action Error: " . $e->getMessage());
    header("Location: ../signup.php?error=unknown");
    exit();
}
?>