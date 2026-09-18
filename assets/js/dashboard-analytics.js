/**
 * Civentral Dashboard Analytics Module
 * Renders dynamic Chart.js visualizations for the Citizen Information & Engagement Dashboard.
 */

document.addEventListener('DOMContentLoaded', () => {
    let trendsChart = null;
    let demoChart = null;
    let radarChart = null;

    const dataPayload = window.dashboardAnalyticsData || {
        demographics: [6, 1, 1, 1],
        demographicsLabels: ['Youth (<30)', 'Working Class (30-59)', 'Senior Citizens (60+)', 'Solo Parents'],
        radarLabels: ['Civil Registry & KYC', 'Public Grievance (311)', 'Barangay Certificates', 'Public Consultations', 'Community Broadcasts'],
        radarData: [88, 76, 92, 68, 75]
    };

    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            gridColor: isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(241, 245, 249, 0.8)',
            ticksColor: isDark ? '#94a3b8' : '#64748b',
            legendColor: isDark ? '#cbd5e1' : '#475569',
            radarGridColor: isDark ? '#334155' : '#E2E8F0',
            radarAngleColor: isDark ? '#1e293b' : '#F1F5F9',
            radarBg: isDark ? '#0f172a' : '#ffffff'
        };
    }

    // 1. Line/Area Chart for Trends
    function renderTrendsChart() {
        const trendsCanvas = document.getElementById('trendsChart');
        if (!trendsCanvas) return;
        const trendsCtx = trendsCanvas.getContext('2d');
        const colors = getThemeColors();
        
        const citizenGrad = trendsCtx.createLinearGradient(0, 0, 0, 300);
        citizenGrad.addColorStop(0, 'rgba(30, 81, 123, 0.25)');
        citizenGrad.addColorStop(1, 'rgba(30, 81, 123, 0)');

        const appGrad = trendsCtx.createLinearGradient(0, 0, 0, 300);
        appGrad.addColorStop(0, 'rgba(245, 158, 11, 0.22)');
        appGrad.addColorStop(1, 'rgba(245, 158, 11, 0)');

        trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [
                    {
                        label: 'Citizens Verified',
                        data: [1, 1, 2, 2, 2, 2],
                        borderColor: '#1E517B',
                        borderWidth: 3,
                        fill: true,
                        backgroundColor: citizenGrad,
                        tension: 0.4,
                        pointBackgroundColor: '#1E517B',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Engagement Actions',
                        data: [4, 6, 8, 11, 12, 14],
                        borderColor: '#F59E0B',
                        borderWidth: 3,
                        fill: true,
                        backgroundColor: appGrad,
                        tension: 0.4,
                        pointBackgroundColor: '#F59E0B',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9,
                                weight: '600'
                            },
                            color: colors.ticksColor
                        }
                    },
                    y: {
                        grid: {
                            color: colors.gridColor,
                            lineWidth: 1
                        },
                        ticks: {
                            font: {
                                size: 9,
                                weight: '600'
                            },
                            color: colors.ticksColor,
                            stepSize: 2
                        }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart for Demographics
    function renderDemoChart() {
        const demoCanvas = document.getElementById('demographicsChart');
        if (!demoCanvas) return;
        const demoCtx = demoCanvas.getContext('2d');
        const colors = getThemeColors();

        demoChart = new Chart(demoCtx, {
            type: 'doughnut',
            data: {
                labels: dataPayload.demographicsLabels,
                datasets: [{
                    data: dataPayload.demographics,
                    backgroundColor: [
                        '#1E517B', // youth (navy)
                        '#0D9488', // working class (teal)
                        '#F59E0B', // senior (amber)
                        '#7C3AED'  // solo parent (purple)
                    ],
                    borderWidth: 3,
                    borderColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 9,
                                weight: '700'
                            },
                            boxWidth: 10,
                            color: colors.legendColor,
                            padding: 12
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }

    // 3. Radar Chart for Engagement by Module
    function renderRadarChart() {
        const radarCanvas = document.getElementById('workloadRadarChart');
        if (!radarCanvas) return;
        const radarCtx = radarCanvas.getContext('2d');
        const colors = getThemeColors();

        radarChart = new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: dataPayload.radarLabels,
                datasets: [
                    {
                        label: 'Module Engagement Index',
                        data: dataPayload.radarData,
                        backgroundColor: 'rgba(14, 165, 233, 0.18)',
                        borderColor: '#0284c7',
                        borderWidth: 2,
                        pointBackgroundColor: '#0284c7',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            color: colors.radarAngleColor
                        },
                        grid: {
                            color: colors.radarGridColor
                        },
                        ticks: {
                            display: false
                        },
                        pointLabels: {
                            font: {
                                size: 8,
                                weight: '700'
                            },
                            color: colors.ticksColor
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
    }

    // Initial Render
    renderTrendsChart();
    renderDemoChart();
    renderRadarChart();

    // Listen for Theme Toggle to dynamically redraw charts
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            setTimeout(() => {
                if (trendsChart) trendsChart.destroy();
                if (demoChart) demoChart.destroy();
                if (radarChart) radarChart.destroy();

                renderTrendsChart();
                renderDemoChart();
                renderRadarChart();
            }, 150);
        });
    }
});
