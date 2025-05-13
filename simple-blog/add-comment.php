<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $name = trim($_POST['name']);
    $comment = trim($_POST['comment']);

    if ($name && $comment && $post_id) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, name, comment) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $name, $comment]);
    }
    header("Location: post.php?id=" . $post_id);
    exit;
}
?>
