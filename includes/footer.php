</main>
<footer class="app-footer">&copy; <?= date('Y') ?> <?= e(setting('institution_name', APP_NAME)) ?> &middot; PC Mastery</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>window.APP={base:"<?= BASE_URL ?>",csrf:"<?= e(csrf_token()) ?>"};</script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($flash)): ?>
<script>Swal.fire({icon:<?= json_encode($flash['type']) ?>,title:<?= json_encode($flash['msg']) ?>,timer:3500,showConfirmButton:false,toast:true,position:'top-end'});</script>
<?php endif; ?>
<?php if ($m = pull_modal()): ?><script>Swal.fire({title:<?= json_encode($m['title']) ?>,html:<?= json_encode($m['html']) ?>,icon:'info'});</script><?php endif; ?>
<?= $extraScripts ?? '' ?>
</body></html>
