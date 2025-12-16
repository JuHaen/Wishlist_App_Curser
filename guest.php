<?php
require_once __DIR__ . '/helpers.php';

$token = $_GET['token'] ?? '';
$settings = getSettings();
if (!$token || $token !== $settings['guest_access_token']) {
    http_response_code(403);
    echo 'Ungültiger oder fehlender Zugang.';
    exit;
}

$pdo = getPDO();
$showNames = (bool)$settings['show_giver_names'];

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$gifts = $pdo->query('SELECT g.*, c.name AS category_name FROM gifts g LEFT JOIN categories c ON c.id = g.category_id ORDER BY c.name, g.title')->fetchAll();

renderHeader('Wunschliste');
?>
<header class="hero">
    <div>
        <p class="muted">Für unsere Feier</p>
        <h1>Wunschliste</h1>
        <p class="muted">Wähle ein Geschenk aus und reserviere es verbindlich. Vielen Dank!</p>
    </div>
</header>
<main class="layout guest">
    <section class="card">
        <h2>Geschenkideen</h2>
        <div class="grid">
            <?php foreach ($gifts as $gift): ?>
                <article class="gift <?= $gift['is_reserved'] ? 'disabled' : '' ?>" data-gift-id="<?= $gift['id'] ?>">
                    <div class="gift-head">
                        <div>
                            <h3><?= htmlspecialchars($gift['title']) ?></h3>
                            <?php if ($gift['category_name']): ?><span class="tag"><?= htmlspecialchars($gift['category_name']) ?></span><?php endif; ?>
                        </div>
                        <?php if ($gift['is_reserved']): ?>
                            <span class="status reserved">Reserviert</span>
                        <?php else: ?>
                            <span class="status open">Verfügbar</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($gift['price']): ?><p class="muted"><?= htmlspecialchars($gift['price']) ?></p><?php endif; ?>
                    <?php if ($gift['description']): ?><p><?= nl2br(htmlspecialchars($gift['description'])) ?></p><?php endif; ?>
                    <?php if ($gift['is_reserved']): ?>
                        <p class="muted">Bereits zugesagt<?php if ($showNames && $gift['reserved_by_name']): ?> von <strong><?= htmlspecialchars($gift['reserved_by_name']) ?></strong><?php endif; ?>.</p>
                    <?php else: ?>
                        <button class="primary reserve-btn" data-gift="<?= $gift['id'] ?>">Reservieren</button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<div class="modal" id="reserveModal" hidden>
    <div class="modal-content card">
        <h3>Geschenk reservieren</h3>
        <p>Mit deiner Bestätigung wird das Geschenk für alle Gäste als reserviert angezeigt.</p>
        <form id="reserveForm">
            <input type="hidden" name="gift_id" id="giftIdInput">
            <label>Dein Name*<input type="text" name="guest_name" id="guestName" required></label>
            <p class="muted">Dein Name wird <?= $showNames ? '' : 'nicht ' ?>auf der Liste angezeigt.</p>
            <div class="actions">
                <button type="button" class="ghost" id="cancelModal">Abbrechen</button>
                <button type="submit" class="primary">Verbindlich reservieren</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('reserveModal');
const reserveButtons = document.querySelectorAll('.reserve-btn');
const giftInput = document.getElementById('giftIdInput');
const cancelModal = document.getElementById('cancelModal');
const reserveForm = document.getElementById('reserveForm');
const nameInput = document.getElementById('guestName');

reserveButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        giftInput.value = btn.dataset.gift;
        modal.hidden = false;
        nameInput.focus();
    });
});

cancelModal.addEventListener('click', () => {
    modal.hidden = true;
    reserveForm.reset();
});

reserveForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(reserveForm);
    formData.append('token', '<?= htmlspecialchars($token) ?>');
    const response = await fetch('reserve.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();
    if (result.success) {
        alert('Danke! Das Geschenk wurde für dich reserviert.');
        window.location.reload();
    } else {
        alert(result.message || 'Etwas ist schiefgelaufen.');
    }
});
</script>
<?php
renderFooter();
