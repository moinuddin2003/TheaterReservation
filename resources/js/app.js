

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

Alpine.data('movieActions', () => ({
    menu: null,
    trigger: null,
    reposition: null,
    closeListener: null,
    open(menu, trigger) {
        if (menu.matches(':popover-open')) {
            menu.hidePopover();
            return;
        }

        this.menu = menu;
        this.trigger = trigger;
        this.reposition = () => this.positionMenu();
        this.closeListener = (event) => {
            if (event.newState === 'closed') {
                this.stopTracking();
            }
        };

        menu.addEventListener('toggle', this.closeListener);
        menu.showPopover();
        this.positionMenu();
        window.addEventListener('scroll', this.reposition, true);
        window.addEventListener('resize', this.reposition);
    },
    positionMenu() {
        if (!this.menu || !this.trigger || !this.menu.matches(':popover-open')) {
            return;
        }

        const triggerRect = this.trigger.getBoundingClientRect();
        const menuRect = this.menu.getBoundingClientRect();
        const gap = 8;
        const top = triggerRect.bottom + menuRect.height + gap <= window.innerHeight - gap
            ? triggerRect.bottom + gap
            : Math.max(gap, triggerRect.top - menuRect.height - gap);
        const left = Math.max(
            gap,
            Math.min(triggerRect.right - menuRect.width, window.innerWidth - menuRect.width - gap),
        );

        this.menu.style.top = `${top}px`;
        this.menu.style.left = `${left}px`;
    },
    stopTracking() {
        if (this.reposition) {
            window.removeEventListener('scroll', this.reposition, true);
            window.removeEventListener('resize', this.reposition);
        }

        if (this.menu && this.closeListener) {
            this.menu.removeEventListener('toggle', this.closeListener);
        }

        this.menu = null;
        this.trigger = null;
        this.reposition = null;
        this.closeListener = null;
    },
}));

Alpine.data('deleteConfirmationToast', () => ({
    open: false,
    formId: null,
    title: '',
    ask({ formId, title }) {
        this.formId = formId;
        this.title = title;
        this.open = true;
    },
    dismiss() {
        this.open = false;
        this.formId = null;
        this.title = '';
    },
    confirm() {
        const form = document.getElementById(this.formId);
        if (!(form instanceof HTMLFormElement)) {
            throw new Error(`Movie delete form "${this.formId}" was not found.`);
        }

        form.requestSubmit();
        this.dismiss();
    },
}));

Alpine.start();