<?php
session_start();
// 1. Connect to the database
require_once 'db.php'; 
require_once 'config/google_config.php';

// Generate CSRF Token for security if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// 2. Handle the Login when the button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: Check CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid security token. Please refresh the page.");
    }

    // SECURITY: Sanitize Inputs (Prevent XSS)
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // VALIDATION: Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } else {
        // SECURITY: Securely check the database (Prevent SQL Injection)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 4. Verify password
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // Login Success! Set standard session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role']; 

            // ==========================================
            // NEW: "REMEMBER ME" COOKIE LOGIC
            // ==========================================
            if (isset($_POST['remember_me'])) {
                // 1. Generate a random, secure token
                $token = bin2hex(random_bytes(32)); 
                
                // 2. Hash it before saving to the database (for security)
                $hashed_token = hash('sha256', $token);

                // 3. Save the hashed token in the database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$hashed_token, $user['id']]);

                // 4. Set the Cookie in the user's browser (Valid for 30 days)
                // Format: user_id : token
                $cookie_value = $user['id'] . ':' . $token;
                setcookie('jilomaac_remember', $cookie_value, time() + (86400 * 30), "/", "", true, true);
            }
            // ==========================================

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: products.php");
            }
            exit;
        } else {
            $error = "Invalid email or password."; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jilomaac Sign In</title>
    <link rel="stylesheet" href="assets/css/styled.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet" />
  </head>
  <body>
    <div class="varent">
      <div class="sidebar">
        <div class="logo-stack">
          <section class="brand-text">JIL</section>
          <div class="logo-icon">
            <img src="assets/images/Adobe Express - file.png" alt="Logo" />
          </div>
          <section class="brand-text">MAAC</section>
        </div>
      </div>

      <div class="main-content">
        <div class="form-section">
          
          <form class="form_container" method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="title_container">
              <p class="title">Welcome Back</p>
              <span class="subtitle">Enter your details to access your account.</span>
              
              <?php if($error): ?>
                <div style="color: #ff4444; font-size: 0.9rem; margin-top: 10px; background: #3d1a1a; border: 1px solid #ff4444; padding: 10px; border-radius: 4px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
              <?php endif; ?>
            </div>

            <br />

            <div class="input_container">
              <label class="input_label" for="email_field">Email</label>
              <svg fill="none" viewBox="0 0 24 24" height="24" width="24" class="icon">
                <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" stroke="#645bff" d="M7 8.5L9.94202 10.2394C11.6572 11.2535 12.3428 11.2535 14.058 10.2394L17 8.5"></path>
                <path stroke-linejoin="round" stroke-width="1.5" stroke="#645bff" d="M2.01577 13.4756C2.08114 16.5412 2.11383 18.0739 3.24496 19.2094C4.37608 20.3448 5.95033 20.3843 9.09883 20.4634C11.0393 20.5122 12.9607 20.5122 14.9012 20.4634C18.0497 20.3843 19.6239 20.3448 20.7551 19.2094C21.8862 18.0739 21.9189 16.5412 21.9842 13.4756C22.0053 12.4899 22.0053 11.5101 21.9842 10.5244C21.9189 7.45886 21.8862 5.92609 20.7551 4.79066C19.6239 3.65523 18.0497 3.61568 14.9012 3.53657C12.9607 3.48781 11.0393 3.48781 9.09882 3.53656C5.95033 3.61566 4.37608 3.65521 3.24495 4.79065C2.11382 5.92608 2.08114 7.45885 2.01576 10.5244C1.99474 11.5101 1.99475 12.4899 2.01577 13.4756Z"></path>
              </svg>
              <input placeholder="name@mail.com" name="email" type="email" class="input_field" id="email_field" required />
            </div>

            <div class="input_container">
              <label class="input_label" for="password_field">Password</label>
              <svg fill="none" viewBox="0 0 24 24" height="24" width="24" class="icon">
                <path stroke-linecap="round" stroke-width="1.5" stroke="#645bff" d="M18 11.0041C17.4166 9.91704 16.273 9.15775 14.9519 9.0993C13.477 9.03404 11.9788 9 10.329 9C8.67911 9 7.18091 9.03404 5.70604 9.0993C3.95328 9.17685 2.51295 10.4881 2.27882 12.1618C2.12602 13.2541 2 14.3734 2 15.5134C2 16.6534 2.12602 17.7727 2.27882 18.865C2.51295 20.5387 3.95328 21.8499 5.70604 21.9275C6.42013 21.9591 7.26041 21.9834 8 22"></path>
                <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" stroke="#645bff" d="M6 9V6.5C6 4.01472 8.01472 2 10.5 2C12.9853 2 15 4.01472 15 6.5V9"></path>
              </svg>
            <input placeholder="••••••••" name="password" type="password" class="input_field" id="password_field" required />

              <svg id="togglePassword" class="eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="cursor: pointer;">
                <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>

            <div class="remember_container">
              <input type="checkbox" id="remember_me" name="remember_me" />
              <label for="remember_me">Remember me</label>
            </div>

            <button title="Sign In" type="submit" class="sign-in_btn">
              <span>Sign In</span>
            </button>

            <div class="separator">
              <hr class="line" />
              <span>Or</span>
              <hr class="line" />
            </div>

            <a href="<?php echo $google_login_url; ?>" style="text-decoration: none;">
                <button title="Sign In with Google" type="button" class="social_btn">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="G" width="20" style="margin-right:10px;">
                    <span>Sign In with Google</span>
                </button>
            </a>

            <p class="signup-footer">
              Don't have an account? <a href="signup.php">Sign up</a>
            </p>
          </form>
        </div>

        <div class="agent-section">
          <div class="agent-wrapper">
            <model-viewer
              src="assets/images/final2-v1.glb"
              alt="Jiloomac Support Agent"
              autoplay
              camera-controls
              disable-zoom
              shadow-intensity="1"
              bounds="tight"
              camera-orbit="0deg 75deg 105%"
              min-camera-orbit="auto auto 105%"
              max-camera-orbit="auto auto 105%"
              field-of-view="30deg"
            >
            </model-viewer>
          </div>
        </div>
      </div>
    </div>
    
    <script src="assets/js/script.js"></script>
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>
    
    <script>
      const togglePassword = document.querySelector('#togglePassword');
      const password = document.querySelector('#password_field');

      togglePassword.addEventListener('click', function (e) {
          // toggle the type attribute
          const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
          password.setAttribute('type', type);
          
          // Change icon color when visible
          this.style.stroke = type === 'text' ? '#645bff' : '#9ca3af';
      });
    </script>
  </body>
</html>