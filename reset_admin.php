<?php
require_once 'db.php';

// 1. The Credentials we want
$email = 'admin@jiloomac.com';
$raw_password = '123456';
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

try {
    // 2. Delete old admin if exists (to avoid duplicates)
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$email]);

    // 3. Insert the fresh Admin User
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Super Admin', $email, $hashed_password, 'admin']);

    echo "<h1 style='color: green;'>✅ Success! Admin Reset.</h1>";
    echo "<p>User: <strong>$email</strong></p>";
    echo "<p>Pass: <strong>$raw_password</strong></p>";
    echo "<br><a href='login.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "<h1 style='color: red;'>❌ Error</h1>";
    echo "Database Error: " . $e->getMessage();
}
?>