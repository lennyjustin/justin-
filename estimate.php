<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Construction cost estimator';
$user = current_user();
$input = [
    'project_name' => '',
    'location' => '',
    'project_type' => 'house',
    'floor_area' => '',
    'floors' => '1',
    'finish_level' => 'standard',
    'transport_percent' => '8',
    'contingency_percent' => '10',
];
$estimate = null;
$error = null;
$projectImage = 'family-home-concept.png';
$projectImageAlt = 'Five-room family home concept poster';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $input = validate_estimate_input($_POST);
        $estimate = calculate_estimate($input, material_rates($user ? (int) $user['id'] : null));
        if ($input['project_type'] === 'rental') {
            $projectImage = 'apartment-concept.png';
            $projectImageAlt = 'Rental apartment building concept poster';
        }
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-hero">
    <div class="eyebrow"><span class="eyebrow-dot"></span> YOUR PROJECT STARTS HERE</div>
    <h1>Construction cost estimator</h1>
    <p>Get a preliminary, itemized budget in KSh. Adjust the assumptions to reflect your project.</p>
</section>
<section class="estimator-layout">
    <div class="form-card">
        <div class="form-card-head"><div><span class="step-kicker">PROJECT DETAILS</span><h2>Tell us what you’re building</h2></div><span class="form-number">01</span></div>
        <?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('estimate.php')) ?>" class="estimate-form" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Project name <span class="optional">Optional</span><input type="text" name="project_name" maxlength="100" placeholder="e.g. Our family home" value="<?= e($input['project_name']) ?>"></label>
            <div class="form-row">
                <label>Project type<select name="project_type" required><option value="house" <?= $input['project_type'] === 'house' ? 'selected' : '' ?>>New home</option><option value="extension" <?= $input['project_type'] === 'extension' ? 'selected' : '' ?>>Home extension</option><option value="rental" <?= $input['project_type'] === 'rental' ? 'selected' : '' ?>>Rental units</option></select></label>
                <label>Location / county<input type="text" name="location" required maxlength="100" placeholder="e.g. Kiambu" value="<?= e($input['location']) ?>"></label>
            </div>
            <div class="form-row">
                <label>Floor area <span class="input-unit">m² per floor</span><input type="number" name="floor_area" min="10" max="20000" step="0.1" required placeholder="e.g. 120" value="<?= e($input['floor_area']) ?>"></label>
                <label>Number of floors<select name="floors"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= (string) $input['floors'] === (string) $i ? 'selected' : '' ?>><?= $i ?> floor<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></label>
            </div>
            <fieldset class="finish-options"><legend>Finish level</legend>
                <?php foreach (['basic' => ['Basic', 'Essential, practical finish'], 'standard' => ['Standard', 'A balanced everyday finish'], 'premium' => ['Premium', 'Higher-spec finish allowance']] as $key => [$label, $description]): ?>
                    <label class="finish-option"><input type="radio" name="finish_level" value="<?= e($key) ?>" <?= $input['finish_level'] === $key ? 'checked' : '' ?>><span class="finish-radio"></span><span><strong><?= e($label) ?></strong><small><?= e($description) ?></small></span></label>
                <?php endforeach; ?>
            </fieldset>
            <details class="assumption-details"><summary>Adjust allowances</summary><div class="form-row">
                <label>Transport allowance <span class="input-unit">%</span><input type="number" name="transport_percent" min="0" max="30" step="0.5" value="<?= e($input['transport_percent']) ?>"></label>
                <label>Contingency <span class="input-unit">%</span><input type="number" name="contingency_percent" min="0" max="30" step="0.5" value="<?= e($input['contingency_percent']) ?>"></label>
            </div></details>
            <button class="button button-large button-full" type="submit">Calculate my estimate <span aria-hidden="true">↗</span></button>
            <p class="form-note">Your estimate is calculated from the sample rates or your own saved rate overrides.</p>
        </form>
    </div>
    <aside class="estimator-aside">
        <div class="aside-card aside-dark"><span class="aside-icon">✳</span><h3>A useful starting point, not a final quote.</h3><p>Our calculator uses simplified quantities per square metre. Actual needs vary with design, soil, specifications, site and local construction practice.</p></div>
        <div class="aside-card"><span class="step-kicker">WHAT’S INCLUDED</span><ul class="included-list"><li>7 core construction materials</li><li>Labour allowance by finish level</li><li>Transport allowance</li><li>Contingency allowance</li><li>Print-friendly itemization</li></ul></div>
    </aside>
</section>
<?php if ($estimate): ?>
<section class="results-section" id="estimate-results">
    <div class="results-heading"><div><div class="eyebrow">YOUR PRELIMINARY ESTIMATE</div><h2><?= e($input['project_name']) ?></h2><p><?= e($estimate['project_type_label']) ?> · <?= e($input['location']) ?> · <?= e(number_format($input['floor_area'], 1)) ?> m² × <?= e($input['floors']) ?> floor(s) · <?= e($estimate['finish_label']) ?> finish</p></div><button type="button" class="button button-outline print-button" onclick="window.print()">Print / Save PDF</button></div>
    <img class="estimate-house-image" src="<?= e(url('assets/img/' . $projectImage)) ?>" alt="<?= e($projectImageAlt) ?>">
    <div class="results-total"><span>Estimated project total</span><strong><?= e(kes($estimate['total_cost'])) ?></strong><small>Planning estimate only · Sample or personal rates</small></div>
    <div class="table-wrap"><table class="estimate-table"><thead><tr><th>Material</th><th>Estimated quantity</th><th>Rate</th><th class="amount">Estimated cost</th></tr></thead><tbody>
    <?php foreach ($estimate['items'] as $item): ?><tr><td><?= e($item['name']) ?></td><td><?= e(number_format($item['quantity'], 2)) ?> <?= e($item['unit']) ?></td><td><?= e(kes($item['unit_price'])) ?> / <?= e($item['unit']) ?></td><td class="amount"><?= e(kes($item['total_cost'])) ?></td></tr><?php endforeach; ?>
    <tr class="summary-row"><td colspan="3">Materials subtotal</td><td class="amount"><?= e(kes($estimate['materials_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Labour allowance</td><td class="amount"><?= e(kes($estimate['labour_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Transport (<?= e($input['transport_percent']) ?>%)</td><td class="amount"><?= e(kes($estimate['transport_cost'])) ?></td></tr>
    <tr class="summary-row"><td colspan="3">Contingency (<?= e($input['contingency_percent']) ?>%)</td><td class="amount"><?= e(kes($estimate['contingency_cost'])) ?></td></tr>
    </tbody></table></div>
    <p class="estimate-disclaimer">Quantities and prices are illustrative. Verify supplier prices and take-offs locally. This is not a professional BOQ or structural design.</p>
    <?php if ($user): ?>
    <form method="post" action="<?= e(url('save_estimate.php')) ?>" class="save-estimate-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($input as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
        <button type="submit" class="button">Save this estimate <span aria-hidden="true">↗</span></button>
    </form>
    <?php else: ?><p class="save-prompt"><a href="<?= e(url('register.php')) ?>">Create a free account</a> to save your estimate and your own material rates.</p><?php endif; ?>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
