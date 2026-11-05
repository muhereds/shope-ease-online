<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Get dashboard statistics from the database.
$statsSql = "
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
        COALESCE(SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END), 0) AS delivered_orders
    FROM orders
";

$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

// Get the five most recent orders.
$recentSql = "
    SELECT id, customer_name, total, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
";

$recentOrders = $conn->query($recentSql);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase | Admin Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #173b6c;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 28px;
        }

        h1 {
            margin: 0 0 8px;
        }

        .subtitle {
            color: #64748b;
            margin: 0;
        }

        .nav a {
            display: inline-block;
            padding: 11px 15px;
            margin: 4px;
            border-radius: 7px;
            background: #173b6c;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .nav a:hover {
            background: #24558f;
        }

.nav .logout-btn {
    display: inline-block;
    padding: 11px 15px;
    margin: 4px;
    border: none;
    border-radius: 7px;
    background: #dc2626;
    color: white;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
}

.nav .logout-btn:hover {
    background: #b91c1c;
}

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #173b6c12;
            border-top: 4px solid #2563eb;
        }

        .card.sales {
            border-top-color: #16a34a;
        }

        .card.pending {
            border-top-color: #d97706;
        }

        .card.delivered {
            border-top-color: #0d9488;
        }

        .card h3 {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 15px;
        }

        .number {
            font-size: 27px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .section {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #173b6c12;
            overflow-x: auto;
        }

        .section h2 {
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #173b6c;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-size: 12px;
            font-weight: bold;
        }

        .status.processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status.delivered {
            background: #dcfce7;
            color: #166534;
        }

        .view-all {
            display: inline-block;
            margin-top: 18px;
            color: #173b6c;
            font-weight: bold;
        }

        .empty {
            padding: 20px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 500px) {
            body {
                padding: 14px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <header>
        <div>
            <h1>ShopEase Dashboard</h1>
            <p class="subtitle">Monitor your store and customer orders.</p>
        </div>

        
<nav class="nav">
    <a href="../index.php">Visit Store</a>
    <a href="orders.php">Manage Orders</a>

    <form action="logout.php" method="POST" style="display:inline;">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</nav>

    </header>

    <div class="cards">
        <div class="card">
            <h3>Total Orders</h3>
            <div class="number">
                <?= number_format((int) $stats['total_orders']) ?>
            </div>
        </div>

        <div class="card sales">
            <h3>Total Sales (ETB)</h3>
            <div class="number">
                <?= number_format((float) $stats['total_sales'], 2) ?>
            </div>
        </div>

        <div class="card pending">
            <h3>Pending Orders</h3>
            <div class="number">
                <?= number_format((int) $stats['pending_orders']) ?>
            </div>
        </div>

        <div class="card delivered">
            <h3>Delivered Orders</h3>
            <div class="number">
                <?= number_format((int) $stats['delivered_orders']) ?>
            </div>
        </div>
    </div>

    <section class="section">
        <h2>Recent Orders</h2>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Order Date</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($recentOrders->num_rows > 0): ?>
                <?php while ($order = $recentOrders->fetch_assoc()): ?>
                    <?php
                    $status = $order['status'] ?? 'Pending';
                    $statusClass = strtolower($status);
                    ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td>ETB <?= number_format((float) $order['total'], 2) ?></td>
                        <td>
                            <span class="status <?= e($statusClass) ?>">
                                <?= e($status) ?>
                            </span>
                        </td>
                        <td><?= e($order['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">
                        No orders have been placed yet.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <a class="view-all" href="orders.php">View all orders →</a>
    </section>

</div>
</body>
</html>
