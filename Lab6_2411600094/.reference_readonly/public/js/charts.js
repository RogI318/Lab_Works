const dashboardCharts = (function () {
    'use strict';

    let categoryChart = null;
    let statusChart = null;
    let topProductsChart = null;
    let valueChart = null;

    const COLORS = {
        primary: '#FF8FAB',
        secondary: '#FFC2D1',
        accent: '#6B818C',
        success: '#28a745',
        warning: '#f3a712',
        danger: '#dc3545'
    };

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 500
        }
    };

    // Formats a number as pesos, example: 1234.5 -> ₱1,234.50
    function formatPeso(value) {
        return '₱' + Number(value).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Chart 1: Quantity by Category
    function renderCategoryChart(categorySummary) {
        const canvas = document.getElementById('categoryChart');

        if (!canvas) return;

        const labels = categorySummary.map(c => c.category);
        const values = categorySummary.map(c => Number(c.total_quantity));

        if (categoryChart) {
            categoryChart.destroy();
        }

        categoryChart = new Chart(canvas, {
            type: 'bar',

            data: {
                labels: labels,

                datasets: [{
                    label: 'Quantity',
                    data: values,
                    backgroundColor: COLORS.primary,
                    hoverBackgroundColor: COLORS.secondary,
                    borderRadius: 6,
                    maxBarThickness: 48
                }]
            },

            options: {
                ...baseOptions,

                plugins: {
                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: (ctx) =>
                                `${ctx.parsed.y} unit(s)`
                        }
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },

                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 30,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    // Chart 2: Stock Status Distribution
    function renderStatusChart(stats) {
        const canvas = document.getElementById('statusChart');

        if (!canvas) return;

        if (statusChart) {
            statusChart.destroy();
        }

        statusChart = new Chart(canvas, {
            type: 'polarArea',

            data: {
                labels: [
                    'In Stock',
                    'Low Stock',
                    'Out of Stock'
                ],

                datasets: [{
                    data: [
                        stats.inStock,
                        stats.lowStock,
                        stats.outOfStock
                    ],

                    backgroundColor: [
                        COLORS.success,
                        COLORS.warning,
                        COLORS.danger
                    ],

                    hoverOffset: 8
                }]
            },

            options: {
                ...baseOptions,

                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Chart 3: Top 5 Products by Stock Value
    function renderTopProductsChart(topProducts) {
        const canvas = document.getElementById('topProductsChart');

        if (!canvas) return;

        if (topProductsChart) {
            topProductsChart.destroy();
        }

        topProductsChart = new Chart(canvas, {
            type: 'bar',

            data: {
                labels: topProducts.map(p => p.name),

                datasets: [{
                    label: 'Stock Value',

                    data: topProducts.map(p => Number(p.value)),

                    backgroundColor: COLORS.secondary,
                    hoverBackgroundColor: COLORS.primary,
                    borderRadius: 6
                }]
            },

            options: {
                ...baseOptions,

                indexAxis: 'y',

                plugins: {
                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: (ctx) => formatPeso(ctx.parsed.x)
                        }
                    }
                },

                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Chart 4: Inventory Value by Category
    function renderValueChart(categorySummary) {
        const canvas = document.getElementById('valueChart');

        if (!canvas) return;

        if (valueChart) {
            valueChart.destroy();
        }

        valueChart = new Chart(canvas, {
            type: 'bar',

            data: {
                labels: categorySummary.map(c => c.category),

                datasets: [{
                    label: 'Inventory Value',
                    data: categorySummary.map(c => Number(c.stock_value)),
                    backgroundColor: COLORS.accent,
                    hoverBackgroundColor: COLORS.primary,
                    borderRadius: 6,
                    maxBarThickness: 48
                }]
            },

            options: {
                ...baseOptions,

                plugins: {
                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: (ctx) => formatPeso(ctx.parsed.y)
                        }
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true
                    },

                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 30,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    function renderAll(categorySummary, statusStats, topProducts) {
        renderCategoryChart(categorySummary);
        renderStatusChart(statusStats);
        renderTopProductsChart(topProducts);
        renderValueChart(categorySummary);
    }

    return {
        renderAll,
        renderCategoryChart,
        renderStatusChart,
        renderTopProductsChart,
        renderValueChart
    };

})();
