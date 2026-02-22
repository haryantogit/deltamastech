<div>
    <canvas id="contactCashFlowChart" style="height: 300px; width: 100%;"></canvas>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('contactCashFlowChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        datasets: [
                            {
                                label: 'Uang Masuk',
                                data: [10000000, 15000000, 8000000, 12000000, 45000000, 20000000, 18000000, 25000000, 15000000, 12000000, 8000000, 10000000],
                                backgroundColor: '#FCD34D',
                                borderRadius: 4,
                            },
                            {
                                label: 'Uang Keluar',
                                data: [8000000, 12000000, 6000000, 10000000, 35000000, 15000000, 14000000, 20000000, 12000000, 10000000, 6000000, 8000000],
                                backgroundColor: '#60A5FA',
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush