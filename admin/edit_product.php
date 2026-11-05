
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getProduct(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        'SELECT id, name, description, price, image
         FROM products WHERE id = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $product ?: null;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$postedId || $postedId < 1) {
        http_response_code(400);
        exit('Invalid product ID.');
    }

    $id = $postedId;
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid product ID.');
}

$product = getProduct($conn, (int) $id);

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

$error = '';

$name = $product['name'];
$description = $product['description'] ?? '';
$price = (string) $product['price'];
$image = $product['image'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Invalid security token. Return to the product list and try again.');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $image = trim($_POST['image'] ?? '');

    if ($name === '' || strlen($name) > 150) {
        $error = 'Enter a product name of 1–150 characters.';
    } elseif (
        !is_numeric($price) ||
        !is_finite((float) $price) ||
        (float) $price < 0 ||
        (float) $price > 99999999.99
    ) {
        $error = 'Enter a valid non-negative price.';
    } elseif (strlen($image) > 255) {
        $error = 'Image path must be 255 characters or fewer.';
    } else {
        try {
            $stmt = $conn->prepare(
                'UPDATE products
                 SET name = ?, description = ?, price = ?, image = ?
                 WHERE id = ?'
            );

            $numericPrice = (float) $price;
            $stmt->bind_param(
                'ssdsi',
                $name,
                $description,
                $numericPrice,
                $image,
                $id
            );

            $stmt->execute();
            $stmt->close();

            header('Location: products.php?updated=1');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('ShopEase product update failed: ' . $exception->getMessage());
            $error = 'Could not update the product. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Product | ShopEase</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1e293b;
        }

        .card {
            max-width: 650px;
            margin: auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
        }

        h1 { color: #173b6c; }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        textarea { min-height: 110px; }

        button, .back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 17px;
            border: 0;
            border-radius: 7px;
            background: #173b6c;
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .error {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 7px;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Edit Product #<?= (int) $id ?></h1>
    <p>Change the product information below.</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="edit_product.php">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >

        <label for="name">Product Name *</label>
        <input
            id="name"
            name="name"
            maxlength="150"
            required
            value="<?= e($name) ?>"
        >

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($description) ?></textarea>

        <label for="price">Price (ETB) *</label>
        <input
            id="price"
            name="price"
            type="number"
            min="0"
            max="99999999.99"
            step="0.01"
            required
            value="<?= e($price) ?>"
        >

        <label for="image">Image Filename or Path</label>
        <input
            id="image"
            name="image"
            maxlength="255"
            value="<?= e($image) ?>"
        >

        <button type="submit">Save Changes</button>
        <a class="back" href="products.php">Cancel</a>
    </form>
</div>
</body>
</html>
