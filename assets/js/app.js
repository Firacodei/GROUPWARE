/**
 * GROUPWARE — SaaS Client Interactivity Engine
 * Provides reactive interactions: collapsible sidebar, command search,
 * mobile drawer, dropdowns, toasts, password visibility, and confirmations.
 */
(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initProfileDropdown();
        initPasswordToggles();
        initConfirmations();
        initSearchShortcut();
    });

    /**
     * 1. Collapsible & Responsive Mobile Sidebar
     */
    function initSidebar() {
        const sidebar = document.getElementById('app-sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const mobileBtn = document.getElementById('mobile-menu-btn');

        if (!sidebar) return;

        // Restore collapsed state on desktop
        const isCollapsed = localStorage.getItem('groupware_sidebar_collapsed') === 'true';
        if (isCollapsed && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                localStorage.setItem(
                    'groupware_sidebar_collapsed',
                    sidebar.classList.contains('collapsed')
                );
            });
        }

        // Mobile drawer overlay & toggle
        if (mobileBtn) {
            let overlay = document.getElementById('sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'sidebar-overlay';
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(11,15,25,0.7);backdrop-filter:blur(4px);z-index:35;display:none;';
                document.body.appendChild(overlay);
            }

            const toggleMobile = () => {
                const isOpen = sidebar.classList.toggle('mobile-open');
                overlay.style.display = isOpen ? 'block' : 'none';
            };

            mobileBtn.addEventListener('click', toggleMobile);
            overlay.addEventListener('click', toggleMobile);
        }
    }

    /**
     * 2. Profile Dropdown Menu
     */
    function initProfileDropdown() {
        const trigger = document.getElementById('profile-dropdown-trigger');
        const menu = document.getElementById('profile-dropdown-menu');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    }

    /**
     * 3. Password Visibility Toggle
     */
    function initPasswordToggles() {
        document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : btn.previousElementSibling;
                if (!input) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
                } else {
                    input.type = 'password';
                    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
                }
            });
        });
    }

    /**
     * 4. Confirmation Dialogs
     */
    function initConfirmations() {
        document.querySelectorAll('[data-confirm]').forEach((el) => {
            el.addEventListener('click', (e) => {
                const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * 5. Search Trigger & Shortcut (Ctrl/Cmd + K)
     */
    function initSearchShortcut() {
        const searchBtn = document.getElementById('global-search-btn');

        const triggerSearch = () => {
            const query = prompt('Quick Find / Jump to (Tasks, Proposals, Teams):');
            if (query && query.trim()) {
                const q = encodeURIComponent(query.trim().toLowerCase());
                // Look for matching elements on the current page or filter
                const rows = document.querySelectorAll('tbody tr, .proposal-item, .stat-card');
                let found = 0;
                rows.forEach((row) => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query.trim().toLowerCase())) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.style.outline = '2px solid #3B82F6';
                        setTimeout(() => row.style.outline = '', 2000);
                        found++;
                    }
                });
                if (found > 0) {
                    window.showToast(`Found and highlighted ${found} matching item(s).`, 'info');
                } else {
                    window.showToast(`No items matching "${query}" on this view.`, 'warning');
                }
            }
        };

        if (searchBtn) {
            searchBtn.addEventListener('click', triggerSearch);
        }

        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                triggerSearch();
            }
        });
    }

    /**
     * 6. Global Toast Notification System
     */
    window.showToast = function (message, type = 'info', duration = 4000) {
        let container = document.getElementById('global-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'global-toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            danger: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`,
            warning: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
            info: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
        };

        toast.innerHTML = `
            ${icons[type] || icons.info}
            <span style="flex:1;">${escapeHtml(message)}</span>
            <button type="button" style="color:var(--text-muted);padding:2px;" onclick="this.parentElement.remove()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 200ms ease, transform 200ms ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(() => toast.remove(), 250);
        }, duration);
    };

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
