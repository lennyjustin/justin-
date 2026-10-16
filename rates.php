<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$pageTitle = 'My material rates';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $materials = material_rates();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $upsertSql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? 'INSERT INTO user_material_rates (user_id, material_id, price) VALUES (:user_id, :material_id, :price) ON CONFLICT (user_id, material_id) DO UPDATE SET price = EXCLUDED.price'
            : 'INSERT INTO user_material_rates (user_id, material_id, price) VALUES (:user_id, :material_id, :price) ON DUPLICATE KEY UPDATE price = VALUES(price)';
        $upsert = $pdo->prepare($upsertSql);
        $delete = $pdo->prepare('DELETE FROM user_material_rates WHERE user_id = :user_id AND material_id = :material_id');
        foreach ($materials as $material) {
            $key = 'rate_' . $material['id'];
            $value = trim((string) ($_POST[$key] ?? ''));
            if ($value === '') {
                $delete->execute(['user_id' => $user['id'], 'material_id' => $material['id']]);
            } elseif (!is_numeric($value) || (float) $value <= 0 || (float) $value > 10000000) {
                throw new InvalidArgumentException('Enter a valid positive rate for ' . $material['name'] . ', or leave it blank to use the sample rate.');
            } else {
                $upsert->execute(['user_id' => $user['id'], 'material_id' => $material['id'], 'price' => round((float) $value, 2)]);
            }
        }
        $pdo->commit();
        set_flash('Your material rates have been updated.');
        redirect('rates.php');
    } catch (InvalidArgumentException $exception) {
        $pdo->rollBack();
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
$materials = material_rates((int) $user['id']);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-hero rates-hero"><div class="eyebrow"><span class="eyebrow-dot"></span> MAKE IT YOURS</div><h1>Set your material rates.</h1><p>Replace sample prices with the rates from your suppliers. Leave a field blank to use the sample rate again.</p></section>
<section class="rates-content"><div class="rates-callout"><span>i</span><p><strong>Rates are private to your account.</strong> Prices are not verified market averages. Add the source and date to your own project notes outside the app.</p></div>
<div class="form-card rates-card"><div class="form-card-head"><div><span class="step-kicker">PERSONAL PRICE LIST</span><h2>Materials & unit prices</h2></div><span class="form-number">KSh</span></div><?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="rates-list"><?php foreach ($materials as $material): ?><label class="rate-row"><span><strong><?= e($material['name']) ?></strong><small>Sample: <?= e(kes((float) $material['default_price'])) ?> / <?= e($material['unit']) ?><?= $material['price'] != $material['default_price'] ? ' · Your current override: ' . e(kes((float) $material['price'])) : '' ?></small></span><span class="rate-input"><span>KSh</span><input type="number" name="rate_<?= e($material['id']) ?>" min="0.01" max="10000000" step="0.01" placeholder="<?= e(number_format((float) $material['default_price'], 2)) ?>" value="<?= $material['price'] != $material['default_price'] ? e(number_format((float) $material['price'], 2, '.', '')) : '' ?>" aria-label="<?= e($material['name']) ?> price per <?= e($material['unit']) ?>"><small>/ <?= e($material['unit']) ?></small></span></label><?php endforeach; ?></div><div class="rates-actions"><p>Blank fields use sample rates.</p><button class="button" type="submit">Save my rates <span>↗</span></button></div></form></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
