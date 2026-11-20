<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? '') ?> | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet"></head>
<body class="auth-body"><div class="auth-card <?= $wide ?? false ? 'auth-wide' : '' ?>">
<a href="<?= url() ?>" class="auth-brand"><i class="fa fa-microchip"></i> PC Mastery</a>
<?php if ($f = pull_flash()): ?><div class="alert alert-<?= $f["type"]==="success"?"success":"info" ?> py-2"><?= e($f["msg"]) ?></div><?php endif; ?>
