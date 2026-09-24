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
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kantumruy Pro', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #1e293b; color: #fff; width: 250px; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 10px 14px; display: block; border-radius: 6px; margin-bottom: 2px; }
        .sidebar a:hover { background-color: #334155; color: #fff; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <nav class="navbar bg-white px-4 py-2 border-bottom justify-content-between">
            <h5 class="mb-0 fw-bold text-secondary"><?= e($page_title) ?></h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa fa-user-circle me-1"></i> <?= e($_SESSION['user_name'] ?? 'User') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="/logout.php"><i class="fa fa-sign-out-alt me-1"></i> ចាកចេញ</a></li>
                </ul>
            </div>
        </nav>
        <div class="p-4 flex-grow-1">
            <?php display_flash(); ?>
