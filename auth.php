<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header("Location: ../login.php");
        exit();
    }
}

function loginUser($username, $password) {
    global $conn;
    
    $username = sanitizeInput($username);
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) AND is_verified = 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = ($user['username'] == 'admin'); // Simple admin check
        
        // Update last login
        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
        mysqli_stmt_execute($update_stmt);
        
        return true;
    }
    
    return false;
}
?>