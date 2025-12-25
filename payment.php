<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver = sanitizeInput($_POST['receiver']);
    $amount = floatval($_POST['amount']);
    $description = sanitizeInput($_POST['description']);
    
    if ($amount <= 0) {
        $error = "Amount must be greater than 0";
    } elseif ($amount > $user['balance']) {
        $error = "Insufficient balance";
    } else {
        // Find receiver
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) AND is_verified = 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $receiver, $receiver, $receiver);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $receiver_data = mysqli_fetch_assoc($result);
        
        if (!$receiver_data) {
            $error = "Receiver not found or not verified";
        } elseif ($receiver_data['id'] == $user_id) {
            $error = "Cannot send payment to yourself";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Create transaction
                $sql = "INSERT INTO transactions (sender_id, receiver_id, amount, description) 
                        VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iids", $user_id, $receiver_data['id'], $amount, $description);
                mysqli_stmt_execute($stmt);
                
                // Update sender balance
                $sql = "UPDATE users SET balance = balance - ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "di", $amount, $user_id);
                mysqli_stmt_execute($stmt);
                
                // Update receiver balance
                $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "di", $amount, $receiver_data['id']);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                
                $success = "Payment of Rp " . number_format($amount, 2) . " sent successfully to " . $receiver_data['username'];
                
                // Refresh user data
                $user = getUserById($user_id);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Payment failed: " . $e->getMessage();
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
    <title>Send Payment - Pay to Pay</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">PAY<span>to</span>PAY</a>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="transaction_history.php">History</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="form-container">
            <h2 class="form-title">Send Payment</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card mb-20">
                <div class="text-center">
                    <div class="balance-label">Your Balance</div>
                    <div class="balance-amount">Rp <?php echo number_format($user['balance'], 2); ?></div>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="receiver">Send to (Username/Email/Phone) *</label>
                    <input type="text" id="receiver" name="receiver" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (Rp) *</label>
                    <input type="number" id="amount" name="amount" class="form-control" 
                           step="0.01" min="0.01" max="<?php echo $user['balance']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn">Send Payment</button>
            </form>
        </div>
    </div>
</body>
</html>