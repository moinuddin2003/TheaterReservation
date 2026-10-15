

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

Alpine.start();
