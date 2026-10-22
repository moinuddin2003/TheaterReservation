<script>
    (() => {
        const savedTheme = localStorage.getItem('cinebook-theme');
        const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;

        document.documentElement.dataset.theme = savedTheme || (prefersLight ? 'light' : 'dark');
    })();
</script>
