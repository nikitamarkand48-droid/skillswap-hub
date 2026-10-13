// =======================================================
// SkillSwap - Admin Analytics with Chart.js
// =======================================================

function initAdminCharts(chartData) {
    if (!window.Chart) return;

    // 1. Category Bar Chart: Offered vs Desired
    const catCanvas = document.getElementById('categoryChart');
    if (catCanvas && chartData.categories) {
        new Chart(catCanvas, {
            type: 'bar',
            data: {
                labels: chartData.categories.labels,
                datasets: [
                    {
                        label: 'Offered (Teaching)',
                        data: chartData.categories.teachData,
                        backgroundColor: '#4f46e5',
                        borderRadius: 6
                    },
                    {
                        label: 'Desired (Learning)',
                        data: chartData.categories.learnData,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Swap Status Doughnut Chart
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && chartData.statuses) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: chartData.statuses.labels,
                datasets: [{
                    data: chartData.statuses.counts,
                    backgroundColor: [
                        '#10b981', // completed - green
                        '#4f46e5', // accepted - indigo
                        '#f59e0b', // pending - amber
                        '#ef4444', // rejected - red
                        '#94a3b8'  // cancelled - gray
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '65%'
            }
        });
    }
}
