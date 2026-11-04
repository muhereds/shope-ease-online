```php
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Only allow deletion through a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Check the security token.
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Invalid security token. Return to the product list and try again.');
}

// Validate the product ID.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid product ID.');
}

// Delete the selected product using a prepared statement.
$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

$deleted = $stmt->affected_rows;
$stmt->close();

if ($deleted === 0) {
    header('Location: products.php?error=not_found');
    exit;
}

header('Location: products.php?deleted=1');
exit;
