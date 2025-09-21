<?php 
include 'includes/header.php'; 

$error_message = '';
$error_code = ''; // Initialize $error_code
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        $error_message = 'An account with this email address already exists.';
    } elseif ($error_code == 2) {
        $error_message = 'An unexpected error occurred. Please try again.';
    }
} elseif ($error_code == 3) {
    $error_message = 'Please fill in all fields.';
} elseif ($error_code == 4) {
    $error_message = 'Passwords do not match.';
} elseif ($error_code == 'empty_full_name') {
    $error_message = 'Please enter your full name.';
} elseif ($error_code == 'file_upload_failed') {
    $error_message = 'One or more files failed to upload.';
} elseif ($error_code == 'file_size_exceeded') {
    $error_message = 'One or more files exceeded the maximum size limit (5MB).';
} elseif ($error_code == 'invalid_file_type') {
    $error_message = 'One or more files have an invalid type. Only JPG, JPEG, PNG, GIF are allowed.';
} elseif ($error_code == 'file_upload_error') {
    $error_message = 'An error occurred during file upload.';
}


?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card mt-5 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Create Account</h2>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="actions/signup_action.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_type" class="form-label">I am a:</label>
                            <select class="form-select" id="user_type" name="user_type">
                                <option value="freelancer">Freelancer</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="profile_photo_1" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo_1" name="profile_photos[]" accept="image/*">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Sign Up</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small>Already have an account? <a href="login.php">Log in here</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>