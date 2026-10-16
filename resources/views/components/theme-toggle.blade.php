@props(['floating' => false])

<button
    {{ $attributes->class(['theme-toggle', 'theme-toggle--floating' => $floating]) }}
    type="button"
    data-theme-toggle
    aria-label="Switch to light mode"
>
    <span data-theme-icon aria-hidden="true">☼</span>
    <span class="theme-toggle__label">Light mode</span>
</button>
