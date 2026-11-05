
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

$name = '';
$description = '';
$price = '';
$image = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
   
$image = '';

if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed. Please try again.';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Image must be 2 MB or smaller.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mime])) {
            $error = 'Choose a JPG, PNG, or WebP image.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/';

            if (!is_dir($uploadDir)) {
                $error = 'The uploads folder was not found.';
            } else {
                $filename = bin2hex(random_bytes(16))
                    . '.' . $allowedTypes[$mime];

                if (move_uploaded_file(
                    $file['tmp_name'],
                    $uploadDir . $filename
                )) {
                    $image = 'uploads/' . $filename;
                } else {
                    $error = 'Could not save the uploaded image.';
                }
            }
        }
    }
}

    if ($name === '' || $price === '') {
        $error = 'Product name and price are required.';
    } elseif (strlen($name) > 150) {
        $error = 'Product name must be 150 characters or fewer.';
    } elseif (
        !is_numeric($price) ||
        !is_finite((float) $price) ||
        (float) $price < 0 ||
        (float) $price > 99999999.99
    ) {
        $error = 'Enter a valid non-negative price.';
    } elseif (strlen($image) > 255) {
        $error = 'Image filename must be 255 characters or fewer.';
    
    } else {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO products (name, description, price, image)
                 VALUES (?, ?, ?, ?)'
            );

            $numericPrice = (float) $price;

            $stmt->bind_param(
                'ssds',
                $name,
                $description,
                $numericPrice,
                $image
            );

            if ($stmt->execute()) {
                $success = 'Product added successfully!';

                $name = '';
                $description = '';
                $price = '';
                $image = '';

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = 'Could not add product. Please try again.';
            }

            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            error_log('ShopEase product insert failed: ' . $exception->getMessage());
            $error = 'Could not save the product. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Product | ShopEase</title>

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
            box-shadow: 0 4px 18px #0000000d;
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

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        button, .back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 17px;
            border: none;
            border-radius: 7px;
            background: #173b6c;
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin: 15px 0;
        }

        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }

        .hint {
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Add New Product</h1>
    <p>Enter the details for your new ShopEase product.</p>

    <?php if ($error !== ''): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="message success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
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

<label for="image">Choose Product Image</label>
<input
    id="image"
    name="image"
    type="file"
    accept="image/jpeg,image/png,image/webp"
>

<p class="hint">
    Choose a JPG, PNG, or WebP image (maximum 2 MB).
</p>



        <button type="submit">Save Product</button>
        <a class="back" href="products.php">View Products</a>
    </form>
</div>
</body>
</html>
