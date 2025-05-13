<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if(!$id || !is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    header("Location: index.php");
    exit;
}

// Handle comment submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    $name = sanitize($_POST['name']);
    $comment = sanitize($_POST['comment']);
    
    if($name && $comment) {
        $status = isset($_SESSION['admin_id']) ? 'approved' : 'pending';
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, name, comment, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $name, $comment, $status]);
        
        $_SESSION['comment_message'] = $status === 'approved' ? 
            "Your comment has been posted!" : 
            "Your comment is awaiting moderation. Thank you!";
        header("Location: post.php?id=$id");
        exit;
    }
}

// Fetch approved comments
$comments = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
$comments->execute([$id]);
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= sanitize($post['title']) ?> | Simple Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .post-content {
      line-height: 1.8;
      font-size: 1.1rem;
    }
    
    .comment {
      border-left: 3px solid #3498db;
      padding-left: 15px;
      margin-bottom: 20px;
    }
    
    .comment-meta {
      font-size: 0.9rem;
      color: #6c757d;
    }
    
    .comment-form {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
    }
    
    .alert-message {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      animation: fadeInOut 3s ease-in-out;
    }
    
    @keyframes fadeInOut {
      0% { opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { opacity: 0; }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>
  
  <div class="container my-5">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <!-- Post Content -->
        <article>
          <h1 class="mb-3"><?= sanitize($post['title']) ?></h1>
          <p class="text-muted mb-4">
            <i class="far fa-calendar-alt me-2"></i>
            Posted on <?= date('F j, Y', strtotime($post['created_at'])) ?>
          </p>
          <div class="post-content mb-5">
            <?= nl2br(sanitize($post['content'])) ?>
          </div>
        </article>
        
        <hr class="my-5">
        
        <!-- Comments Section -->
        <section class="mb-5">
          <h3 class="mb-4">
            <i class="far fa-comments me-2"></i>
            Comments (<?= $comments->rowCount() ?>)
          </h3>
          
          <?php if(isset($_SESSION['comment_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $_SESSION['comment_message'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['comment_message']); ?>
          <?php endif; ?>
          
          <?php if($comments->rowCount() > 0): ?>
            <?php foreach($comments as $comment): ?>
              <div class="comment">
                <h5 class="mb-1"><?= sanitize($comment['name']) ?></h5>
                <p class="comment-meta mb-2">
                  <i class="far fa-clock me-1"></i>
                  <?= date('M j, Y \a\t g:i a', strtotime($comment['created_at'])) ?>
                </p>
                <p><?= nl2br(sanitize($comment['comment'])) ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="alert alert-info">No comments yet. Be the first to comment!</div>
          <?php endif; ?>
        </section>
        
        <!-- Comment Form -->
        <section class="comment-form">
          <h4 class="mb-4">Leave a Comment</h4>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="comment" class="form-label">Comment</label>
              <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
            </div>
            <button type="submit" name="submit_comment" class="btn btn-primary">
              <i class="far fa-paper-plane me-1"></i> Submit Comment
            </button>
          </form>
        </section>
      </div>
    </div>
  </div>
  
  <?php include 'footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>