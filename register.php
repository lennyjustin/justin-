<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';
$workspace = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $workspace = trim((string) ($_POST['workspace_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($name === '' || $workspace === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your name, a valid email address, and a workspace name.';
    } elseif (strlen($password) < 10) {
        $error = 'Choose a password with at least 10 characters.';
    } else {
        try {
            $statement = db()->prepare(
                'INSERT INTO users (name, email, password_hash, workspace_name) VALUES (?, ?, ?, ?)'
            );
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $workspace]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['workspace_name'] = $workspace;
            $_SESSION['currency'] = 'KES';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'An account with that email address already exists.';
            } else {
                error_log('FleetCare registration failed: ' . $exception->getMessage());
                $error = 'Your account could not be created. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create your FleetCare EA account</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="app-extra.css">
</head>
<body class="auth-page">
  <main class="auth-layout">
    <section class="auth-story"><a class="brand auth-brand" href="login.php"><span class="brand-mark">FC</span><span class="brand-name">fleetcare<span>EA</span></span></a><div class="auth-story-copy"><span class="auth-asterisk">✳</span><p class="eyebrow">YOUR FLEET, MOVING FORWARD</p><h1>Good care keeps<br>business moving.</h1><p>Bring vehicles, maintenance, fuel and renewals together in one calm, clear workspace.</p><div class="auth-story-note"><span class="auth-note-check">✓</span>Set up your fleet in a few minutes.</div></div><span class="auth-story-footer">MADE FOR EAST AFRICAN FLEETS</span></section>
    <section class="auth-main"><div class="auth-form-wrap"><p class="eyebrow">GET STARTED</p><h2>Create your account</h2><p class="auth-subtitle">Set up your workspace and start looking after your fleet.</p>
      <?php if ($error !== ''): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form class="auth-form" method="post" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Your name<input name="name" autocomplete="name" required maxlength="100" value="<?= e($name) ?>" placeholder="e.g. Alex Mwangi"></label>
        <label>Work email<input type="email" name="email" autocomplete="email" required maxlength="254" value="<?= e($email) ?>" placeholder="you@company.com"></label>
        <label>Business or workspace name<input name="workspace_name" required maxlength="120" value="<?= e($workspace) ?>" placeholder="e.g. Kijani Logistics"></label>
        <label>Password <span class="label-hint">At least 10 characters</span><input type="password" name="password" autocomplete="new-password" required minlength="10" placeholder="Create a strong password"></label>
        <button class="button button-primary auth-submit" type="submit">Create account <svg class="icon"><use href="#i-arrow"/></svg></button>
      </form>
      <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
      <p class="auth-legal">By creating an account, your fleet records will be stored in your local FleetCare database.</p>
    </div></section>
  </main>
</body>
</html>
