<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = 'success';

$allowedStatuses = ['Pending', 'Processing', 'Delivered'];

// Handle status updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $_SESSION['flash_message'] = 'Invalid request. Please refresh the page.';
        $_SESSION['flash_type'] = 'error';
    } elseif (
        !$orderId ||
        !in_array($newStatus, $allowedStatuses, true)
    ) {
        $_SESSION['flash_message'] = 'Invalid order ID or status.';
        $_SESSION['flash_type'] = 'error';
    } else {
        try {
            $stmt = $conn->prepare(
                'UPDATE orders SET status = ? WHERE id = ?'
            );

            $stmt->bind_param('si', $newStatus, $orderId);
            $stmt->execute();
            $stmt->close();

            // Verify that the order exists and check its saved status.
            $check = $conn->prepare(
                'SELECT status FROM orders WHERE id = ?'
            );

            $check->bind_param('i', $orderId);
            $check->execute();
            $existingOrder = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$existingOrder) {
                $_SESSION['flash_message'] = 'Order not found.';
                $_SESSION['flash_type'] = 'error';
            } elseif ($existingOrder['status'] === $newStatus) {
                $_SESSION['flash_message'] =
                    "Order #{$orderId} status is now {$newStatus}.";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] =
                    'The order status could not be updated.';
                $_SESSION['flash_type'] = 'error';
            }
        } catch (mysqli_sql_exception $e) {
            error_log('ShopEase status update failed: ' . $e->getMessage());
            $_SESSION['flash_message'] =
                'Could not update the order. Please try again.';
            $_SESSION['flash_type'] = 'error';
        }
    }

    // Prevent the form from being submitted again on refresh.
    header('Location: orders.php');
    exit;
}

// Display the message after redirecting.
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';

    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Load orders.
$sql = "SELECT id, customer_name, phone, address,
               products, total, status, created_at
        FROM orders
        ORDER BY created_at DESC";

$result = $conn->query($sql);
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShopEase | Admin Orders</title>

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
            max-width: 1250px;
            margin: auto;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        .table-wrapper {
            background: white;
            padding: 18px;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px #173b6c12;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        th {
            background: #173b6c;
            color: white;
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 18px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        select, button {
            padding: 9px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            margin-bottom: 6px;
        }

        button {
            display: block;
            background: #173b6c;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #24558f;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #173b6c;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }

        .status-form {
            min-width: 125px;
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="../index.php">&larr; Back to ShopEase</a>

    <h1>Customer Orders</h1>
    <p class="subtitle">
        View customer orders and update their status.
    </p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'error' ? 'error' : '' ?>"
             role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Products</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Update Status</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>

                        <td>
                            <?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <?= nl2br(htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8')) ?>
                        </td>

                        <td>
                            <?= nl2br(htmlspecialchars($order['products'], ENT_QUOTES, 'UTF-8')) ?>
                        </td>

                        <td>
                            ETB <?= number_format((float) $order['total'], 2) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($order['status'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <form method="post" class="status-form">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= (int) $order['id'] ?>">

                                <label for="status-<?= (int) $order['id'] ?>">
                                    Status
                                </label>

                                <select
                                    id="status-<?= (int) $order['id'] ?>"
                                    name="status"
                                    required>
                                    <?php foreach ($allowedStatuses as $status): ?>
                                        <option
                                            value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= ($order['status'] ?? 'Pending') === $status ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="empty">
                        No customer orders found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
