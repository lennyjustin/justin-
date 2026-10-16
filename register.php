<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Create your account';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($name) < 2 || strlen($name) > 100) {
        $error = 'Enter a name between 2 and 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Use a password with at least 10 characters.';
    } else {
        $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
        try {
            $statement->execute(['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => inserted_id(db(), 'users'), 'name' => $name, 'email' => $email];
            set_flash('Welcome to Construction Estimator. Your account is ready.');
            redirect('dashboard.php');
        } catch (PDOException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                $error = 'An account with that email already exists.';
            } else {
                throw $exception;
            }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout"><div class="auth-intro"><div class="eyebrow">GET STARTED FOR FREE</div><h1>Your plans.<br><span>All in one place.</span></h1><p>Save estimates, revisit project budgets and add the material rates you get from suppliers.</p><ul><li>Save and compare project estimates</li><li>Set your own material price overrides</li><li>Print a clear itemized summary</li></ul></div><div class="auth-card"><span class="step-kicker">CREATE AN ACCOUNT</span><h2>Join Construction Estimator</h2><p>It only takes a minute.</p><?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" class="stack-form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Your name<input type="text" name="name" autocomplete="name" required maxlength="100" value="<?= e($_POST['name'] ?? '') ?>"></label>
<label>Email address<input type="email" name="email" autocomplete="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"></label>
<label>Password <span class="optional">At least 10 characters</span><input type="password" name="password" autocomplete="new-password" minlength="10" required></label>
<button class="button button-full" type="submit">Create account <span>↗</span></button></form><p class="auth-switch">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
