<?php
session_start();
require_once 'db.php';
require_once 'config/google_config.php'; // This loads the $client object

if (isset($_GET['code'])) {
    // 1. Exchange the code for a Token using the Library
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if(!isset($token['error'])){
        $client->setAccessToken($token['access_token']);

        // 2. Get User Profile
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;
        $picture = $google_account_info->picture;

        // 3. Check Database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // USER EXISTS: Login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Update Google ID if missing
            if(empty($user['google_id'])){
                $upd = $pdo->prepare("UPDATE users SET google_id = ?, profile_pic = ? WHERE id = ?");
                $upd->execute([$google_id, $picture, $user['id']]);
            }

        } else {
            // NEW USER: Create Account
            $stmt = $pdo->prepare("INSERT INTO users (name, email, google_id, profile_pic, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $google_id, $picture]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }

        header("Location: index.php");
        exit;
    }
}

// Fallback if something failed
header("Location: login.php");
exit;
?>