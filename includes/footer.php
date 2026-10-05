</div> <!-- Penutup <div class="p-4"> -->
    </div> <!-- Penutup #page-content -->
</div> <!-- Penutup #wrapper -->

<!-- FOOTER -->
<footer class="bg-black border-top text-center py-3 fixed-bottom" style="height: var(--footer-height); box-sizing: border-box; margin: 0; z-index: 1050;">
    <div class="container-fluid d-flex align-items-center justify-content-center h-100">
        <small class="text-white fw-semibold">
            &copy; <?= date('Y'); ?> <strong class="text-warning">SPInE FYP</strong> | Politeknik Besut, Terengganu. Hak Cipta Terpelihara.
        </small>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/fyp_system/assets/js/user-preferences.js?v=21"></script>

<!-- Script Toggle Sidebar -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
            });
        }
    });
</script>
</body>
</html>