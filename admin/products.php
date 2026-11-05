
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Get all products from the database
$stmt = $conn->prepare(
    'SELECT id, name, description, price, image, created_at
     FROM products
     ORDER BY id DESC'
);

$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Safely display text in HTML
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | ShopEase</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1e293b;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            color: #173b6c;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        a.button {
            display: inline-block;
            padding: 11px 16px;
            background: #173b6c;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .table-wrap {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px #0000000d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #eaf0f8;
            color: #173b6c;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #64748b;
        }

        .price {
            white-space: nowrap;
            font-weight: bold;
        }
        .product-image {
    width: 75px;
    height: 65px;
    object-fit: contain;
    padding: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-block;
    padding: 7px 11px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
}

.edit-btn {
    background: #dbeafe;
    color: #1d4ed8;
}

.delete-btn {
    background: #fee2e2;
    color: #b91c1c;
}
    </style>
</head>

<body>
<div class="container">

    <div class="topbar">
        <div>
            <h1>Manage Products</h1>
            <p>View products saved in your ShopEase database.</p>
        </div>

        <a class="button" href="dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="empty">
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= (int) $product['id'] ?></td>

                        <td><?= e($product['name']) ?></td>

                        <td>
                            <?= e($product['description'] ?? '') ?>
                        </td>

                        <td class="price">
                            ETB <?= number_format((float) $product['price'], 2) ?>
                        </td>

                     <td>
    <?php if (!empty($product['image'])): ?>
        <img
            class="product-image"
            src="../images/<?= e(basename($product['image'])) ?>"
            alt="<?= e($product['name']) ?>"
        >
    <?php else: ?>
        <span>No image</span>
    <?php endif; ?>
</td>   
          <td>
    <div class="actions">
        <a class="action-btn edit-btn"
           href="edit_product.php?id=<?= (int) $product['id'] ?>">
            Edit
        </a>

        <form method="POST"
              action="delete_product.php"
              onsubmit="return confirm('Are you sure you want to delete this product?');">

            <input type="hidden" name="id"
                   value="<?= (int) $product['id'] ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <button class="action-btn delete-btn" type="submit">
                Delete
            </button>
        </form>
    </div>
</td>              
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
