<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$transactions = getTransactionHistory($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pay to Pay</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">PAY<span>to</span>PAY</a>
            <nav class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="payment.php">Send Payment</a>
                <a href="transaction_history.php">History</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="dashboard-container">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="payment.php">Send Payment</a></li>
                    <li><a href="transaction_history.php">Transaction History</a></li>
                    <li><a href="profile.php">Profile Settings</a></li>
                </ul>
            </aside>
            
            <main class="dashboard-content">
                <div class="balance-display card">
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-amount">Rp <?php echo number_format($user['balance'], 2); ?></div>
                    <div class="text-center mt-20">
                        <a href="payment.php" class="btn btn-small">Send Payment</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Transactions</h3>
                        <a href="transaction_history.php" class="btn btn-outline btn-small">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($transaction = mysqli_fetch_assoc($transactions)): 
                                    $is_sender = $transaction['sender_id'] == $user_id;
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?></td>
                                    <td><?php echo $is_sender ? 'Sent' : 'Received'; ?></td>
                                    <td>
                                        <?php if($is_sender): ?>
                                            To: <?php echo htmlspecialchars($transaction['receiver_name']); ?>
                                        <?php else: ?>
                                            From: <?php echo htmlspecialchars($transaction['sender_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $is_sender ? 'text-error' : 'text-success'; ?>">
                                        <?php echo $is_sender ? '-' : '+'; ?>Rp <?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>