        </section>
    </main>
</div>
<footer class="bg-black border-top text-center py-3 fixed-bottom" style="height: var(--footer-height); box-sizing: border-box; margin: 0; z-index: 1050;">
    <div class="container-fluid d-flex align-items-center justify-content-center h-100">
        <small class="text-white fw-semibold">
            &copy; <?= date('Y'); ?> <strong class="text-warning">SPInE FYP</strong> | Politeknik Besut, Terengganu. Hak Cipta Terpelihara.
        </small>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/fyp_system/assets/js/user-preferences.js?v=21"></script>
<script>
(function () {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.querySelector('.admin-main');
    const toggle = document.getElementById('adminSidebarToggle');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                if (main) main.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('open');
            }
        });
    }
})();
</script>
</body>
</html>
