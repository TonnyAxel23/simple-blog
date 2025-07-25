<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get post statistics
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pendingComments = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();

// Pagination for posts
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalPages = ceil($totalPosts / $perPage);
$stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Simple Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .dashboard-card {
      border-radius: 10px;
      transition: transform 0.3s;
      color: white;
      margin-bottom: 20px;
    }
    
    .dashboard-card:hover {
      transform: translateY(-5px);
    }
    
    .posts-card {
      background: linear-gradient(135deg, #3498db, #2980b9);
    }
    
    .comments-card {
      background: linear-gradient(135deg, #2ecc71, #27ae60);
    }
    
    .pending-card {
      background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    
    .stat-icon {
      font-size: 2.5rem;
      opacity: 0.3;
    }
    
    .action-buttons .btn {
      margin-right: 5px;
    }
    
    .table-responsive {
      border-radius: 8px;
      overflow: hidden;
    }
  </style>
</head>
<body>
  <?php include 'admin-header.php'; ?>
  
  <div class="container py-5">
    <h1 class="mb-4">Admin Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="dashboard-card posts-card p-4 shadow">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">Total Posts</h5>
              <h2 class="mb-0"><?= $totalPosts ?></h2>
            </div>
            <i class="fas fa-newspaper stat-icon"></i>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="dashboard-card comments-card p-4 shadow">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">Total Comments</h5>
              <h2 class="mb-0"><?= $totalComments ?></h2>
            </div>
            <i class="fas fa-comments stat-icon"></i>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="dashboard-card pending-card p-4 shadow">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">Pending Comments</h5>
              <h2 class="mb-0"><?= $pendingComments ?></h2>
              <?php if($pendingComments > 0): ?>
                <a href="comments.php?filter=pending" class="text-white small">Review now</a>
              <?php endif; ?>
            </div>
            <i class="fas fa-clock stat-icon"></i>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Posts Table -->
    <div class="card shadow mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Posts</h5>
        <a href="new-post.php" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i> New Post
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-dark">
              <tr>
                <th>Title</th>
                <th>Date</th>
                <th>Comments</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($posts as $post): 
                $commentCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
                $commentCount->execute([$post['id']]);
                $count = $commentCount->fetchColumn();
              ?>
                <tr>
                  <td><?= sanitize($post['title']) ?></td>
                  <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                  <td><?= $count ?></td>
                  <td class="action-buttons">
                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-info" title="View">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit-post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete-post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this post?');">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
              <?php if($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <?php if($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <?php include 'footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
