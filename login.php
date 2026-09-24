<?php
// login.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db_query($pdo, "SELECT * FROM users WHERE username = ?", [$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
        $_SESSION['user_role'] = $user['role'];
        redirect('/index.php');
    } else {
        $error = 'ឈ្មោះគណនី ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ!';
    }
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ចូលប្រព័ន្ធ - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Kantumruy Pro', sans-serif; background: #0f172a; }</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="card p-4 rounded-4 shadow-lg" style="width: 380px;">
    <h4 class="text-center fw-bold text-primary mb-3">ចូលប្រើប្រព័ន្ធ</h4>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label small">ឈ្មោះគណនី</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label small">ពាក្យសម្ងាត់</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ចូលប្រព័ន្ធ</button>
    </form>
</div>
</body>
</html>
