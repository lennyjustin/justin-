<?php
$pageTitle = $pageTitle ?? 'Construction Estimator';
$user = current_user();
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Plan construction budgets with a transparent, Kenya-first preliminary cost estimator.">
    <title><?= e($pageTitle) ?> | Construction Estimator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body>
<header class="site-header">
    <div class="nav-wrap">
        <a class="brand" href="<?= e(url()) ?>" aria-label="Construction Estimator home"><span class="brand-mark">C</span><span>Construction <span class="brand-light">Estimator</span></span></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>
        <nav class="main-nav" id="main-nav">
            <a class="<?= $activePage === 'index.php' ? 'active' : '' ?>" href="<?= e(url()) ?>">Home</a>
            <a class="<?= $activePage === 'estimate.php' ? 'active' : '' ?>" href="<?= e(url('estimate.php')) ?>">Estimator</a>
            <a class="<?= $activePage === 'pricing.php' ? 'active' : '' ?>" href="<?= e(url('pricing.php')) ?>">Pricing</a>
            <?php if ($user): ?>
                <a class="<?= $activePage === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">My projects</a>
                <a class="<?= $activePage === 'rates.php' ? 'active' : '' ?>" href="<?= e(url('rates.php')) ?>">My rates</a>
                <a class="nav-user" href="<?= e(url('dashboard.php')) ?>"><?= e($user['name']) ?></a>
                <a class="button button-small button-outline" href="<?= e(url('logout.php')) ?>">Sign out</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Sign in</a>
                <a class="button button-small" href="<?= e(url('register.php')) ?>">Get started <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php if ($flash = get_flash()): ?>
    <div class="flash flash-<?= e($flash['type'] ?? 'notice') ?>" role="status"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>
