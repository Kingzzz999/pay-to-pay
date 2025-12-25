<?php
require_once 'database.php';
require_once 'functions.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: register.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = sanitizeInput($_POST['otp']);
    $username = $_SESSION['temp_user']['username'];
    
    $sql = "SELECT * FROM users WHERE username = ? AND otp_code = ? AND otp_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $otp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        // Update user as verified
        $update_sql = "UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
        mysqli_stmt_execute($update_stmt);
        
        // Auto login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        unset($_SESSION['temp_user']);
        
        $success = "Account verified successfully! Redirecting...";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 2000);
              </script>";
    } else {
        $error = "Invalid or expired OTP";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Pay to Pay</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">PAY<span>to</span>PAY</a>
        </header>
        
        <div class="form-container">
            <h2 class="form-title">Verify OTP</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                OTP has been sent to Telegram bot. Please check your bot messages.
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp">Enter 6-digit OTP</label>
                    <input type="text" id="otp" name="otp" class="form-control" 
                           maxlength="6" pattern="[0-9]{6}" required>
                </div>
                
                <button type="submit" class="btn">Verify OTP</button>
            </form>
            
            <div class="text-center mt-20">
                <a href="register.php" class="btn btn-outline">Back to Register</a>
            </div>
        </div>
    </div>
</body>
</html>