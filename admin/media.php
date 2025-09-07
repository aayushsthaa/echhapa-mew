<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle file uploads
if ($_POST && isset($_FILES['media_file'])) {
    $uploadDir = '../uploads/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['media_file'];
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'zip'];
    
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO media (title, file_name, file_path, file_type, file_size, alt_text, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $title = sanitize($_POST['title'] ?? $file['name']);
            $altText = sanitize($_POST['alt_text'] ?? '');
            
            if ($stmt->execute([$title, $fileName, 'uploads/' . $fileName, $fileType, $file['size'], $altText, $_SESSION['user_id']])) {
                $success = 'Media uploaded successfully';
            } else {
                $error = 'Failed to save media to database';
            }
        } else {
            $error = 'Failed to upload file';
        }
    } else {
        $error = 'Invalid file type';
    }
}

// Handle media deletion
if (isset($_POST['delete_media'])) {
    $mediaId = intval($_POST['media_id']);
    
    // Get file path before deletion
    $stmt = $conn->prepare("SELECT file_path FROM media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($media) {
        // Delete file from filesystem
        $filePath = '../' . $media['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM media WHERE id = ?");
        if ($deleteStmt->execute([$mediaId])) {
            $success = 'Media deleted successfully';
        } else {
            $error = 'Failed to delete media';
        }
    }
}

// Get all media files
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$media_stmt = $conn->prepare("SELECT m.*, u.first_name, u.last_name FROM media m LEFT JOIN users u ON m.uploaded_by = u.id ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
$media_stmt->execute([$limit, $offset]);
$media_files = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$count_stmt = $conn->query("SELECT COUNT(*) FROM media");
$total_media = $count_stmt->fetchColumn();
$total_pages = ceil($total_media / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .media-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .media-preview {
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .media-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .media-info {
            padding: 15px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background: #e3f2fd;
        }
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
                    <a href="article-create.php"><i class="fas fa-plus-circle"></i> New Article</a>
                </li>
                <li class="menu-item">
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="menu-item active">
                    <a href="media.php"><i class="fas fa-images"></i> Media Library</a>
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
                    <h5 class="mb-0">Media Library</h5>
                </div>
                <div class="topbar-right">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload"></i> Upload Media
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

                    <!-- Media Grid -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($media_files)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                    <h5>No media files found</h5>
                                    <p class="text-muted">Upload your first media file to get started</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        <i class="fas fa-upload"></i> Upload Media
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="media-grid">
                                    <?php foreach ($media_files as $media): ?>
                                    <div class="media-item">
                                        <div class="media-preview">
                                            <?php if (in_array($media['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                <img src="../<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($media['alt_text']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-file fa-3x text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="media-info">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($media['title']); ?></h6>
                                            <small class="text-muted d-block mb-2">
                                                <?php echo strtoupper($media['file_type']); ?> • 
                                                <?php echo formatFileSize($media['file_size']); ?>
                                            </small>
                                            <small class="text-muted d-block mb-2">
                                                By <?php echo htmlspecialchars($media['first_name'] . ' ' . $media['last_name']); ?>
                                            </small>
                                            <div class="btn-group btn-group-sm w-100">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="copyToClipboard('../<?php echo htmlspecialchars($media['file_path']); ?>')"
                                                        title="Copy URL">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info"
                                                        onclick="viewMedia('<?php echo htmlspecialchars($media['file_path']); ?>', '<?php echo htmlspecialchars($media['title']); ?>')"
                                                        title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this media?')">
                                                    <input type="hidden" name="media_id" value="<?php echo $media['id']; ?>">
                                                    <button type="submit" name="delete_media" class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Media pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>">Previous</a>
                                        </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next</a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Drop files here or click to browse</h5>
                            <p class="text-muted">Supported: JPG, PNG, GIF, WebP, PDF, DOC, DOCX, ZIP</p>
                            <input type="file" name="media_file" id="fileInput" style="display: none;" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="title">
                        </div>
                        
                        <div class="mb-3">
                            <label for="alt_text" class="form-label">Alt Text (for images)</label>
                            <input type="text" class="form-control" name="alt_text" id="alt_text">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Upload area functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateFileDisplay();
        });
        
        fileInput.addEventListener('change', updateFileDisplay);
        
        function updateFileDisplay() {
            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                uploadArea.innerHTML = `
                    <i class="fas fa-file fa-2x text-success mb-2"></i>
                    <h6>${fileName}</h6>
                    <p class="text-muted">Ready to upload</p>
                `;
                
                // Auto-fill title if empty
                if (!document.getElementById('title').value) {
                    document.getElementById('title').value = fileName.replace(/\.[^/.]+$/, "");
                }
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a toast or alert
                alert('URL copied to clipboard!');
            });
        }
        
        function viewMedia(filePath, title) {
            window.open('../' + filePath, '_blank');
        }
    </script>
</body>
</html>