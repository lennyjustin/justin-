<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$pageTitle = 'My projects';
$statement = db()->prepare('SELECT id, project_name, project_type, location, floor_area, floors, finish_level, total_cost, created_at FROM estimates WHERE user_id = :user_id ORDER BY created_at DESC');
$statement->execute(['user_id' => $user['id']]);
$estimates = $statement->fetchAll();
$total = array_sum(array_map(static fn(array $row): float => (float) $row['total_cost'], $estimates));
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-hero"><div><div class="eyebrow"><span class="eyebrow-dot"></span> YOUR BUILDING PLANS</div><h1>Good to see you, <?= e($user['name']) ?>.</h1><p>Keep track of your estimates and the budgets you’re exploring.</p></div><a class="button" href="<?= e(url('estimate.php')) ?>">New estimate <span>↗</span></a></section>
<section class="dashboard-stats"><div class="dashboard-stat"><span>Saved estimates</span><strong><?= count($estimates) ?></strong></div><div class="dashboard-stat"><span>Combined estimated budgets</span><strong><?= e(kes($total)) ?></strong></div><div class="dashboard-stat"><span>Your rate list</span><a href="<?= e(url('rates.php')) ?>">Manage material rates ↗</a></div></section>
<section class="project-list-section"><div class="list-heading"><div><div class="eyebrow">YOUR PROJECTS</div><h2>Saved estimates</h2></div><?php if ($estimates): ?><span class="project-count"><?= count($estimates) ?> project<?= count($estimates) === 1 ? '' : 's' ?></span><?php endif; ?></div>
<?php if (!$estimates): ?><div class="empty-state"><span class="empty-icon">⌂</span><h3>Your first project starts here.</h3><p>Run your first estimate to see your budget summary here.</p><a class="button" href="<?= e(url('estimate.php')) ?>">Create an estimate <span>↗</span></a></div>
<?php else: ?><div class="project-table-wrap"><table class="project-table"><thead><tr><th>Project</th><th>Location</th><th>Size</th><th>Saved</th><th class="amount">Estimated budget</th><th></th></tr></thead><tbody><?php foreach ($estimates as $estimate): ?><tr><td><strong><?= e($estimate['project_name']) ?></strong><small><?= e(ucfirst($estimate['project_type'])) ?> · <?= e(ucfirst($estimate['finish_level'])) ?> finish</small></td><td><?= e($estimate['location']) ?></td><td><?= e(number_format((float) $estimate['floor_area'], 1)) ?> m² × <?= e($estimate['floors']) ?></td><td><?= e(date('d M Y', strtotime($estimate['created_at']))) ?></td><td class="amount budget-cell"><?= e(kes((float) $estimate['total_cost'])) ?></td><td><a class="project-view-link" href="<?= e(url('estimate_detail.php?id=' . (int) $estimate['id'])) ?>">View / print</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<div class="dashboard-note"><strong>Remember:</strong> Saved totals are snapshots based on the material rates available when each estimate was created. Confirm prices and quantities before making decisions.</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
