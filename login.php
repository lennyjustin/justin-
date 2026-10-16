<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Sign in';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        set_flash('Welcome back, ' . $user['name'] . '.');
        redirect('dashboard.php');
    }
    $error = 'Email or password was not recognized.';
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout login-layout"><div class="auth-intro"><div class="eyebrow">WELCOME BACK</div><h1>Pick up where<br><span>you left off.</span></h1><p>Your saved projects and material rates are ready when you are.</p></div><div class="auth-card"><span class="step-kicker">SIGN IN</span><h2>Welcome back</h2><p>Enter your account details below.</p><?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" class="stack-form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Email address<input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button button-full" type="submit">Sign in <span>↗</span></button></form><p class="auth-switch">New to Construction Estimator? <a href="<?= e(url('register.php')) ?>">Create an account</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
