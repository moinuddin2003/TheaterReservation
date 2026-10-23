{{-- Keeps the token box focused so a keyboard-mode USB scanner always types into it. --}}
<script>
    (() => {
        const input = document.querySelector('[data-scan-input]');
        if (!input) return;

        const refocus = () => input.focus();
        refocus();

        // Clicking empty space must not steal focus; the show picker, links and buttons may keep it.
        input.addEventListener('blur', () => setTimeout(() => {
            const active = document.activeElement;
            if (!active || active === document.body) refocus();
        }, 0));
        window.addEventListener('focus', refocus);

        // Switching show re-loads the screen for that show.
        const picker = document.querySelector('[data-auto-submit]');
        picker?.addEventListener('change', () => picker.form.requestSubmit());
    })();
</script>
