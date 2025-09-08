<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_user':
            $username = sanitize($_POST['username'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $first_name = sanitize($_POST['first_name'] ?? '');
            $last_name = sanitize($_POST['last_name'] ?? '');
            $role = sanitize($_POST['role'] ?? 'author');
            $status = sanitize($_POST['status'] ?? 'active');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Username, email, and password are required';
            } else {
                // Check if username or email exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check_stmt->execute([$username, $email]);
                
                if ($check_stmt->rowCount() > 0) {
                    $error = 'Username or email already exists';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $role, $status])) {
                        $success = 'User created successfully';
                    } else {
                        $error = 'Failed to create user';
                    }
                }
            }
            break;
            
        case 'update_user_status':
            $user_id = intval($_POST['user_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'active');
            
            if ($user_id > 0 && $user_id != $_SESSION['user_id']) { // Don't allow changing own status
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $user_id])) {
                    $success = 'User status updated';
                } else {
                    $error = 'Failed to update user status';
                }
            } else {
                $error = 'Cannot update your own status';
            }
            break;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id > 0 && $user_id != $_SESSION['user_id']) { // Don't allow deleting own account
                // First update articles to remove author reference
                $update_articles = $conn->prepare("UPDATE articles SET author_id = ? WHERE author_id = ?");
                $update_articles->execute([1, $user_id]); // Assign to user ID 1 (admin)
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $success = 'User deleted successfully';
                } else {
                    $error = 'Failed to delete user';
                }
            } else {
                $error = 'Cannot delete your own account';
            }
            break;
    }
}

// Get all users with article counts
$users_stmt = $conn->query("
    SELECT u.*, 
           COUNT(a.id) as article_count,
           MAX(a.created_at) as last_article
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
    <style>
        .role-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .role-admin { background: #dc3545; }
        .role-editor { background: #fd7e14; }
        .role-author { background: #198754; }
        .role-contributor { background: #0dcaf0; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-newspaper"></i> News Admin</h4>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="menu-header">Content Management</li>
                <li class="menu-item">
                    <a href="articles.php"><i class="fas fa-file-alt"></i> Articles</a>
                </li>
                <li class="menu-item">
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="menu-item">
                </li>
                <li class="menu-item">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Advertisements</a>
                </li>
                <li class="menu-header">Site Settings</li>
                <li class="menu-item">
                    <a href="homepage-layout.php"><i class="fas fa-layout"></i> Homepage Layout</a>
                </li>
                <li class="menu-item">
                    <a href="seo-settings.php"><i class="fas fa-search"></i> SEO Settings</a>
                </li>
                <li class="menu-item active">
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                </li>
                <li class="menu-item">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0">User Management</h5>
                </div>
                <div class="topbar-right">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>
            </div>

            <div class="content-area">
                <div class="container-fluid">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Users List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No users found</h5>
                                    <p class="text-muted">Add your first user to get started</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                        <i class="fas fa-plus"></i> Add User
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Articles</th>
                                                <th>Status</th>
                                                <th>Last Activity</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-3">
                                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                            <br><small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge role-<?php echo $user['role']; ?> role-badge">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold"><?php echo number_format($user['article_count']); ?></span>
                                                    <?php if ($user['last_article']): ?>
                                                        <br><small class="text-muted">Last: <?php echo date('M j, Y', strtotime($user['last_article'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" onchange="this.submit()">
                                                        <input type="hidden" name="action" value="update_user_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm" 
                                                                <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($user['last_login'] ?? $user['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary" 
                                                                    onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this user? Their articles will be reassigned to admin.')">
                                                                <input type="hidden" name="action" value="delete_user">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                            <small class="text-muted">Minimum 8 characters recommended</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" name="role" id="role">
                                        <option value="author">Author</option>
                                        <option value="editor">Editor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            alert('Edit user functionality will be implemented');
        }
    </script>
</body>
</html>