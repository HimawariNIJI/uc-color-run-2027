/* UC Fair frontend template. Sesuaikan teks dan tanggal setelah data resmi tersedia. */
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const navigation = document.querySelector('[data-navigation]');
    const dropdowns = Array.from(document.querySelectorAll('[data-dropdown]'));
    const mobileNavQuery = window.matchMedia('(max-width: 980px)');
    let wasMobileViewport = mobileNavQuery.matches;

    const closeDropdown = (dropdown, { returnFocus = false } = {}) => {
        const toggle = dropdown?.querySelector('[data-dropdown-toggle]');
        if (!dropdown || !toggle) return;
        dropdown.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggle.focus();
    };

    const closeAllDropdowns = () => dropdowns.forEach((dropdown) => closeDropdown(dropdown));

    const closeMobileMenu = () => {
        navigation?.classList.remove('is-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Buka menu navigasi');
        document.body.classList.remove('menu-open');
        closeAllDropdowns();
    };

    // Keep the navigation state correct when the window crosses the mobile breakpoint.
    window.addEventListener('resize', () => {
        const isMobileViewport = mobileNavQuery.matches;
        if (isMobileViewport !== wasMobileViewport) {
            closeMobileMenu();
            wasMobileViewport = isMobileViewport;
        }
    });

    if (menuToggle && navigation) {
        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Buka menu navigasi' : 'Tutup menu navigasi');
            navigation.classList.toggle('is-open', !isOpen);
            document.body.classList.toggle('menu-open', !isOpen);
            if (isOpen) closeAllDropdowns();
        });

        navigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => closeMobileMenu());
        });
    }

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        if (!toggle) return;

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';
            closeAllDropdowns();
            if (shouldOpen) {
                dropdown.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });

        dropdown.querySelectorAll('.dropdown-panel a').forEach((link) => {
            link.addEventListener('click', () => {
                closeDropdown(dropdown);
                // Also close the mobile navigation when a nested item is selected.
                if (mobileNavQuery.matches) closeMobileMenu();
            });
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dropdown]')) closeAllDropdowns();
        if (navigation?.classList.contains('is-open') &&
            !event.target.closest('[data-navigation]') &&
            !event.target.closest('[data-menu-toggle]')) {
            closeMobileMenu();
        }
    });

    // Modal login hanya demo UI. Belum ada autentikasi PHP atau database.
    const loginModal = document.querySelector('[data-login-modal]');
    const openLoginButtons = document.querySelectorAll('[data-open-login]');
    const closeLoginButton = document.querySelector('[data-close-login]');
    const loginForm = document.querySelector('[data-login-form]');
    const loginMessage = document.querySelector('[data-login-message]');
    let lastLoginTrigger = null;

    const openLogin = (event) => {
        if (!loginModal) return;
        lastLoginTrigger = event?.currentTarget || document.activeElement;
        closeMobileMenu();
        loginModal.hidden = false;
        document.body.classList.add('modal-open');
        loginModal.querySelector('input')?.focus();
    };
    const closeLogin = () => {
        if (!loginModal || loginModal.hidden) return;
        loginModal.hidden = true;
        document.body.classList.remove('modal-open');
        lastLoginTrigger?.focus?.();
    };

    openLoginButtons.forEach((button) => button.addEventListener('click', openLogin));
    closeLoginButton?.addEventListener('click', closeLogin);
    loginModal?.addEventListener('click', (event) => {
        if (event.target === loginModal) closeLogin();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (loginModal && !loginModal.hidden) {
            closeLogin();
            return;
        }
        const openDropdown = dropdowns.find((dropdown) => dropdown.classList.contains('is-open'));
        if (openDropdown) {
            closeDropdown(openDropdown, { returnFocus: true });
            return;
        }
        if (navigation?.classList.contains('is-open')) {
            closeMobileMenu();
            menuToggle?.focus();
        }
    });
    loginForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (loginMessage) {
            loginMessage.textContent = 'Ini masih tampilan demo. Sistem login perlu dihubungkan ke backend.';
            loginMessage.classList.add('form-note-highlight');
        }
    });

    // Countdown nonaktif sampai tanggal acara resmi dimasukkan.
    // Contoh format: const EVENT_DATE = '2027-01-01T08:00:00+07:00';
    const EVENT_DATE = '';
    const countdown = document.querySelector('[data-countdown]');
    if (countdown) {
        const status = document.querySelector('[data-countdown-status]');
        const fields = {
            days: countdown.querySelector('[data-days]'),
            hours: countdown.querySelector('[data-hours]'),
            minutes: countdown.querySelector('[data-minutes]'),
            seconds: countdown.querySelector('[data-seconds]'),
        };
        const writeNumber = (element, value) => { if (element) element.textContent = String(value).padStart(2, '0'); };
        const updateCountdown = () => {
            if (!EVENT_DATE || Number.isNaN(new Date(EVENT_DATE).getTime())) {
                if (status) status.textContent = 'Tanggal acara segera diumumkan.';
                return;
            }
            const remaining = new Date(EVENT_DATE).getTime() - Date.now();
            if (remaining <= 0) {
                Object.values(fields).forEach((field) => writeNumber(field, 0));
                if (status) status.textContent = 'Hari acara telah tiba!';
                return;
            }
            writeNumber(fields.days, Math.floor(remaining / 86400000));
            writeNumber(fields.hours, Math.floor((remaining % 86400000) / 3600000));
            writeNumber(fields.minutes, Math.floor((remaining % 3600000) / 60000));
            writeNumber(fields.seconds, Math.floor((remaining % 60000) / 1000));
            if (status) status.textContent = 'Menuju hari acara UC Fair.';
        };
        updateCountdown();
        if (EVENT_DATE) window.setInterval(updateCountdown, 1000);
    }

    // Tombol demo video: beri instruksi bahwa blok ini dapat diganti dengan embed video.
    document.querySelector('[data-video-demo]')?.addEventListener('click', () => {
        window.alert('Placeholder video: ganti blok ini dengan embed YouTube atau video resmi acara.');
    });

    // Carousel logo sponsor sederhana.
    const sponsorTrack = document.querySelector('[data-sponsor-track]');
    const sponsorWindow = document.querySelector('.sponsor-window');
    const moveSponsors = (direction) => {
        if (!sponsorTrack || !sponsorWindow) return;
        const firstCard = sponsorTrack.querySelector('.sponsor-card');
        if (!firstCard) return;
        const gap = parseFloat(getComputedStyle(sponsorTrack).columnGap || getComputedStyle(sponsorTrack).gap) || 0;
        sponsorWindow.scrollBy({ left: direction * (firstCard.getBoundingClientRect().width + gap), behavior: 'smooth' });
    };
    document.querySelector('[data-sponsor-prev]')?.addEventListener('click', () => moveSponsors(-1));
    document.querySelector('[data-sponsor-next]')?.addEventListener('click', () => moveSponsors(1));

    const year = document.querySelector('[data-current-year]');
    if (year) year.textContent = String(new Date().getFullYear());
});
