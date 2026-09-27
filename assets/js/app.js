/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Global JavaScript Application Framework & Utilities
 */

const App = {
    baseUrl: '',

    init() {
        this.initBaseUrl();
        this.initMobileSidebar();
        this.initTooltips();
        this.initDrawerListeners();
    },

    initDrawerListeners() {
        // Global Escape key listener
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' || e.keyCode === 27) {
                this.closeAllDrawers();
                // Also close active modals if any
                document.querySelectorAll('.modal-backdrop.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
        });

        // Global backdrop click listener for drawers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('drawer-backdrop')) {
                this.closeDrawer(e.target.id);
            }
            if (e.target.classList.contains('modal-backdrop')) {
                this.closeModal(e.target.id);
            }
        });
    },

    initBaseUrl() {
        // Detect base URL from current pathname
        const path = window.location.pathname;
        const match = path.match(/^(.*?)\/(admin|auth|api)/);
        this.baseUrl = match ? match[1] : '';
    },

    initMobileSidebar() {
        const toggleBtn = document.getElementById('mobileSidebarToggle');
        const sidebar = document.querySelector('.app-sidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
            });
        }
    },

    initTooltips() {
        // Simple tooltip handler
    },

    /**
     * Reusable Fetch API Wrapper with JSON parsing & error handling
     */
    async api(endpoint, options = {}) {
        const url = `${this.baseUrl}/api/${endpoint}`;
        const method = options.method || 'GET';
        const headers = options.headers || {};
        let body = options.body;

        if (body && !(body instanceof FormData) && typeof body === 'object') {
            const formData = new FormData();
            for (const [k, v] of Object.entries(body)) {
                formData.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
            }
            body = formData;
        }

        try {
            const response = await fetch(url, {
                method,
                headers,
                body
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const errMsg = data.message || 'An unexpected error occurred.';
                throw new Error(errMsg);
            }

            return data;
        } catch (err) {
            console.error(`API Error [${endpoint}]:`, err);
            throw err;
        }
    },

    /**
     * Toast notification display
     */
    toast(message, type = 'info', duration = 4000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const iconMap = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        toast.innerHTML = `
            <div style="font-size: 18px; font-weight: bold;">${iconMap[type] || 'ℹ'}</div>
            <div style="flex: 1; font-size: 13px;">${this.escapeHtml(message)}</div>
            <button style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px;" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * Modal controller helpers
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    /**
     * Slide-in Drawer controller helpers
     */
    openDrawer(drawerId) {
        const drawer = document.getElementById(drawerId);
        if (drawer) {
            drawer.classList.add('active');
            document.body.style.overflow = 'hidden';
            // Auto focus first input if present
            const firstInput = drawer.querySelector('input:not([type=hidden]):not([disabled]), select:not([disabled])');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 150);
            }
        }
    },

    closeDrawer(drawerId) {
        const drawer = document.getElementById(drawerId);
        if (drawer) {
            drawer.classList.remove('active');
            // Check if any other drawer or modal is still open before resetting overflow
            const hasOtherOpen = document.querySelector('.drawer-backdrop.active, .modal-backdrop.active');
            if (!hasOtherOpen) {
                document.body.style.overflow = '';
            }
        }
    },

    closeAllDrawers() {
        document.querySelectorAll('.drawer-backdrop.active').forEach(drawer => {
            drawer.classList.remove('active');
        });
        const hasModalOpen = document.querySelector('.modal-backdrop.active');
        if (!hasModalOpen) {
            document.body.style.overflow = '';
        }
    },

    /**
     * Escape HTML string to prevent XSS
     */
    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Format time from HH:MM:SS to 12-hour format (e.g., 08:30 AM)
     */
    formatTime(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let hours = parseInt(parts[0], 10);
        const minutes = parts[1] || '00';
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${hours}:${minutes} ${ampm}`;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
