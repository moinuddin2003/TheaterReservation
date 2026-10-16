

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminShell', () => ({
    collapsed: localStorage.getItem('cinebook-admin-sidebar-collapsed') === 'true',
    theme: localStorage.getItem('cinebook-admin-theme') === 'light' ? 'light' : 'dark',
    mobileOpen: false,
    userMenuOpen: false,
    init() {
        this.$watch('collapsed', (value) => {
            localStorage.setItem('cinebook-admin-sidebar-collapsed', String(value));
        });
        this.$nextTick(() => {
            const savedScrollTop = Number(localStorage.getItem('cinebook-admin-sidebar-scroll') || 0);

            this.$refs.sidebarNavigation.scrollTop = savedScrollTop;
        });
    },
    saveSidebarScroll(scrollTop) {
        localStorage.setItem('cinebook-admin-sidebar-scroll', String(scrollTop));
    },
    toggleSidebar() {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            this.collapsed = !this.collapsed;
            return;
        }

        this.mobileOpen = !this.mobileOpen;
    },
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('cinebook-admin-theme', this.theme);
    },
}));

Alpine.data('adminSearch', () => ({}));

Alpine.directive('trap', (element, { expression }, { evaluate, cleanup }) => {
    let previousFocus = null;
    let wasActive = false;

    const focusableElements = () => [...element.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    )].filter((item) => item.offsetParent !== null);

    const sync = () => {
        const active = Boolean(evaluate(expression));
        if (active && !wasActive) {
            previousFocus = document.activeElement;
            requestAnimationFrame(() => focusableElements()[0]?.focus());
        } else if (!active && wasActive && previousFocus instanceof HTMLElement) {
            previousFocus.focus();
        }
        wasActive = active;
    };

    Alpine.effect(sync);
    const keepFocusInside = (event) => {
        if (!wasActive || event.key !== 'Tab') return;
        const focusable = focusableElements();
        if (!focusable.length) {
            event.preventDefault();
            element.focus();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };
    element.addEventListener('keydown', keepFocusInside);
    cleanup(() => {
        element.removeEventListener('keydown', keepFocusInside);
        if (previousFocus instanceof HTMLElement) previousFocus.focus();
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        if (button instanceof HTMLButtonElement && !button.querySelector('.admin-loading-indicator')) {
            const spinner = document.createElement('span');
            spinner.className = 'admin-loading-indicator';
            spinner.setAttribute('aria-hidden', 'true');
            button.prepend(spinner);
        }
    });
});

Alpine.start();
