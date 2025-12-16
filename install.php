<?php
if (file_exists(__DIR__ . '/config.php')) {
    echo 'Die Anwendung ist bereits installiert.';
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPassword = trim($_POST['db_password'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $showGiverNames = isset($_POST['show_giver_names']) ? 1 : 0;

    if ($dbHost && $dbName && $dbUser && $adminPassword) {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPassword, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                admin_password_hash VARCHAR(255) NOT NULL,\n                show_giver_names TINYINT(1) NOT NULL DEFAULT 1,\n                guest_access_token VARCHAR(64) NOT NULL,\n                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS categories (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                name VARCHAR(100) NOT NULL\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS gifts (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                title VARCHAR(150) NOT NULL,\n                description TEXT,\n                category_id INT DEFAULT NULL,\n                price VARCHAR(50) DEFAULT NULL,\n                is_reserved TINYINT(1) NOT NULL DEFAULT 0,\n                reserved_by_name VARCHAR(150) DEFAULT NULL,\n                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n                CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $guestToken = bin2hex(random_bytes(16));

            $pdo->exec('TRUNCATE TABLE settings');
            $stmt = $pdo->prepare('INSERT INTO settings (admin_password_hash, show_giver_names, guest_access_token) VALUES (?, ?, ?)');
            $stmt->execute([$adminHash, $showGiverNames, $guestToken]);

            $config = [
                'db_host' => $dbHost,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPassword,
            ];
            $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
            file_put_contents(__DIR__ . '/config.php', $configContent);

            $message = 'Installation erfolgreich! Admin Panel: admin.php — Gastlink: guest.php?token=' . $guestToken;
        } catch (Throwable $e) {
            $message = 'Fehler bei der Installation: ' . $e->getMessage();
        }
    } else {
        $message = 'Bitte alle Pflichtfelder ausfüllen.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Installer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="installer">
<div class="card centered">
    <h1>Wunschlisten Installer</h1>
    <p>Bitte trage die Daten für deine MySQL-Datenbank und ein Admin-Passwort ein.</p>
    <?php if ($message): ?>
        <div class="notice"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <label>Datenbank Host*<input type="text" name="db_host" required></label>
        <label>Datenbank Name*<input type="text" name="db_name" required></label>
        <label>Datenbank Nutzer*<input type="text" name="db_user" required></label>
        <label>Datenbank Passwort<input type="password" name="db_password"></label>
        <label>Admin-Passwort*<input type="password" name="admin_password" required></label>
        <label class="checkbox">
            <input type="checkbox" name="show_giver_names" checked>
            Namen der Schenkenden für Gäste anzeigen
        </label>
        <button type="submit" class="primary">Installation starten</button>
    </form>
</div>
</body>
</html>
