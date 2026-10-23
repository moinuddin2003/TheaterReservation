

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

        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && (document.activeElement === first || ! this.$el.contains(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

Alpine.data('bookingActionModals', () => ({
    busy: {},
    errors: {},
    toast: '',
    toastTone: 'success',
    refundableCents: 0,
    refundAmount: '0.00',
    currency: 'USD',
    stripeConfigured: false,
    alsoRefund: false,
    refundType: 'full',
    ticket: {},
    errorMessages(key, field = null) {
        const errors = this.errors[key] || {};
        if (field) {
            return errors[field] || [];
        }

        return errors.form || [];
    },
    setTicket(ticket) {
        this.ticket = ticket;
        this.errors.void = {};
    },
    setRefundType(type) {
        this.refundType = type;
        if (type === 'full') {
            this.refundAmount = (this.refundableCents / 100).toFixed(2);
        }
    },
    async submit(event) {
        const form = event.currentTarget;
        const key = form.dataset.actionKey;
        if (!(form instanceof HTMLFormElement) || !key || this.busy[key]) {
            return;
        }

        this.busy[key] = true;
        this.errors[key] = {};

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();

            if (!response.ok) {
                this.errors[key] = payload.errors || {
                    form: [payload.message || 'The action could not be completed. Please try again.'],
                };
                if (key === 'resend') {
                    this.toastTone = 'danger';
                    this.toast = this.errorMessages(key).join(' ') || 'The confirmation email could not be sent.';
                }
                this.busy[key] = false;
                return;
            }

            this.toast = payload.message || 'Action completed.';
            this.toastTone = payload.error ? 'danger' : 'success';
            const modal = form.dataset.modal;
            if (modal) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: modal }));
            }
            window.setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            console.error('Booking action request failed.', error);
            this.errors[key] = {
                form: ['The request could not reach the server. Check your connection and try again.'],
            };
            if (key === 'resend') {
                this.toastTone = 'danger';
                this.toast = this.errorMessages(key).join(' ');
            }
            this.busy[key] = false;
        }
    },
}));

Alpine.start();

const seatButtons = [...document.querySelectorAll('[data-seat]')];

if (seatButtons.length > 0) {
    const seatMap = document.querySelector('.seat-map-card');
    const selectionCard = document.querySelector('.selection-card');
    const selectionList = document.querySelector('[data-selection-list]');
    const seatCount = document.querySelector('[data-seat-count]');
    const unitPrice = document.querySelector('[data-unit-price]');
    const totalPrice = document.querySelector('[data-total-price]');
    const holdCount = document.querySelector('[data-hold-count]');
    const holdPrice = document.querySelector('[data-hold-price]');
    const availableCount = document.querySelector('[data-available-count]');
    const holdButton = document.querySelector('[data-hold-submit]');
    const toast = document.querySelector('[data-toast]');

    if (
        !(seatMap instanceof HTMLElement)
        || !(selectionCard instanceof HTMLElement)
        || !(selectionList instanceof HTMLElement)
        || !(seatCount instanceof HTMLElement)
        || !(unitPrice instanceof HTMLElement)
        || !(totalPrice instanceof HTMLElement)
        || !(holdCount instanceof HTMLElement)
        || !(holdPrice instanceof HTMLElement)
        || !(availableCount instanceof HTMLElement)
        || !(holdButton instanceof HTMLButtonElement)
        || !(toast instanceof HTMLElement)
    ) {
        throw new Error('Seat selection UI is missing required elements.');
    }

    const prices = {
        standard: Number(seatMap.dataset.standardPrice),
        premium: Number(seatMap.dataset.premiumPrice),
    };
    const maxSelection = Number(selectionCard.dataset.maxSelection);
    let toastTimeout;

    const showToast = (message) => {
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimeout);
        toastTimeout = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
    };

    const updateSelection = () => {
        const selectedSeats = seatButtons.filter((seat) => seat.getAttribute('aria-pressed') === 'true');
        const selectedLabels = selectedSeats.map((seat) => {
            return `${seat.dataset.seatLabel} · ${seat.dataset.seatTypeLabel}`;
        });
        const selectionTotal = selectedSeats.reduce((total, seat) => {
            return total + prices[seat.dataset.seatKind];
        }, 0);
        const averagePrice = selectedSeats.length > 0 ? selectionTotal / selectedSeats.length : 0;
        const availableSeats = seatButtons.filter((seat) => {
            return !seat.disabled && seat.getAttribute('aria-pressed') !== 'true';
        }).length;

        selectionList.textContent = selectedLabels.length > 0 ? selectedLabels.join(' · ') : 'No seats selected';
        seatCount.textContent = String(selectedSeats.length);
        unitPrice.textContent = String(averagePrice.toFixed(2).replace(/\.?0+$/, ''));
        totalPrice.textContent = String(selectionTotal);
        holdCount.textContent = String(selectedSeats.length);
        holdPrice.textContent = String(selectionTotal);
        availableCount.textContent = String(availableSeats);
        holdButton.disabled = selectedSeats.length === 0;
    };

    seatButtons.forEach((seat) => {
        seat.addEventListener('click', () => {
            const isSelected = seat.getAttribute('aria-pressed') === 'true';
            const selectedCount = seatButtons.filter((selectedSeat) => {
                return selectedSeat.getAttribute('aria-pressed') === 'true';
            }).length;

            if (!isSelected && selectedCount >= maxSelection) {
                showToast(`You can select up to ${maxSelection} seats per booking.`);
                return;
            }

            seat.setAttribute('aria-pressed', String(!isSelected));
            seat.classList.toggle('seat--selected', !isSelected);
            updateSelection();
        });
    });

    updateSelection();
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const button = event.target.closest('[data-day-button]');

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const section = button.closest('.showtimes-section');
    const selectedDate = button.dataset.dayButton;

    if (!section || !selectedDate) {
        return;
    }

    section.querySelectorAll('[data-day-button]').forEach((dateButton) => {
        const isActive = dateButton === button;
        dateButton.classList.toggle('is-active', isActive);
        dateButton.setAttribute('aria-selected', String(isActive));
    });

    section.querySelectorAll('[data-day-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.dayPanel !== selectedDate;
    });
});