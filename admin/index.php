<?php
include 'header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch users
$sql_users = "SELECT id, email, user_type, first_name, last_name, company_name, is_admin FROM users ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);

// Fetch projects
$sql_projects = "SELECT p.id, p.title, p.status, u.email as client_email FROM projects p JOIN users u ON p.client_id = u.id ORDER BY p.created_at DESC";
$result_projects = $conn->query($sql_projects);

?>

<div class="container">
    <h2>Admin Dashboard</h2>

    <h3>Users</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Name</th>
                <th>Is Admin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user = $result_users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type'] == 'freelancer' ? $user['first_name'] . ' ' . $user['last_name'] : $user['company_name']); ?></td>
                    <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                    <td><a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a> | <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <hr>

    <h3>Projects</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Client</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($project = $result_projects->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $project['id']; ?></td>
                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                    <td><?php echo htmlspecialchars($project['client_email']); ?></td>
                    <td><?php echo htmlspecialchars($project['status']); ?></td>
                    <td><a href="view_project.php?id=<?php echo $project['id']; ?>">View</a> | <a href="delete_project.php?id=<?php echo $project['id']; ?>" onclick="return confirm('Are you sure you want to delete this project?');">Delete</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<?php include 'footer.php'; ?>