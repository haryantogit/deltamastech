<script>
    document.addEventListener('DOMContentLoaded', () => {
        initSidebarObserver();
        initBreadcrumbObserver();
    });

    document.addEventListener('livewire:navigated', () => {
        applySidebarActiveState();
        applyBreadcrumbFix();
    });

    function initBreadcrumbObserver() {
        const topbar = document.querySelector('.fi-topbar');
        if (!topbar) {
            setTimeout(initBreadcrumbObserver, 500);
            return;
        }

        applyBreadcrumbFix();

        const observer = new MutationObserver(() => {
            applyBreadcrumbFix();
        });

        observer.observe(topbar, { childList: true, subtree: true });
    }

    function applyBreadcrumbFix() {
        const breadcrumbsList = document.querySelector('.fi-breadcrumbs-list');
        if (!breadcrumbsList) return;

        // Cek apakah item pertama sudah Beranda atau Dashboard
        const firstItem = breadcrumbsList.querySelector('.fi-breadcrumbs-item-label');
        if (!firstItem) return;

        const text = firstItem.textContent.trim();
        if (text === 'Beranda' || text === 'Dasbor' || text === 'Dashboard') return;

        // Hindari duplikasi jika script berjalan berkali-kali
        if (breadcrumbsList.querySelector('[data-breadcrumb-fix]')) return;

        const li = document.createElement('li');
        li.className = 'fi-breadcrumbs-item';
        li.setAttribute('data-breadcrumb-fix', 'true');
        li.innerHTML = `<a href="/admin" class="fi-breadcrumbs-item-label">Beranda</a>`;

        const existingFirstItem = breadcrumbsList.firstElementChild;
        if (existingFirstItem) {
            const separatorHtml = `
                <svg class="fi-icon fi-size-md fi-breadcrumbs-item-separator fi-ltr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"></path>
                </svg>
                <svg class="fi-icon fi-size-md fi-breadcrumbs-item-separator fi-rtl" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                    <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"></path>
                </svg>
            `;
            existingFirstItem.insertAdjacentHTML('afterbegin', separatorHtml);
        }

        breadcrumbsList.insertBefore(li, existingFirstItem);
    }

    function initSidebarObserver() {
        const sidebar = document.querySelector('.fi-sidebar-nav');
        if (!sidebar) {
            setTimeout(initSidebarObserver, 500);
            return;
        }

        applySidebarActiveState();

        const observer = new MutationObserver((mutationsList, observer) => {
            if (!window._sidebarApplying) {
                applySidebarActiveState();
            }
        });

        observer.observe(sidebar, { attributes: true, childList: true, subtree: true });
    }

    function applySidebarActiveState() {
        if (window._sidebarApplying) return;
        window._sidebarApplying = true;

        try {
            const sidebar = document.querySelector('.fi-sidebar-nav');
            if (!sidebar) return;

            const currentUrl = window.location.href;
            let targetLabelText = null;

            // Logic to determine which item should be active
            if (currentUrl.includes('/kas-bank/') || currentUrl.includes('kas-bank-detail')) {
                targetLabelText = 'Kas & Bank';
            } else if (
                currentUrl.includes('/contacts') ||
                currentUrl.includes('/hutang') ||
                currentUrl.includes('/piutang')
            ) {
                targetLabelText = 'Kontak';
            } else if (
                currentUrl.includes('/inventori-page') ||
                currentUrl.includes('/warehouses') ||
                currentUrl.includes('/warehouse-transfers') ||
                currentUrl.includes('/stock-adjustments') ||
                currentUrl.includes('/stock-movements')
            ) {
                targetLabelText = 'Inventori';
            } else if (
                currentUrl.includes('/pengaturan') ||
                currentUrl.includes('/data-perusahaan') ||
                currentUrl.includes('/notification-settings') ||
                currentUrl.includes('/invoice-layout-settings') ||
                currentUrl.includes('/profile') ||
                currentUrl.includes('/roles') ||
                currentUrl.includes('/units') ||
                currentUrl.includes('/users') ||
                currentUrl.includes('/tags') ||
                currentUrl.includes('/shipping-methods') ||
                currentUrl.includes('/payment-terms') ||
                currentUrl.includes('/taxes') ||
                currentUrl.includes('/pajak')
            ) {
                targetLabelText = 'Pengaturan';
            } else if (
                currentUrl.includes('/closings/')
            ) {
                targetLabelText = 'Akun';
            }

            if (!targetLabelText) return;

            // Find valid sidebar items
            const labels = Array.from(sidebar.querySelectorAll('.fi-sidebar-item-label'));
            const targetLabel = labels.find(el => el.textContent.trim() === targetLabelText);

            if (!targetLabel) return;

            const targetLink = targetLabel.closest('a');
            if (!targetLink) return;

            // Apply Active Styles
            // 1. Force Background (Light Blue)
            targetLink.style.setProperty('background-color', 'rgba(239, 246, 255, 1)', 'important'); // bg-blue-50
            targetLink.classList.add('fi-active');

            // 2. Force Text Color (Primary Blue)
            targetLink.style.setProperty('color', 'rgb(37, 99, 235)', 'important');
            targetLabel.style.setProperty('color', 'rgb(37, 99, 235)', 'important');

            // 3. Fix Icon Color and swap to solid if it's Pengaturan
            const iconContainer = targetLink.querySelector('.fi-sidebar-item-icon') || targetLink.querySelector('svg');
            if (iconContainer) {
                iconContainer.style.setProperty('color', 'rgb(37, 99, 235)', 'important');
            }

        } finally {
            setTimeout(() => { window._sidebarApplying = false; }, 50);
        }
    }
</script>