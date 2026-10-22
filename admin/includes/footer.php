<footer class="py-4 my-4 border-top text-center text-muted fs-8">
    <div class="container">
        &copy; <?= date('Y') ?> <strong class="text-body">IDVISION Eyewear</strong>. Admin Management System.
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnToggle     = document.getElementById('btnThemeToggle');
        const themeIconMenu = document.getElementById('themeIconMenu');
        const htmlTag       = document.documentElement;

        function updateUI(theme) {
            if (theme === 'dark') {
                if (themeIconMenu) themeIconMenu.className = 'bi bi-sun-fill text-warning';
            } else {
                if (themeIconMenu) themeIconMenu.className = 'bi bi-moon-stars text-muted';
            }
        }

        const currentTheme = htmlTag.getAttribute('data-bs-theme') || 'light';
        updateUI(currentTheme);

        if (btnToggle) {
            btnToggle.addEventListener('click', (e) => {
                e.stopPropagation(); // Menjaga dropdown tidak langsung tertutup saat diklik
                const activeTheme = htmlTag.getAttribute('data-bs-theme');
                const newTheme    = activeTheme === 'dark' ? 'light' : 'dark';

                htmlTag.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateUI(newTheme);
            });
        }
    });
</script>
</body>
</html>