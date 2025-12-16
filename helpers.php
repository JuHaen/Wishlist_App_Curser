<?php
require_once __DIR__ . '/db.php';

session_start();

function getSettings(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM settings LIMIT 1');
    $settings = $stmt->fetch();
    if (!$settings) {
        return [
            'admin_password_hash' => '',
            'show_giver_names' => 1,
            'guest_access_token' => ''
        ];
    }
    return $settings;
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: admin.php');
        exit;
    }
}

function renderHeader(string $title): void
{
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>{$title}</title><link rel=\"stylesheet\" href=\"assets/style.css\"></head><body>";
}

function renderFooter(): void
{
    echo '</body></html>';
}
