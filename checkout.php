
<?php
require_once 'db.php';

$message = '';
$success = false;

$name = '';
$phone = '';
$address = '';
$products = '';
$total = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $products = trim($_POST['products'] ?? '');
    $totalInput = $_POST['total'] ?? '';

    $total = filter_var($totalInput, FILTER_VALIDATE_FLOAT);

    if (
        $name === '' ||
        $phone === '' ||
        $address === '' ||
        $products === '' ||
        $total === false ||
        $total < 0
    ) {
        $message = 'Please complete all fields correctly.';
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO orders
                (customer_name, phone, address, products, total)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssd",
                $name,
                $phone,
                $address,
                $products,
                $total
            );

            $stmt->execute();
            $stmt->close();

            $message = 'Your order has been submitted successfully!';
            $success = true;

            $name = '';
            $phone = '';
            $address = '';
            $products = '';
            $total = '';
        } catch (mysqli_sql_exception $e) {
            error_log('ShopEase order save failed: ' . $e->getMessage());
            $message = 'We could not save your order. Please try again.';
        }
    }
} else {
    $products = $_GET['products'] ?? '';
    $total = $_GET['total'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | ShopEase</title>

   <link rel="stylesheet" href="styles.css?v=2">
</style>
</head>
<body>

<header class="checkout-topbar">
    <a href="index.php" class="checkout-brand">
        Shop<span>Ease</span>
    </a>

    <span class="checkout-secure">
        <span aria-hidden="true">✓</span> Secure Checkout
    </span>
</header>

<main class="checkout-page">
    <div class="checkout-container">

        <div class="checkout-heading">
            <p class="checkout-eyebrow">ALMOST THERE!</p>
            <h1>Complete Your Order</h1>
            <p>Enter your details below to arrange your delivery.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="checkout-message <?= $success ? 'success' : 'error' ?>"
                 role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="checkout-layout">

            <section class="checkout-card">
                <h2>Delivery Information</h2>
                <p class="checkout-description">
                    Please provide accurate contact and delivery details.
                </p>

                <form method="post" action="checkout.php"
                      class="checkout-form">

                    <div class="form-group">
                        <label for="customer_name">Full name</label>
                        <input
                            id="customer_name"
                            type="text"
                            name="customer_name"
                            placeholder="Enter your full name"
                            maxlength="100"
                            autocomplete="name"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input
                            id="phone"
                            type="tel"
                            name="phone"
                            placeholder="Enter your phone number"
                            maxlength="20"
                            autocomplete="tel"
                            value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="address">Delivery address</label>
                        <textarea
                            id="address"
                            name="address"
                            placeholder="Enter your town, area, and delivery details"
                            autocomplete="street-address"
                            required><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="products">Products ordered</label>
                        <textarea
                            id="products"
                            name="products"
                            placeholder="Your selected products"
                            required><?= htmlspecialchars($products, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="total">Total price (ETB)</label>
                        <input
                            id="total"
                            type="number"
                            name="total"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            value="<?= htmlspecialchars((string)$total, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>

                    <button type="submit" class="checkout-submit">
                        Submit Order
                        <span aria-hidden="true">→</span>
                    </button>
                </form>
            </section>

            <aside class="order-summary">
                <div class="summary-icon" aria-hidden="true">🛍️</div>
                <h2>Your Order</h2>
                <p>Review your selected products before submitting.</p>

                <div class="summary-products">
                    <span>Products</span>
                    <strong>
                        <?= $products !== ''
                            ? htmlspecialchars($products, ENT_QUOTES, 'UTF-8')
                            : 'No products selected yet' ?>
                    </strong>
                </div>

                <div class="summary-total">
                    <span>Total to pay</span>
                    <strong>
                        ETB <?= is_numeric($total)
                            ? number_format((float)$total, 2)
                            : '0.00' ?>
                    </strong>
                </div>

                <div class="summary-note">
                    <span aria-hidden="true">✓</span>
                    Please check your delivery details before submitting.
                </div>
            </aside>

        </div>
        <?php else: ?>
            <section class="checkout-success">
                <div class="success-icon" aria-hidden="true">✓</div>
                <h2>Thank you for shopping with us!</h2>
                <p>Your order has been saved. Our store team can review it.</p>
                <a href="index.php" class="checkout-submit success-back">
                    Continue Shopping
                </a>
            </section>
        <?php endif; ?>

        <a href="index.php" class="checkout-back">
            ← Back to ShopEase
        </a>

    </div>
</main>

<footer class="checkout-footer">
    <p>© <?= date('Y') ?> ShopEase. Thank you for shopping with us.</p>
</footer>

</body>
</html>
