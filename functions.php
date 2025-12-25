<?php
require_once 'database.php';

function generateOTP($length = 6) {
    $characters = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $otp;
}

function formatPhoneNumber($phone) {
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 0, replace with +62
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    return $phone;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    $phone = formatPhoneNumber($phone);
    return preg_match('/^62[0-9]{9,12}$/', $phone);
}

function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function getUserById($id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getTransactionHistory($user_id) {
    global $conn;
    $sql = "SELECT t.*, 
                   u1.username as sender_name, 
                   u2.username as receiver_name 
            FROM transactions t
            LEFT JOIN users u1 ON t.sender_id = u1.id
            LEFT JOIN users u2 ON t.receiver_id = u2.id
            WHERE t.sender_id = ? OR t.receiver_id = ?
            ORDER BY t.created_at DESC
            LIMIT 50";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
?>