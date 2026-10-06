<?php
// =========================================================================
// ឯកសារ: login.php
// គោលបំណង: ផ្ទៀងផ្ទាត់ការចូលប្រើប្រាស់ និងផ្ទុកសិទ្ធិ Permissions
// =========================================================================

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
        $_SESSION['user_id']          = $user['id'];
        $_SESSION['user_name']        = $user['full_name'] ?: $user['username'];
        $_SESSION['user_role']        = $user['role'];
        $_SESSION['user_permissions'] = $user['permissions'] ?? '["pos"]';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចូលប្រព័ន្ធ - Stock POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Kantumruy Pro', sans-serif; background: #0f172a; }</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="card p-4 rounded-4 shadow-lg" style="width: 380px;">
    <div class="text-center mb-3">
        <h3 class="fw-bold text-primary mb-1">Stock POS</h3>
        <p class="text-muted small">ចូលប្រើប្រាស់ប្រព័ន្ធដេប៉ូគ្រឿងសំណង់</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">ឈ្មោះគណនី (Username)</label>
            <input type="text" name="username" class="form-control" required autofocus placeholder="admin">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">ពាក្យសម្ងាត់ (Password)</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ចូលប្រព័ន្ធ</button>
    </form>
</div>
</body>
</html>
