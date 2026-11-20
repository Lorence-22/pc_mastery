<?php
// Expects: $user (array), $pageTitle (string), $activeNav (string)
$flash = pull_flash();
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($pageTitle ?? 'Dashboard') ?> | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head><body class="app-body">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="app-main">
<?php include __DIR__ . '/navbar.php'; ?>
<main class="app-content">
