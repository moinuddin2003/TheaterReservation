

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const storedTheme = window.localStorage.getItem('cinebook-theme');
    const initialTheme = storedTheme === 'light' ? 'light' : 'dark';
    const themeButtons = document.querySelectorAll('[data-theme-toggle]');

    document.documentElement.dataset.theme = initialTheme;

    const setTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        document.documentElement.classList.toggle('dark', theme === 'dark');
        window.localStorage.setItem('cinebook-theme', theme);

        themeButtons.forEach((button) => {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            button.setAttribute('aria-label', `Switch to ${nextTheme} mode`);
            button.querySelector('[data-theme-icon]').textContent = theme === 'dark' ? '☼' : '☾';
            button.querySelector('.theme-toggle__label').textContent = `${nextTheme[0].toUpperCase()}${nextTheme.slice(1)} mode`;
        });
    };

    setTheme(initialTheme);

    themeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    });

    const dateButtons = document.querySelectorAll('[data-day-button]');
    const datePanels = document.querySelectorAll('[data-day-panel]');

    dateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            dateButtons.forEach((dateButton) => {
                const isActive = dateButton === button;
                dateButton.classList.toggle('is-active', isActive);
                dateButton.setAttribute('aria-selected', String(isActive));
            });

            datePanels.forEach((panel) => {
                panel.hidden = panel.dataset.dayPanel !== button.dataset.dayButton;
            });
        });
    });

    const homeDateButtons = document.querySelectorAll('[data-home-date]');

    homeDateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            homeDateButtons.forEach((dateButton) => {
                const isActive = dateButton === button;
                dateButton.classList.toggle('is-active', isActive);
                dateButton.setAttribute('aria-selected', String(isActive));
            });

            document.querySelectorAll('[data-home-schedule]').forEach((schedule) => {
                schedule.hidden = schedule.dataset.homeSchedule !== button.dataset.homeDate;
            });
        });
    });

    const catalogCards = [...document.querySelectorAll('[data-movie-card]')];

    if (catalogCards.length > 0) {
        const searchInput = document.querySelector('[data-movie-search]');
        const genreSelect = document.querySelector('[data-movie-genre]');
        const statusTabs = [...document.querySelectorAll('[data-catalog-status]')];
        const emptyMessage = document.querySelector('[data-catalog-empty]');
        const summary = document.querySelector('[data-catalog-summary]');
        const resultCount = document.querySelector('[data-catalog-count]');
        const query = new URLSearchParams(window.location.search);
        let selectedStatus = query.get('status') === 'coming-soon' ? 'coming-soon' : 'now-showing';

        if (query.has('genre')) {
            genreSelect.value = query.get('genre').toLowerCase();
        }

        const filterMovies = () => {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedGenre = genreSelect.value;
            let visibleCount = 0;

            catalogCards.forEach((card) => {
                const matchesStatus = card.dataset.movieStatus === selectedStatus;
                const matchesTitle = card.dataset.movieTitle.includes(searchTerm);
                const matchesGenre = !selectedGenre || card.dataset.movieGenres.split(' ').includes(selectedGenre);
                const isVisible = matchesStatus && matchesTitle && matchesGenre;

                card.hidden = !isVisible;
                visibleCount += Number(isVisible);
            });

            statusTabs.forEach((tab) => {
                const isActive = tab.dataset.catalogStatus === selectedStatus;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', String(isActive));
            });

            emptyMessage.hidden = visibleCount > 0;
            resultCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'movie' : 'movies'}`;
            summary.textContent = `${visibleCount} ${selectedStatus === 'now-showing' ? 'movies now showing' : 'movies coming soon'}`;
        };

        statusTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                selectedStatus = tab.dataset.catalogStatus;
                filterMovies();
            });
        });

        searchInput.addEventListener('input', filterMovies);
        genreSelect.addEventListener('change', filterMovies);
        filterMovies();

    }

    const seatButtons = [...document.querySelectorAll('[data-seat]')];

    if (seatButtons.length > 0) {
        const seatMap = document.querySelector('.seat-map-card');
        const selectionCard = document.querySelector('.selection-card');
        const prices = {
            standard: Number(seatMap.dataset.standardPrice),
            premium: Number(seatMap.dataset.premiumPrice),
        };
        const maxSelection = Number(selectionCard.dataset.maxSelection);
        const selectionList = document.querySelector('[data-selection-list]');
        const seatCount = document.querySelector('[data-seat-count]');
        const unitPrice = document.querySelector('[data-unit-price]');
        const totalPrice = document.querySelector('[data-total-price]');
        const holdCount = document.querySelector('[data-hold-count]');
        const holdPrice = document.querySelector('[data-hold-price]');
        const availableCount = document.querySelector('[data-available-count]');

        const updateSelection = () => {
            const selectedButtons = seatButtons.filter((seat) => seat.getAttribute('aria-pressed') === 'true');
            const selectedLabels = selectedButtons.map((seat) => {
                return `${seat.dataset.seatLabel} · ${seat.dataset.seatTypeLabel}`;
            });
            const selectionTotal = selectedButtons.reduce((total, seat) => {
                return total + prices[seat.dataset.seatKind];
            }, 0);
            const averagePrice = selectedButtons.length > 0 ? selectionTotal / selectedButtons.length : 0;
            const availableSeats = seatButtons.filter((seat) => {
                return seat.dataset.seatKind !== 'unavailable' && !seat.disabled && seat.getAttribute('aria-pressed') !== 'true';
            }).length;
            const holdButton = document.querySelector('[data-hold-submit]');

            selectionList.textContent = selectedLabels.length > 0 ? selectedLabels.join(' · ') : 'No seats selected';
            seatCount.textContent = String(selectedButtons.length);
            unitPrice.textContent = String(averagePrice.toFixed(2).replace(/\.?0+$/, ''));
            totalPrice.textContent = String(selectionTotal);
            holdCount.textContent = String(selectedButtons.length);
            holdPrice.textContent = String(selectionTotal);
            availableCount.textContent = String(availableSeats);
            holdButton.disabled = selectedButtons.length === 0;
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

        this.mobileOpen = !this.mobileOpen;
    },
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('cinebook-admin-theme', this.theme);
    },
}));

Alpine.data('movieActions', () => ({
    open(menu, trigger) {
        if (menu.matches(':popover-open')) {
            menu.hidePopover();
            return;
        }

        menu.showPopover();

        const triggerRect = trigger.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        const gap = 8;
        const top = triggerRect.bottom + menuRect.height + gap <= window.innerHeight - gap
            ? triggerRect.bottom + gap
            : Math.max(gap, triggerRect.top - menuRect.height - gap);
        const left = Math.max(
            gap,
            Math.min(triggerRect.right - menuRect.width, window.innerWidth - menuRect.width - gap),
        );

        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
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
