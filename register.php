<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'config/telegram.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || (empty($email) && empty($phone)) || empty($password)) {
        $error = "Please fill all required fields";
    } elseif (!empty($email) && !validateEmail($email)) {
        $error = "Invalid email format";
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $error = "Invalid phone number format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username exists
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        $check_phone = formatPhoneNumber($phone);
        mysqli_stmt_bind_param($check_stmt, "sss", $username, $email, $check_phone);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Username, email or phone already exists";
        } else {
            // Generate OTP
            $otp = generateOTP();
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $phone = formatPhoneNumber($phone);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, phone, password, otp_code, otp_expiry) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $phone, $hashed_password, $otp, $otp_expiry);
            
            if (mysqli_stmt_execute($stmt)) {
                // Send OTP to Telegram
                sendOTPToTelegram($username, $otp);
                
                // Store in session for verification
                $_SESSION['temp_user'] = [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone
                ];
                
                header("Location: verify_otp.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pay to Pay</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">PAY<span>to</span>PAY</a>
        </header>
        
        <div class="form-container">
            <h2 class="form-title">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address (or Phone)</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="example@domain.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number (or Email)</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="081234567890">
                    <small class="text-muted">Enter email OR phone number</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            <div class="text-center mt-20">
                <span class="text-muted">Already have an account?</span>
                <a href="login.php" class="text-white">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>