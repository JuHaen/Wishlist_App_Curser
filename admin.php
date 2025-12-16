<?php
require_once __DIR__ . '/helpers.php';

if (!file_exists(__DIR__ . '/config.php')) {
    echo 'Bitte installiere die Anwendung zuerst über install.php';
    exit;
}

$settings = getSettings();
$pdo = getPDO();
$message = '';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    if (password_verify($_POST['login_password'], $settings['admin_password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $message = 'Falsches Passwort';
    }
}

if (!isAdminLoggedIn()) {
    renderHeader('Admin Login');
    ?>
    <div class="card centered">
        <h1>Admin Login</h1>
        <?php if ($message): ?><div class="notice error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <form method="post">
            <label>Passwort<input type="password" name="login_password" required></label>
            <button type="submit" class="primary">Anmelden</button>
        </form>
    </div>
    <?php
    renderFooter();
    exit;
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_password'])) {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->execute([$name]);
            $message = 'Kategorie hinzugefügt.';
        }
    }

    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Kategorie gelöscht.';
    }

    if (isset($_POST['add_gift'])) {
        $title = trim($_POST['gift_title'] ?? '');
        if ($title) {
            $stmt = $pdo->prepare('INSERT INTO gifts (title, description, category_id, price) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $title,
                trim($_POST['gift_description'] ?? ''),
                $_POST['gift_category'] !== '' ? (int)$_POST['gift_category'] : null,
                trim($_POST['gift_price'] ?? '')
            ]);
            $message = 'Geschenk gespeichert.';
        }
    }

    if (isset($_POST['update_gift'])) {
        $id = (int)$_POST['gift_id'];
        $keepReserved = empty($_POST['clear_reserved']) ? 1 : 0;
        if ($keepReserved) {
            $stmt = $pdo->prepare('UPDATE gifts SET title = ?, description = ?, category_id = ?, price = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['gift_title']),
                trim($_POST['gift_description']),
                $_POST['gift_category'] !== '' ? (int)$_POST['gift_category'] : null,
                trim($_POST['gift_price']),
                $id
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE gifts SET title = ?, description = ?, category_id = ?, price = ?, is_reserved = 0, reserved_by_name = NULL WHERE id = ?');
            $stmt->execute([
                trim($_POST['gift_title']),
                trim($_POST['gift_description']),
                $_POST['gift_category'] !== '' ? (int)$_POST['gift_category'] : null,
                trim($_POST['gift_price']),
                $id
            ]);
        }
        $message = 'Geschenk aktualisiert.';
    }

    if (isset($_POST['delete_gift'])) {
        $id = (int)$_POST['gift_id'];
        $stmt = $pdo->prepare('DELETE FROM gifts WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Geschenk gelöscht.';
    }

    if (isset($_POST['update_settings'])) {
        $showNames = isset($_POST['show_giver_names']) ? 1 : 0;
        $newPassword = trim($_POST['new_password'] ?? '');
        $newToken = !empty($_POST['regenerate_token']);

        $passwordHash = $settings['admin_password_hash'];
        if ($newPassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $token = $settings['guest_access_token'];
        if ($newToken) {
            $token = bin2hex(random_bytes(16));
        }

        $stmt = $pdo->prepare('UPDATE settings SET admin_password_hash = ?, show_giver_names = ?, guest_access_token = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $showNames, $token, $settings['id']]);
        $settings = getSettings();
        $message = 'Einstellungen aktualisiert.';
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$gifts = $pdo->query('SELECT g.*, c.name AS category_name FROM gifts g LEFT JOIN categories c ON c.id = g.category_id ORDER BY g.created_at DESC')->fetchAll();
$settings = getSettings();
renderHeader('Admin Panel');
?>
<header class="topbar">
    <div>
        <strong>Wishlist Admin</strong>
        <div class="muted">Sicherer Gastlink: <code>guest.php?token=<?= htmlspecialchars($settings['guest_access_token']) ?></code></div>
    </div>
    <a class="ghost" href="?action=logout">Logout</a>
</header>
<div class="layout">
    <section class="card">
        <h2>Geschenk hinzufügen</h2>
        <?php if ($message): ?><div class="notice"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="add_gift" value="1">
            <label>Titel*<input type="text" name="gift_title" required></label>
            <label>Kategorie
                <select name="gift_category">
                    <option value="">Keine</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Preis / Hinweis<input type="text" name="gift_price" placeholder="Optional"></label>
            <label>Beschreibung<textarea name="gift_description" rows="3" placeholder="Details oder Links"></textarea></label>
            <button type="submit" class="primary">Speichern</button>
        </form>
    </section>

    <section class="card">
        <h2>Kategorien</h2>
        <form method="post" class="inline-form">
            <input type="hidden" name="add_category" value="1">
            <input type="text" name="category_name" placeholder="Neue Kategorie" required>
            <button type="submit">Hinzufügen</button>
        </form>
        <div class="pill-list">
            <?php foreach ($categories as $cat): ?>
                <form method="post" class="pill">
                    <input type="hidden" name="delete_category" value="1">
                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <button type="submit" class="ghost">Löschen</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h2>Geschenke verwalten</h2>
        <div class="list">
            <?php foreach ($gifts as $gift): ?>
                <details>
                    <summary>
                        <div>
                            <strong><?= htmlspecialchars($gift['title']) ?></strong>
                            <?php if ($gift['category_name']): ?>
                                <span class="tag"><?= htmlspecialchars($gift['category_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($gift['is_reserved']): ?>
                            <span class="status reserved">Reserviert</span>
                        <?php else: ?>
                            <span class="status open">Offen</span>
                        <?php endif; ?>
                    </summary>
                    <form method="post" class="form-grid compact">
                        <input type="hidden" name="update_gift" value="1">
                        <input type="hidden" name="gift_id" value="<?= $gift['id'] ?>">
                        <label>Titel*<input type="text" name="gift_title" value="<?= htmlspecialchars($gift['title']) ?>" required></label>
                        <label>Kategorie
                            <select name="gift_category">
                                <option value="">Keine</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $gift['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Preis / Hinweis<input type="text" name="gift_price" value="<?= htmlspecialchars($gift['price']) ?>"></label>
                        <label>Beschreibung<textarea name="gift_description" rows="3"><?= htmlspecialchars($gift['description']) ?></textarea></label>
                        <label class="checkbox">
                            <input type="checkbox" name="clear_reserved">Reservierung zurücksetzen
                        </label>
                        <div class="actions">
                            <button type="submit">Speichern</button>
                        </div>
                    </form>
                    <form method="post" class="inline-form right">
                        <input type="hidden" name="delete_gift" value="1">
                        <input type="hidden" name="gift_id" value="<?= $gift['id'] ?>">
                        <button type="submit" class="ghost">Geschenk löschen</button>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h2>Einstellungen</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="update_settings" value="1">
            <label>Neues Passwort<input type="password" name="new_password" placeholder="leer lassen um beizubehalten"></label>
            <label class="checkbox">
                <input type="checkbox" name="show_giver_names" <?= $settings['show_giver_names'] ? 'checked' : '' ?>>
                Namen der Schenkenden für Gäste anzeigen
            </label>
            <label class="checkbox">
                <input type="checkbox" name="regenerate_token">
                Sicheren Gastlink neu generieren
            </label>
            <button type="submit">Aktualisieren</button>
        </form>
        <p class="muted">Aktueller Gastlink: <code>guest.php?token=<?= htmlspecialchars($settings['guest_access_token']) ?></code></p>
    </section>
</div>
<?php
renderFooter();
