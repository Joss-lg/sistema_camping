import './bootstrap';
import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.Alpine = Alpine;
window.Chart = Chart;
window.notificationBell = function notificationBell(config = {}) {
    return {
        open: false,
        pendingCount: Number(config.pendingCount ?? 0),
        items: Array.isArray(config.items) ? config.items : [],
        summaryUrl: config.summaryUrl ?? '',
        pollMs: Number(config.pollMs ?? 15000),
        timerId: null,
        init() {
            this.refresh();
            this.timerId = window.setInterval(() => this.refresh(), this.pollMs);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.refresh();
                }
            });
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.refresh();
            }
        },
        async refresh() {
            if (!this.summaryUrl) {
                return;
            }

            try {
                const response = await fetch(this.summaryUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                this.pendingCount = Number(data.pending_count ?? 0);
                this.items = Array.isArray(data.notifications) ? data.notifications : [];
            } catch (error) {
                console.warn('No se pudo actualizar la campana de notificaciones.', error);
            }
        },
    };
};

Alpine.start();
