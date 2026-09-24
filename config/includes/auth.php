<?php
// includes/auth.php
require_once __DIR__ . '/functions.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash('danger', 'សូមចូលប្រព័ន្ធជាមុនសិន!');
        redirect('/login.php');
    }
}

function require_role($roles = []) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user_role'] ?? 'staff', $roles)) {
        set_flash('danger', 'អ្នកគ្មានសិទ្ធិចូលទំព័រនេះទេ!');
        redirect('/index.php');
    }
}