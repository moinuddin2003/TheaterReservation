<script>
    (() => {
        const theme = window.localStorage.getItem('cinebook-theme') === 'light' ? 'light' : 'dark';

        document.documentElement.dataset.theme = theme;
        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();
</script>
