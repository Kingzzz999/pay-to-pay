<?php
require_once 'database.php';
require_once 'auth.php';
requireAdmin();

// Get statistics
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE is_verified = 1) as verified_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT SUM(amount) FROM transactions WHERE status = 'completed') as total_volume";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Get recent users
$sql_users = "SELECT * FROM users ORDER BY registration_date DESC LIMIT 10";
$recent_users = mysqli_query($conn, $sql_users);

// Get recent transactions
$sql_trans = "SELECT t.*, u1.username as sender, u2.username as receiver 
              FROM transactions t
              LEFT JOIN users u1 ON t.sender_id = u1.id
              LEFT JOIN users u2 ON t.receiver_id = u2.id
              ORDER BY t.created_at DESC LIMIT 10";
$recent_transactions = mysqli_query($conn, $sql_trans);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pay to Pay</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include 'admin_header.php'; ?>
        
        <div class="dashboard-container">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </aside>
            
            <main class="dashboard-content">
                <h2 class="card-title">Admin Dashboard</h2>
                
                <div class="grid-3">
                    <div class="card">
                        <h3>Total Users</h3>
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    </div>
                    
                    <div class="card">
                        <h3>Verified Users</h3>
                        <div class="stat-number"><?php echo $stats['verified_users']; ?></div>
                    </div>
                    
                    <div class="card">
                        <h3>Total Volume</h3>
                        <div class="stat-number">Rp <?php echo number_format($stats['total_volume'], 2); ?></div>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Recent Users</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email/Phone</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($recent_users)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?: $user['phone']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['registration_date'])); ?></td>
                                <td>
                                    <span class="status-badge">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($user['balance'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Recent Transactions</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($trans = mysqli_fetch_assoc($recent_transactions)): ?>
                            <tr>
                                <td><?php echo date('H:i d/m/Y', strtotime($trans['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($trans['sender']); ?></td>
                                <td><?php echo htmlspecialchars($trans['receiver']); ?></td>
                                <td>Rp <?php echo number_format($trans['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $trans['status']; ?>">
                                        <?php echo ucfirst($trans['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($trans['status'] == 'pending'): ?>
                                        <a href="process_transaction.php?id=<?php echo $trans['id']; ?>&action=approve" class="btn btn-small">Approve</a>
                                        <a href="process_transaction.php?id=<?php echo $trans['id']; ?>&action=reject" class="btn btn-small btn-outline">Reject</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>