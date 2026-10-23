import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

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
Alpine.start();

document.addEventListener("DOMContentLoaded", () => {
    const storedTheme = window.localStorage.getItem("cinebook-theme");
    const initialTheme = storedTheme === "light" ? "light" : "dark";
    const themeButtons = document.querySelectorAll("[data-theme-toggle]");

    document.documentElement.dataset.theme = initialTheme;

    const setTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        document.documentElement.classList.toggle("dark", theme === "dark");
        window.localStorage.setItem("cinebook-theme", theme);

        themeButtons.forEach((button) => {
            const nextTheme = theme === "dark" ? "light" : "dark";
            button.setAttribute("aria-label", `Switch to ${nextTheme} mode`);
            button.querySelector("[data-theme-icon]").textContent =
                theme === "dark" ? "☼" : "☾";
            button.querySelector(".theme-toggle__label").textContent =
                `${nextTheme[0].toUpperCase()}${nextTheme.slice(1)} mode`;
        });
    };

    setTheme(initialTheme);

    themeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setTheme(
                document.documentElement.dataset.theme === "dark"
                    ? "light"
                    : "dark",
            );
        });
    });

    const dateButtons = document.querySelectorAll("[data-day-button]");
    const datePanels = document.querySelectorAll("[data-day-panel]");

    dateButtons.forEach((button) => {
        button.addEventListener("click", () => {
            dateButtons.forEach((dateButton) => {
                const isActive = dateButton === button;
                dateButton.classList.toggle("is-active", isActive);
                dateButton.setAttribute("aria-selected", String(isActive));
            });

            datePanels.forEach((panel) => {
                panel.hidden =
                    panel.dataset.dayPanel !== button.dataset.dayButton;
            });
        });
    });

    const homeDateButtons = document.querySelectorAll("[data-home-date]");

    homeDateButtons.forEach((button) => {
        button.addEventListener("click", () => {
            homeDateButtons.forEach((dateButton) => {
                const isActive = dateButton === button;
                dateButton.classList.toggle("is-active", isActive);
                dateButton.setAttribute("aria-selected", String(isActive));
            });

            document
                .querySelectorAll("[data-home-schedule]")
                .forEach((schedule) => {
                    schedule.hidden =
                        schedule.dataset.homeSchedule !==
                        button.dataset.homeDate;
                });
        });
    });

    const catalogCards = [...document.querySelectorAll("[data-movie-card]")];
    const searchInput = document.querySelector("[data-movie-search]");
    const genreSelect = document.querySelector("[data-movie-genre]");

    if (catalogCards.length > 0 && searchInput && genreSelect) {
        const statusTabs = [
            ...document.querySelectorAll("[data-catalog-status]"),
        ];
        const emptyMessage = document.querySelector("[data-catalog-empty]");
        const summary = document.querySelector("[data-catalog-summary]");
        const resultCount = document.querySelector("[data-catalog-count]");
        const query = new URLSearchParams(window.location.search);
        let selectedStatus =
            query.get("status") === "coming-soon"
                ? "coming-soon"
                : "now-showing";

        if (query.has("genre")) {
            genreSelect.value = query.get("genre").toLowerCase();
        }

        const filterMovies = () => {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedGenre = genreSelect.value;
            let visibleCount = 0;

            catalogCards.forEach((card) => {
                const matchesStatus =
                    card.dataset.movieStatus === selectedStatus;
                const matchesTitle =
                    card.dataset.movieTitle.includes(searchTerm);
                const matchesGenre =
                    !selectedGenre ||
                    card.dataset.movieGenres.split(" ").includes(selectedGenre);
                const isVisible = matchesStatus && matchesTitle && matchesGenre;

                card.hidden = !isVisible;
                visibleCount += Number(isVisible);
            });

            statusTabs.forEach((tab) => {
                const isActive = tab.dataset.catalogStatus === selectedStatus;
                tab.classList.toggle("is-active", isActive);
                tab.setAttribute("aria-selected", String(isActive));
            });

            emptyMessage.hidden = visibleCount > 0;
            resultCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? "movie" : "movies"}`;
            summary.textContent = `${visibleCount} ${selectedStatus === "now-showing" ? "movies now showing" : "movies coming soon"}`;
        };

        statusTabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                selectedStatus = tab.dataset.catalogStatus;
                filterMovies();
            });
        });

        searchInput.addEventListener("input", filterMovies);
        genreSelect.addEventListener("change", filterMovies);
        filterMovies();

    }

    const seatButtons = [...document.querySelectorAll("[data-seat]")];

    if (seatButtons.length > 0) {
        const seatMap = document.querySelector(".seat-map-card");
        const selectionCard = document.querySelector(".selection-card");
        const prices = {
            standard: Number(seatMap.dataset.standardPrice),
            premium: Number(seatMap.dataset.premiumPrice),
        };
        const maxSelection = Number(selectionCard.dataset.maxSelection);
        const selectionList = document.querySelector("[data-selection-list]");
        const seatCount = document.querySelector("[data-seat-count]");
        const unitPrice = document.querySelector("[data-unit-price]");
        const totalPrice = document.querySelector("[data-total-price]");
        const holdCount = document.querySelector("[data-hold-count]");
        const holdPrice = document.querySelector("[data-hold-price]");
        const availableCount = document.querySelector("[data-available-count]");
        const holdForm = document.querySelector("[data-hold-form]");
        const holdInputs = document.querySelector("[data-hold-inputs]");
        const holdButton = document.querySelector("[data-hold-submit]");

        const updateSelection = () => {
            const selectedButtons = seatButtons.filter(
                (seat) => seat.getAttribute("aria-pressed") === "true",
            );
            const selectedLabels = selectedButtons.map((seat) => {
                return `${seat.dataset.seatLabel} · ${seat.dataset.seatTypeLabel}`;
            });
            const selectionTotal = selectedButtons.reduce((total, seat) => {
                return total + prices[seat.dataset.seatKind];
            }, 0);
            const averagePrice =
                selectedButtons.length > 0
                    ? selectionTotal / selectedButtons.length
                    : 0;
            const availableSeats = seatButtons.filter((seat) => {
                return (
                    seat.dataset.seatKind !== "unavailable" &&
                    !seat.disabled &&
                    seat.getAttribute("aria-pressed") !== "true"
                );
            }).length;
            selectionList.textContent =
                selectedLabels.length > 0
                    ? selectedLabels.join(" · ")
                    : "No seats selected";
            seatCount.textContent = String(selectedButtons.length);
            unitPrice.textContent = String(
                averagePrice.toFixed(2).replace(/\.?0+$/, ""),
            );
            totalPrice.textContent = String(selectionTotal);
            holdCount.textContent = String(selectedButtons.length);
            holdPrice.textContent = String(selectionTotal);
            availableCount.textContent = String(availableSeats);
            holdButton.disabled = selectedButtons.length === 0;
        };

        seatButtons.forEach((seat) => {
            seat.addEventListener("click", () => {
                const isSelected = seat.getAttribute("aria-pressed") === "true";
                const selectedCount = seatButtons.filter((selectedSeat) => {
                    return selectedSeat.getAttribute("aria-pressed") === "true";
                }).length;

                if (!isSelected && selectedCount >= maxSelection) {
                    showToast(
                        `You can select up to ${maxSelection} seats per booking.`,
                    );
                    return;
                }

                seat.setAttribute("aria-pressed", String(!isSelected));
                seat.classList.toggle("seat--selected", !isSelected);
                seat.classList.toggle(
                    "seat--premium",
                    seat.dataset.seatKind === "premium" && isSelected,
                );
                updateSelection();
            });
        });

        holdForm.addEventListener("submit", (event) => {
            const selectedButtons = seatButtons.filter(
                (seat) => seat.getAttribute("aria-pressed") === "true",
            );

            if (selectedButtons.length === 0) {
                event.preventDefault();
                return;
            }

            holdInputs.replaceChildren(
                ...selectedButtons.map((seat) => {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "seat_ids[]";
                    input.value = seat.dataset.seatId;
                    return input;
                }),
            );
            holdButton.disabled = true;
        });

    updateSelection();
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }
    const seatDateButtons = [...document.querySelectorAll("[data-seat-date]")];
    const timeButtons = [...document.querySelectorAll("[data-time-button]")];

    const selectShowtime = (button) => {
        timeButtons.forEach((timeButton) => {
            const isActive = timeButton === button;
            timeButton.classList.toggle("is-active", isActive);
            timeButton.setAttribute("aria-pressed", String(isActive));
        });

        document.querySelector("[data-screening-time]").textContent =
            button.dataset.timeButton;
        document.querySelector("[data-sales-close-summary]").textContent =
            button.dataset.salesClose;
    };

    const selectSeatDate = (button) => {
        seatDateButtons.forEach((dateButton) => {
            const isActive = dateButton === button;
            dateButton.classList.toggle("is-active", isActive);
            dateButton.setAttribute("aria-selected", String(isActive));
        });

        document.querySelectorAll("[data-time-panel]").forEach((panel) => {
            panel.hidden = panel.dataset.timePanel !== button.dataset.seatDate;
        });

        document.querySelector("[data-seat-date-display]").textContent =
            button.dataset.seatDateLabel;
        document.querySelector("[data-screening-date]").textContent =
            button.dataset.seatDateShort;
        document.querySelector("[data-screening-screen]").textContent =
            button.dataset.seatScreen;
        document.querySelector("[data-movie-screen]").textContent =
            button.dataset.seatScreen;
        document.querySelector("#seat-map-title").textContent =
            button.dataset.seatScreen;
        document
            .querySelector("[data-seat-map]")
            .setAttribute(
                "aria-label",
                `${button.dataset.seatScreen} seat map`,
            );

        const selectedPanel = [
            ...document.querySelectorAll("[data-time-panel]"),
        ].find((panel) => {
            return panel.dataset.timePanel === button.dataset.seatDate;
        });
        const firstTimeButton =
            selectedPanel.querySelector("[data-time-button]");

        if (!firstTimeButton) {
            timeButtons.forEach((timeButton) => {
                timeButton.classList.remove("is-active");
                timeButton.setAttribute("aria-pressed", "false");
            });
            document.querySelector("[data-screening-time]").textContent =
                "Unavailable";
            document.querySelector("[data-sales-close-summary]").textContent =
                "—";
            return;
        }

        selectShowtime(firstTimeButton);
    };

    seatDateButtons.forEach((button) => {
        button.addEventListener("click", () => selectSeatDate(button));
    });

    timeButtons.forEach((button) => {
        button.addEventListener("click", () => selectShowtime(button));
    });

    const query = new URLSearchParams(window.location.search);
    const requestedDateButton =
        seatDateButtons.find((button) => {
            return button.dataset.seatDate === query.get("date");
        }) ??
        seatDateButtons.find((button) =>
            button.classList.contains("is-active"),
        );

    if (requestedDateButton) {
        selectSeatDate(requestedDateButton);

        const selectedPanel = [
            ...document.querySelectorAll("[data-time-panel]"),
        ].find((panel) => {
            return (
                panel.dataset.timePanel === requestedDateButton.dataset.seatDate
            );
        });
        const requestedTimeButton = [
            ...selectedPanel.querySelectorAll("[data-time-button]"),
        ].find((button) => {
            return button.dataset.timeButton === query.get("time");
        });

        if (requestedTimeButton) {
            selectShowtime(requestedTimeButton);
        }
    }

    document.querySelectorAll("[data-hold-countdown]").forEach((countdown) => {
        const expiryTime = new Date(countdown.dataset.holdExpiresAt).getTime();

        const updateCountdown = () => {
            const remainingSeconds = Math.max(
                0,
                Math.floor((expiryTime - Date.now()) / 1000),
            );
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;

            countdown.textContent =
                remainingSeconds > 0
                    ? `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`
                    : "Hold expired";

            return remainingSeconds > 0;
        };

        if (updateCountdown()) {
            const interval = window.setInterval(() => {
                if (!updateCountdown()) {
                    window.clearInterval(interval);
                }
            }, 1000);
        }
    });

    document.querySelectorAll("[data-print-page]").forEach((button) => {
        button.addEventListener("click", () => window.print());
    });

    const toast = document.querySelector("[data-toast]");
    let toastTimeout;
    const showToast = (message) => {
        toast.textContent = message;
        toast.classList.add("is-visible");
        window.clearTimeout(toastTimeout);
        toastTimeout = window.setTimeout(
            () => toast.classList.remove("is-visible"),
            3200,
        );
    };

    document.querySelectorAll("[data-demo-message]").forEach((button) => {
        button.addEventListener("click", () => {
            showToast(button.dataset.demoMessage);
        });
    });
});
