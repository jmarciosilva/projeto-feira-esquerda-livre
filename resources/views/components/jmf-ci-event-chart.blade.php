{{-- Gráfico de tendência de eventos (com Chart.js) --}}
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $title ?? 'Tendência de Eventos' }}</h3>

    <canvas id="chart-{{ $chartId ?? 'default' }}" class="w-full"></canvas>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('chart-{{ $chartId ?? 'default' }}');
        if (ctx) {
            const labels = @json(array_keys($data ?? []));
            const values = @json(array_values($data ?? []));

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{{ $label ?? "Eventos" }}',
                        data: values,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: Math.max(...values) / 5
                            }
                        }
                    }
                }
            });
        }
    </script>
@endpush
