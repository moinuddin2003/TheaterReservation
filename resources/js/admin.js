import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminShell', () => ({
    collapsed: localStorage.getItem('cinebook-admin-sidebar-collapsed') === 'true',
    theme: localStorage.getItem('cinebook-admin-theme') === 'light' ? 'light' : 'dark',
    mobileOpen: false,
    userMenuOpen: false,
    init() {
        this.$watch('collapsed', value => {
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
        this.closeListener = event => {
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

Alpine.data('adminModal', (initiallyOpen = false) => ({
    show: initiallyOpen,
    previouslyFocused: null,
    focusables() {
        const selector = 'a[href], button:not([disabled]), input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]';

        return [...this.$el.querySelectorAll(selector)].filter(element =>
            element.tabIndex >= 0 && element.getClientRects().length > 0
        );
    },
    init() {
        this.$watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
                setTimeout(() => this.focusables()[0]?.focus(), 50);
            } else {
                document.body.classList.remove('overflow-y-hidden');
                setTimeout(() => this.previouslyFocused?.isConnected && this.previouslyFocused.focus(), 0);
            }
        });
    },
    open() {
        this.previouslyFocused = document.activeElement;
        this.show = true;
    },
    close() {
        this.show = false;
    },
    trapFocus(event) {
        const items = this.focusables();
        if (items.length === 0) {
            event.preventDefault();
            this.$refs.panel.focus();
            return;
        }

        const firstItem = items[0];
        const lastItem = items[items.length - 1];

        if (event.shiftKey && document.activeElement === firstItem) {
            event.preventDefault();
            lastItem.focus();
        } else if (!event.shiftKey && document.activeElement === lastItem) {
            event.preventDefault();
            firstItem.focus();
        }
    },
}));

Alpine.data('bookingActionModals', () => ({
    alsoRefund: false,
    busy: { resend: false, refund: false, void: false },
    currency: 'USD',
    errors: {},
    refundableCents: 0,
    refundAmount: '',
    refundType: 'full',
    stripeConfigured: false,
    ticket: { url: '', number: '', seat: '', showtime: '' },
    toast: '',
    toastTone: 'success',
    toastTimeout: null,
    setTicket(ticket) {
        this.ticket = ticket;
    },
    setRefundType(type) {
        this.refundType = type;
        this.refundAmount = type === 'full'
            ? (this.refundableCents / 100).toFixed(2)
            : '';
    },
    errorMessages(action, field = null) {
        const actionErrors = this.errors[action] ?? {};

        return field
            ? actionErrors[field] ?? []
            : Object.values(actionErrors).flat();
    },
    showToast(message, tone = 'success') {
        this.toast = message;
        this.toastTone = tone;
        window.clearTimeout(this.toastTimeout);
        this.toastTimeout = window.setTimeout(() => {
            this.toast = '';
        }, 4000);
    },
    async submit(event) {
        const form = event.currentTarget;
        const action = form.dataset.actionKey;

        if (!action || this.busy[action]) {
            return;
        }

        this.busy[action] = true;
        this.errors[action] = {};

        try {
            const response = await fetch(form.action, {
                method: form.method,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });
            const result = await response.json();

            if (response.status === 422) {
                this.errors[action] = result.errors ?? {};
                const firstError = Object.values(this.errors[action]).flat()[0];
                this.showToast(firstError ?? 'Please review the highlighted fields.', 'danger');
                return;
            }

            if (!response.ok) {
                throw new Error(result.message ?? `The ${action} action failed.`);
            }

            if (action === 'resend') {
                this.showToast(result.message ?? 'Confirmation email sent.');
                return;
            }

            if (action === 'refund' || action === 'void') {
                window.location.reload();
                return;
            }
        } catch (error) {
            this.showToast(error instanceof Error ? error.message : 'The action could not be completed.', 'danger');
        } finally {
            this.busy[action] = false;
        }
    },
}));
