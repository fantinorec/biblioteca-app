document.addEventListener('DOMContentLoaded', function () {
    const datos = window.DATOS_REPORTES || {};
    const paletaOliva = ['#6b6f3a', '#a7ac6e', '#4b4d28', '#b8860b', '#8a8266', '#3d6b8a'];
    const MESES_CORTOS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    function formatearPeriodoMes(periodo) {
        const partes = String(periodo).split('-');
        const indiceMes = parseInt(partes[1], 10) - 1;
        const nombreMes = MESES_CORTOS[indiceMes];
        return nombreMes ? nombreMes + ' ' + partes[0] : periodo;
    }

    function pluralPrestamos(cantidad) {
        return cantidad + (cantidad === 1 ? ' préstamo' : ' préstamos');
    }

    const opcionesComunes = {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: '#3a352a' } },
            tooltip: {
                backgroundColor: '#3a352a',
                titleColor: '#f6f2e7',
                bodyColor: '#f6f2e7',
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
            },
        },
        scales: {
            x: { ticks: { color: '#3a352a', precision: 0 }, grid: { color: '#ded2ba' } },
            y: { ticks: { color: '#3a352a', precision: 0 }, grid: { color: '#ded2ba' }, beginAtZero: true }
        }
    };

    const pluginEtiquetasValor = {
        id: 'etiquetasValor',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, indiceDataset) => {
                const meta = chart.getDatasetMeta(indiceDataset);
                if (meta.hidden) return;
                meta.data.forEach((elemento, indice) => {
                    const valor = dataset.data[indice];
                    if (valor === null || valor === undefined) return;
                    ctx.save();
                    ctx.fillStyle = '#3a352a';
                    ctx.font = '600 12px system-ui, sans-serif';
                    ctx.textBaseline = 'middle';
                    if (chart.options.indexAxis === 'y') {
                        ctx.textAlign = 'left';
                        ctx.fillText(String(valor), elemento.x + 8, elemento.y);
                    } else {
                        ctx.textAlign = 'center';
                        ctx.fillText(String(valor), elemento.x, elemento.y - 10);
                    }
                    ctx.restore();
                });
            });
        }
    };

    if (document.getElementById('graficoPrestamosMes')) {
        new Chart(document.getElementById('graficoPrestamosMes'), {
            type: 'line',
            data: {
                labels: datos.prestamosPorMes.map(f => formatearPeriodoMes(f.periodo)),
                datasets: [{
                    label: 'Préstamos',
                    data: datos.prestamosPorMes.map(f => f.cantidad),
                    borderColor: '#6b6f3a',
                    backgroundColor: function (contexto) {
                        const { chart } = contexto;
                        const { ctx, chartArea } = chart;
                        if (!chartArea) return 'rgba(107,111,58,0.15)';
                        const gradiente = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradiente.addColorStop(0, 'rgba(107,111,58,0.32)');
                        gradiente.addColorStop(1, 'rgba(107,111,58,0.02)');
                        return gradiente;
                    },
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#6b6f3a',
                    pointBorderColor: '#f6f2e7',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                ...opcionesComunes,
                plugins: {
                    ...opcionesComunes.plugins,
                    legend: { display: false },
                    tooltip: {
                        ...opcionesComunes.plugins.tooltip,
                        callbacks: {
                            label: function (contexto) { return pluralPrestamos(contexto.parsed.y); }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('graficoLibrosCategoria')) {
        new Chart(document.getElementById('graficoLibrosCategoria'), {
            type: 'bar',
            data: {
                labels: datos.librosPorCategoria.map(f => f.categoria),
                datasets: [{ label: 'Libros', data: datos.librosPorCategoria.map(f => f.cantidad), backgroundColor: paletaOliva }]
            },
            options: {
                ...opcionesComunes,
                plugins: { ...opcionesComunes.plugins, legend: { display: false } }
            }
        });
    }

    if (document.getElementById('graficoTopLibros')) {
        const TITULO_MAX = 24;
        new Chart(document.getElementById('graficoTopLibros'), {
            type: 'bar',
            data: {
                labels: datos.topLibros.map(f => f.titulo),
                datasets: [{
                    label: 'Préstamos',
                    data: datos.topLibros.map(f => f.cantidad),
                    backgroundColor: '#4b4d28',
                    borderRadius: 6,
                    maxBarThickness: 28,
                    categoryPercentage: 0.7,
                    barPercentage: 0.85,
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                layout: { padding: { right: 30 } },
                interaction: { mode: 'nearest', axis: 'y', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...opcionesComunes.plugins.tooltip,
                        callbacks: {
                            title: function (contextos) { return contextos[0].label; },
                            label: function (contexto) { return pluralPrestamos(contexto.parsed.x); }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: '#3a352a', precision: 0 }, grid: { color: '#ded2ba' }, beginAtZero: true },
                    y: {
                        ticks: {
                            color: '#3a352a',
                            callback: function (valor) {
                                const etiqueta = this.getLabelForValue(valor);
                                return etiqueta.length > TITULO_MAX ? etiqueta.slice(0, TITULO_MAX - 1) + '…' : etiqueta;
                            }
                        },
                        grid: { display: false }
                    }
                }
            },
            plugins: [pluginEtiquetasValor]
        });
    }

    if (document.getElementById('graficoUsuariosPerfil')) {
        new Chart(document.getElementById('graficoUsuariosPerfil'), {
            type: 'doughnut',
            data: {
                labels: datos.usuariosPorPerfil.map(f => f.perfil),
                datasets: [{ data: datos.usuariosPorPerfil.map(f => f.cantidad), backgroundColor: paletaOliva }]
            },
            options: { responsive: true, plugins: { legend: { labels: { color: '#3a352a' } } } }
        });
    }

    document.querySelectorAll('[data-export-xls]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const tabla = document.getElementById(boton.getAttribute('data-export-xls'));
            const nombre = boton.getAttribute('data-nombre') || 'reporte';
            const hoja = XLSX.utils.table_to_sheet(tabla);
            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Datos');
            XLSX.writeFile(libro, nombre + '.xlsx');
            mostrarToast('exito', 'Exportado', 'Se descargó ' + nombre + '.xlsx');
        });
    });

    document.querySelectorAll('[data-export-pdf]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const canvas = document.getElementById(boton.getAttribute('data-export-pdf'));
            const nombre = boton.getAttribute('data-nombre') || 'grafico';
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape' });
            const imagen = canvas.toDataURL('image/png', 1.0);
            const ancho = pdf.internal.pageSize.getWidth() - 20;
            const alto = (canvas.height / canvas.width) * ancho;
            pdf.setFontSize(14);
            pdf.text(nombre.replace(/_/g, ' '), 10, 12);
            pdf.addImage(imagen, 'PNG', 10, 18, ancho, alto);
            pdf.save(nombre + '.pdf');
            mostrarToast('exito', 'Exportado', 'Se descargó ' + nombre + '.pdf');
        });
    });

    const btnTodo = document.getElementById('btnExportarTodoPdf');
    if (btnTodo) {
        btnTodo.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape' });
            const canvases = document.querySelectorAll('canvas');
            canvases.forEach(function (canvas, indice) {
                if (indice > 0) pdf.addPage();
                const imagen = canvas.toDataURL('image/png', 1.0);
                const ancho = pdf.internal.pageSize.getWidth() - 20;
                const alto = (canvas.height / canvas.width) * ancho;
                pdf.setFontSize(14);
                pdf.text(canvas.id, 10, 12);
                pdf.addImage(imagen, 'PNG', 10, 18, ancho, alto);
            });
            pdf.save('reportes_biblioteca.pdf');
            mostrarToast('exito', 'Exportado', 'Se descargaron todos los gráficos en reportes_biblioteca.pdf');
        });
    }
});
