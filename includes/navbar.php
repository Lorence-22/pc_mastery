<header class="topbar">
  <button class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="fa fa-bars"></i></button>
  <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
  <div class="ms-auto d-flex align-items-center gap-3">
    <?php if ($user['role'] === 'student'): $nc = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $nc->execute([$user['id']]); $nc = (int)$nc->fetchColumn(); ?>
      <a href="<?= url('student/notifications.php') ?>" class="position-relative text-secondary fs-5"><i class="fa fa-bell"></i>
      <?php if ($nc): ?><span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size:.6rem"><?= $nc ?></span><?php endif; ?></a>
    <?php endif; ?>
    <div class="text-end d-none d-sm-block lh-sm">
      <div class="fw-semibold"><?= e($user['full_name']) ?></div>
      <small class="text-muted text-capitalize"><?= e($user['role']) ?></small>
    </div>
    <?php if (!empty($user['picture'])): ?>
      <img class="avatar" src="<?= url('uploads/profiles/' . e($user['picture'])) ?>" alt="">
    <?php else: ?>
      <span class="avatar avatar-ph"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span>
    <?php endif; ?>
  </div>
</header>
