{{-- Live search: applies the search shortly after typing stops, then puts the cursor back in the box. --}}
<script>
    (() => {
        const input = document.getElementById('roster-search');
        if (!input) return;

        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => input.form.requestSubmit(), 400);
        });

        if (input.value) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    })();
</script>
