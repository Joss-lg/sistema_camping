<div
    wire:poll.30s="refreshData"
    x-on:dashboard-data-updated.window="if ($event.detail && $event.detail.chartData) { data = $event.detail.chartData; $nextTick(() => renderAll()); }"
    x-data="{
        charts: {
            produccion: null,
            pedidos: null,
            stock: null,
        },
        ui: {
            colorFondo: '#f4f8f2',
            colorBosque: '#1f5f3a',
            colorBosqueClaro: '#43a26f',
            colorTierra: '#8f6a49',
            colorArena: '#d1b191',
            colorNaranja: '#e67e22',
            colorAlerta: '#c0392b',
            colorTexto: '#1f2937',
            colorGrid: 'rgba(31, 95, 58, 0.12)',
        },
        data: @js($chartData),
        get totalProduccionSemana() {
            return (this.data.produccion.datasets || [])
                .reduce((acc, dataset) => {
                    const values = dataset?.values || [];
                    return acc + values.reduce((sum, value) => sum + Number(value || 0), 0);
                }, 0)
                .toFixed(2);
        },
        get pedidosActivos() {
            return this.data.pedidos.values.reduce((acc, value) => acc + Number(value), 0);
        },
        get alertasStock() {
            return this.data.stock.actual.filter((actual, i) => Number(actual) < Number(this.data.stock.minimo[i])).length;
        },
        init() {
            if (!window.Chart) {
                console.error('Chart.js no esta disponible en window.Chart');
                return;
            }

            this.renderAll();
        },
        renderAll() {
            this.renderProduccionChart();
            this.renderPedidosChart();
            this.renderStockChart();
        },
        baseOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: this.ui.colorTexto,
                            font: {
                                family: 'ui-sans-serif, system-ui, sans-serif',
                                weight: '600',
                            },
                        },
                    },
                },
            };
        },
        renderProduccionChart() {
            const ctx = this.$refs.produccionCanvas.getContext('2d');

            if (this.charts.produccion) {
                this.charts.produccion.destroy();
            }

            const palette = [
                { border: '#1f5f3a', background: 'rgba(31, 95, 58, 0.12)' },
                { border: '#0f766e', background: 'rgba(15, 118, 110, 0.12)' },
                { border: '#1d4ed8', background: 'rgba(29, 78, 216, 0.12)' },
                { border: '#8f6a49', background: 'rgba(143, 106, 73, 0.12)' },
                { border: '#b45309', background: 'rgba(180, 83, 9, 0.12)' },
                { border: '#be185d', background: 'rgba(190, 24, 93, 0.12)' },
            ];

            const datasets = (this.data.produccion.datasets || []).map((dataset, index) => {
                const tone = palette[index % palette.length];
                return {
                    label: dataset.label,
                    data: dataset.values,
                    fill: false,
                    tension: 0.42,
                    borderColor: tone.border,
                    backgroundColor: tone.background,
                    borderWidth: 3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: tone.border,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                };
            });

            this.charts.produccion = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.produccion.labels,
                    datasets,
                },
                options: {
                    ...this.baseOptions(),
                    scales: {
                        x: {
                            grid: { color: this.ui.colorGrid },
                            ticks: { color: this.ui.colorTexto },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: this.ui.colorGrid },
                            ticks: { color: this.ui.colorTexto },
                        },
                    },
                },
            });
        },
        renderPedidosChart() {
            const ctx = this.$refs.pedidosCanvas.getContext('2d');

            if (this.charts.pedidos) {
                this.charts.pedidos.destroy();
            }

            this.charts.pedidos = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.data.pedidos.labels,
                    datasets: [
                        {
                            data: this.data.pedidos.values,
                            backgroundColor: [
                                this.ui.colorTierra,
                                this.ui.colorArena,
                                this.ui.colorBosqueClaro,
                                this.ui.colorBosque,
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 2,
                            hoverOffset: 8,
                        },
                    ],
                },
                options: {
                    ...this.baseOptions(),
                    cutout: '62%',
                    plugins: {
                        ...this.baseOptions().plugins,
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: this.ui.colorTexto,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 16,
                            },
                        },
                    },
                },
            });
        },
        renderStockChart() {
            const ctx = this.$refs.stockCanvas.getContext('2d');

            if (this.charts.stock) {
                this.charts.stock.destroy();
            }

            const actualColors = this.data.stock.actual.map((actual, i) => {
                return Number(actual) < Number(this.data.stock.minimo[i]) ? this.ui.colorAlerta : this.ui.colorBosqueClaro;
            });

            this.charts.stock = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.data.stock.labels,
                    datasets: [
                        {
                            label: 'Stock actual',
                            data: this.data.stock.actual,
                            backgroundColor: actualColors,
                            borderRadius: 8,
                            barThickness: 18,
                        },
                        {
                            label: 'Stock minimo',
                            data: this.data.stock.minimo,
                            backgroundColor: this.ui.colorNaranja,
                            borderRadius: 8,
                            barThickness: 18,
                        },
                    ],
                },
                options: {
                    ...this.baseOptions(),
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: this.ui.colorGrid },
                            ticks: { color: this.ui.colorTexto },
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: this.ui.colorTexto },
                        },
                    },
                },
            });
        },
    }"
    class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-[#f4f8f2] to-orange-50 p-5 shadow-sm sm:p-6"
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Logistica de Mochilas</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Dashboard de Produccion y Materiales</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-700">Monitorea la produccion semanal, el estado operativo de pedidos y alertas de inventario critico en tiempo real.</p>
        </div>
        <div class="rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-orange-800">
            {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-emerald-200 bg-white/90 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Produccion 7 dias</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900" x-text="totalProduccionSemana"></p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-white/90 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Pedidos activos</p>
            <p class="mt-2 text-3xl font-bold text-amber-900" x-text="pedidosActivos"></p>
        </article>
        <article class="rounded-2xl border border-red-200 bg-white/90 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-red-700">Alertas de stock</p>
            <p class="mt-2 text-3xl font-bold text-red-800" x-text="alertasStock"></p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <article class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:p-5 xl:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-800">Volumen de Produccion</h3>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">Todos los productos</span>
            </div>
            <div class="relative h-[60vh] min-h-[360px] w-full max-h-[720px]">
                <div wire:ignore class="h-full w-full">
                    <canvas x-ref="produccionCanvas" class="h-full w-full"></canvas>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:p-5" wire:ignore>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-800">Estado de Pedidos</h3>
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Donut</span>
            </div>
            <div class="relative h-72 w-full">
                <canvas x-ref="pedidosCanvas"></canvas>
            </div>
        </article>

        <article class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:p-5" wire:ignore>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-800">Stock de Materiales</h3>
                <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Rojo = por debajo de minimo</span>
            </div>
            <div class="relative h-72 w-full">
                <canvas x-ref="stockCanvas"></canvas>
            </div>
        </article>
    </div>
</div>
