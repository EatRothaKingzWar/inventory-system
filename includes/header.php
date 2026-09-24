<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

require_login();
$page_title = $page_title ?? 'Inventory System';
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kantumruy Pro', sans-serif; background-color: #f8fafc; }
        .sidebar { min-height: 100vh; background-color: #0f172a; color: #fff; width: 250px; }
        .sidebar a { color: #94a3b8; text-decoration: none; padding: 10px 14px; display: block; border-radius: 8px; margin-bottom: 2px; font-size: 14px; }
        .sidebar a:hover { background-color: #1e293b; color: #fff; }
        .live-clock-badge {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 5px 14px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <!-- Top Navbar បំពាក់នាឡិកា Live Clock -->
        <nav class="navbar bg-white px-4 py-2 border-bottom justify-content-between shadow-sm">
            <h5 class="mb-0 fw-bold text-dark"><?= e($page_title) ?></h5>
            
            <div class="d-flex align-items-center gap-3">
                <!-- នាឡិកាដើរ Live Real-time (Asia/Phnom_Penh) -->
                <div class="live-clock-badge d-flex align-items-center text-secondary shadow-none">
                    <i class="fa fa-clock text-primary me-2"></i>
                    <span id="live-clock-time" class="fw-bold text-dark font-monospace">--:--:--</span>
                    <span class="mx-2 text-muted">|</span>
                    <i class="fa fa-calendar-alt text-primary me-1"></i>
                    <span class="small"><?= date('d/m/Y') ?></span>
                </div>

                <!-- គណនី Profile -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-3 py-1" data-bs-toggle="dropdown">
                        <i class="fa fa-user-circle me-1 text-primary"></i> <?= e($_SESSION['user_name'] ?? 'User') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item text-danger" href="/logout.php"><i class="fa fa-sign-out-alt me-1"></i> ចាកចេញ</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="p-4 flex-grow-1">
            <?php display_flash(); ?>

<script>
// JavaScript សម្រាប់ដំណើរការនាឡិកា Live Clock តាមម៉ោងកម្ពុជា
function runLiveClock() {
    const options = {
        timeZone: 'Asia/Phnom_Penh',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    const now = new Date();
    const timeFormatted = new Intl.DateTimeFormat('en-US', options).format(now);
    const clockElem = document.getElementById('live-clock-time');
    if (clockElem) {
        clockElem.innerText = timeFormatted;
    }
}
setInterval(runLiveClock, 1000);
runLiveClock();
</script>
