@props(['floating' => false])

<button
    type="button"
    class="theme-toggle{{ $floating ? ' theme-toggle--floating' : '' }}"
    data-theme-toggle
    aria-label="Toggle color theme"
>
    <span aria-hidden="true">◐</span>
</button>

<script>
    document.querySelectorAll('[data-theme-toggle]:not([data-theme-toggle-ready])').forEach((button) => {
        button.dataset.themeToggleReady = 'true';
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';

            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('cinebook-theme', nextTheme);
        });
    });
</script>
