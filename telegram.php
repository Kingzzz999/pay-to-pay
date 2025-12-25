<?php
function sendTelegramMessage($message) {
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = TELEGRAM_CHAT_ID;
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function sendOTPToTelegram($username, $otp) {
    $message = "📱 <b>Pay to Pay - OTP Verification</b>\n\n";
    $message .= "👤 User: " . htmlspecialchars($username) . "\n";
    $message .= "🔑 OTP Code: <code>$otp</code>\n";
    $message .= "⏰ Expires in: 10 minutes\n";
    $message .= "📅 Time: " . date('Y-m-d H:i:s');
    
    return sendTelegramMessage($message);
}
?>