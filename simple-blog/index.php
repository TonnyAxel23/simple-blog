<?php
session_start();
require 'db.php';

// Sanitize function
function sanitize($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Pagination
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Get total posts count
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Fetch posts for current page using bindValue for LIMIT/OFFSET
$stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simple Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #2c3e50;
      --accent-color: #e74c3c;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
    }
    
    .navbar {
      background-color: var(--secondary-color);
    }
    
    .navbar-brand {
      font-weight: 700;
    }
    
    .post-card {
      transition: transform 0.3s, box-shadow 0.3s;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .post-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .post-title {
      color: var(--secondary-color);
      transition: color 0.3s;
    }
    
    .post-card:hover .post-title {
      color: var(--primary-color);
    }
    
    .read-more {
      color: var(--primary-color);
      font-weight: 500;
    }
    
    .pagination .page-item.active .page-link {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .pagination .page-link {
      color: var(--secondary-color);
    }
    
    footer {
      background-color: var(--secondary-color);
      color: white;
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
      <a class="navbar-brand" href="index.php">Simple Blog</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <?php if(isset($_SESSION['admin_id'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Logout</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="login.php">Admin Login</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mb-5">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <h1 class="text-center mb-4">Welcome to Simple Blog</h1>
        
        <?php if(empty($posts)): ?>
          <div class="alert alert-info">No posts found. Check back later!</div>
        <?php else: ?>
          <?php foreach($posts as $post): ?>
            <div class="card post-card mb-4">
              <div class="card-body">
                <h2 class="card-title post-title">
                  <a href="post.php?id=<?= $post['id'] ?>" class="text-decoration-none">
                    <?= sanitize($post['title']) ?>
                  </a>
                </h2>
                <p class="text-muted mb-3">
                  <i class="far fa-calendar-alt me-2"></i>
                  <?= date('F j, Y', strtotime($post['created_at'])) ?>
                </p>
                <p class="card-text"><?= nl2br(sanitize(substr($post['content'], 0, 250))) ?>...</p>
                <a href="post.php?id=<?= $post['id'] ?>" class="read-more">
                  Read More <i class="fas fa-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
          
          <!-- Pagination -->
          <?php if($totalPages > 1): ?>
            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center">
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
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="py-4 mt-auto">
    <div class="container text-center">
      <p class="mb-0">&copy; <?= date('Y') ?> Simple Blog. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
