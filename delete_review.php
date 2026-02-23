<?php
session_start();
require_once 'db.php';

if (isset($_POST['delete_review'])) {
    
    // 1. Security Check: User must be logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $review_id = $_POST['review_id'];
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];

    // 2. Ownership Check: Ensure the logged-in user OWNS this review
    // We try to delete ONLY if the ID matches AND user_id matches
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$review_id, $user_id])) {
        // Success
        header("Location: product_details.php?id=$product_id&msg=Review deleted");
    } else {
        // Failure (or hacking attempt)
        header("Location: product_details.php?id=$product_id&error=Could not delete review");
    }
    exit;
}

// Redirect if accessed directly
header("Location: index.php");
exit;
?>