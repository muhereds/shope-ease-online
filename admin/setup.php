
<?php
require_once __DIR__ . '/../db.php';

$message = '';
$setupComplete = false;

// Create the admin_users table if it does not exist.
$conn->query("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Check whether an admin account already exists.
$countResult = $conn->query("SELECT COUNT(*) AS total FROM admin_users");
$adminCount = (int) $countResult->fetch_assoc()['total'];

if ($adminCount > 0) {
    http_response_code(403);
    exit(
        'Setup is already complete. ' .
        'For security, this page cannot create another administrator.'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $message = 'Username must be 4–50 characters using letters, numbers, or underscores.';
    } elseif (strlen($password) < 12) {
        $message = 'Use a password with at least 12 characters.';
    } elseif ($password !== $confirmPassword) {
        $message = 'The passwords do not match.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert only if no admin exists, including during simultaneous requests.
            $conn->begin_transaction();

            $lockResult = $conn->query(
                "SELECT id FROM admin_users LIMIT 1 FOR UPDATE"
            );

            if ($lockResult->num_rows > 0) {
                $conn->rollback();
                http_response_code(403);
                exit('An administrator already exists. Setup is closed.');
            }

            $stmt = $conn->prepare(
                "INSERT INTO admin_users (username, password_hash)
                 SELECT ?, ?
                 WHERE NOT EXISTS (SELECT 1 FROM admin_users)"
            );

            $stmt->bind_param('ss', $username, $passwordHash);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $conn->commit();
                $setupComplete = true;
            } else {
                $conn->rollback();
                $message = 'Setup was not completed. Please try again.';
            }

            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
                // The transaction may already have ended.
            }

            error_log('ShopEase admin setup failed: ' . $e->getMessage());
            $message = 'Setup failed. Check the server error log.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopEase | Admin Setup</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #173b6c;
        }

        .panel {
            width: 100%;
            max-width: 440px;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 35px #173b6c18;
        }

        h1 { margin-top: 0; }

        p { color: #64748b; line-height: 1.5; }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            background: #173b6c;
            color: white;
            border: 0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            padding: 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<main class="panel">
    <h1>ShopEase Admin Setup</h1>

    <?php if ($setupComplete): ?>
        <div class="success">
            Administrator account created successfully.
        </div>
        <p>
            Now create the admin login page. Do not reopen or expose
            this setup page publicly.
        </p>

    <?php else: ?>
        <p>
            Create your administrator account.
            Use a strong password that you do not use elsewhere.
        </p>

        <?php if ($message !== ''): ?>
            <div class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="username">Admin username</label>
            <input
                id="username"
                name="username"
                type="text"
                minlength="4"
                maxlength="50"
                pattern="[a-zA-Z0-9_]+"
                autocomplete="username"
                required>

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="12"
                autocomplete="new-password"
                required>

            <label for="confirm_password">Confirm password</label>
            <input
                id="confirm_password"
                name="confirm_password"
                type="password"
                minlength="12"
                autocomplete="new-password"
                required>

            <button type="submit">Create Admin Account</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
