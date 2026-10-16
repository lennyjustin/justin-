<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$estimateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$estimateId || $estimateId < 1) {
    http_response_code(400);
    exit('A valid estimate ID is required.');
}

$statement = db()->prepare('SELECT id, project_name, project_type, location, floor_area, floors, finish_level, transport_percent, contingency_percent, materials_cost, labour_cost, transport_cost, contingency_cost, total_cost, created_at FROM estimates WHERE id = :id AND user_id = :user_id');
$statement->execute(['id' => $estimateId, 'user_id' => $user['id']]);
$estimate = $statement->fetch();
if (!$estimate) {
    http_response_code(404);
    exit('Saved estimate not found.');
}
$projectImage = $estimate['project_type'] === 'rental' ? 'apartment-concept.png' : 'family-home-concept.png';
$projectImageAlt = $estimate['project_type'] === 'rental'
    ? 'Rental apartment building concept poster'
    : 'Five-room family home concept poster';

$itemStatement = db()->prepare('SELECT material_name, unit, quantity, unit_price, total_cost FROM estimate_items WHERE estimate_id = :estimate_id ORDER BY id');
$itemStatement->execute(['estimate_id' => $estimateId]);
$items = $itemStatement->fetchAll();
$pageTitle = 'Saved estimate';
require __DIR__ . '/includes/header.php';
?>
<section class="saved-estimate">
    <div class="saved-estimate-actions">
        <a class="text-link" href="<?= e(url('dashboard.php')) ?>">← Back to my projects</a>
        <button type="button" class="button button-outline print-button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <div class="saved-estimate-heading">
        <div>
            <div class="eyebrow">SAVED PRELIMINARY ESTIMATE</div>
            <h1><?= e($estimate['project_name']) ?></h1>
            <p><?= e(ucfirst($estimate['project_type'])) ?> · <?= e($estimate['location']) ?> · <?= e(number_format((float) $estimate['floor_area'], 1)) ?> m² × <?= e($estimate['floors']) ?> floor(s) · <?= e(ucfirst($estimate['finish_level'])) ?> finish</p>
            <p class="saved-date">Saved <?= e(date('d M Y', strtotime($estimate['created_at']))) ?></p>
        </div>
    </div>
    <figure class="saved-estimate-illustration">
        <img class="estimate-house-image" src="<?= e(url('assets/img/' . $projectImage)) ?>" alt="<?= e($projectImageAlt) ?>">
        <figcaption>Concept poster · Not a rendering of the actual project</figcaption>
    </figure>
    <div class="results-total"><span>Estimated project total</span><strong><?= e(kes((float) $estimate['total_cost'])) ?></strong><small>Planning estimate only · Saved rate snapshot</small></div>
    <div class="table-wrap"><table class="estimate-table"><thead><tr><th>Material</th><th>Estimated quantity</th><th>Rate</th><th class="amount">Estimated cost</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= e($item['material_name']) ?></td><td><?= e(number_format((float) $item['quantity'], 2)) ?> <?= e($item['unit']) ?></td><td><?= e(kes((float) $item['unit_price'])) ?> / <?= e($item['unit']) ?></td><td class="amount"><?= e(kes((float) $item['total_cost'])) ?></td></tr><?php endforeach; ?>
    <tr class="summary-row"><td colspan="3">Materials subtotal</td><td class="amount"><?= e(kes((float) $estimate['materials_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Labour allowance</td><td class="amount"><?= e(kes((float) $estimate['labour_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Transport (<?= e($estimate['transport_percent']) ?>%)</td><td class="amount"><?= e(kes((float) $estimate['transport_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Contingency (<?= e($estimate['contingency_percent']) ?>%)</td><td class="amount"><?= e(kes((float) $estimate['contingency_cost'])) ?></td></tr>
    </tbody></table></div>
    <p class="estimate-disclaimer">This saved estimate reflects rates from the time it was created. Quantities and prices are illustrative; verify supplier prices and take-offs locally. This is not a professional BOQ or structural design.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
