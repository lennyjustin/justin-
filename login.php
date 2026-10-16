<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $statement = db()->prepare('SELECT id, name, email, password_hash, workspace_name, currency FROM users WHERE email = ?');
        $statement->execute([$email]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Email or password is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['workspace_name'] = $user['workspace_name'];
            $_SESSION['currency'] = $user['currency'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in to FleetCare EA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="app-extra.css">
</head>
<body class="auth-page">
  <main class="auth-layout">
    <section class="auth-story"><a class="brand auth-brand" href="login.php"><span class="brand-mark">FC</span><span class="brand-name">fleetcare<span>EA</span></span></a><div class="auth-story-copy"><span class="auth-asterisk">✳</span><p class="eyebrow">YOUR FLEET, MOVING FORWARD</p><h1>Good care keeps<br>business moving.</h1><p>Bring vehicles, maintenance, fuel and renewals together in one calm, clear workspace.</p><div class="auth-story-note"><span class="auth-note-check">✓</span>Everything your fleet needs, in one place.</div></div><span class="auth-story-footer">MADE FOR EAST AFRICAN FLEETS</span></section>
    <section class="auth-main"><div class="auth-form-wrap"><p class="eyebrow">WELCOME BACK</p><h2>Sign in to FleetCare</h2><p class="auth-subtitle">Pick up right where your fleet left off.</p>
      <?php if ($error !== ''): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form class="auth-form" method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Work email<input type="email" name="email" autocomplete="email" required maxlength="254" value="<?= e($email) ?>" placeholder="you@company.com"></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required placeholder="Your password"></label>
        <button class="button button-primary auth-submit" type="submit">Sign in <svg class="icon"><use href="#i-arrow"/></svg></button>
      </form>
      <p class="auth-switch">New to FleetCare? <a href="register.php">Create an account</a></p>
      <p class="auth-legal">Your fleet dashboard is available only to your account.</p>
    </div></section>
  </main>
</body>
</html>
