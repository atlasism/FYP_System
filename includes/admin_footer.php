        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/fyp_system/assets/js/user-preferences.js?v=13"></script>
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
