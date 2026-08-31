/**
 * Renderizado de graficos (Chart.js) y exportacion a Excel (SheetJS) y
 * PDF (jsPDF) de los reportes del dashboard.
 */
document.addEventListener('DOMContentLoaded', function () {
    const datos = window.DATOS_REPORTES || {};
    const paletaOliva = ['#6b6f3a', '#a7ac6e', '#4b4d28', '#b8860b', '#8a8266', '#3d6b8a'];

    const opcionesComunes = {
        responsive: true,
        plugins: { legend: { labels: { color: '#3a352a' } } },
        scales: {
            x: { ticks: { color: '#3a352a' }, grid: { color: '#ded2ba' } },
            y: { ticks: { color: '#3a352a' }, grid: { color: '#ded2ba' }, beginAtZero: true }
        }
    };

    if (document.getElementById('graficoPrestamosMes')) {
        new Chart(document.getElementById('graficoPrestamosMes'), {
            type: 'line',
            data: {
                labels: datos.prestamosPorMes.map(f => f.periodo),
                datasets: [{
                    label: 'Préstamos',
                    data: datos.prestamosPorMes.map(f => f.cantidad),
                    borderColor: '#6b6f3a',
                    backgroundColor: 'rgba(107,111,58,0.2)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: opcionesComunes
        });
    }

    if (document.getElementById('graficoLibrosCategoria')) {
        new Chart(document.getElementById('graficoLibrosCategoria'), {
            type: 'bar',
            data: {
                labels: datos.librosPorCategoria.map(f => f.categoria),
                datasets: [{ label: 'Libros', data: datos.librosPorCategoria.map(f => f.cantidad), backgroundColor: paletaOliva }]
            },
            options: { ...opcionesComunes, plugins: { legend: { display: false } } }
        });
    }

    if (document.getElementById('graficoTopLibros')) {
        new Chart(document.getElementById('graficoTopLibros'), {
            type: 'bar',
            data: {
                labels: datos.topLibros.map(f => f.titulo),
                datasets: [{ label: 'Préstamos', data: datos.topLibros.map(f => f.cantidad), backgroundColor: '#4b4d28' }]
            },
            options: { ...opcionesComunes, indexAxis: 'y', plugins: { legend: { display: false } } }
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

    // --- Exportar a Excel (una tabla por boton) -----------------------------
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

    // --- Exportar un grafico puntual a PDF -----------------------------------
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

    // --- Exportar TODOS los graficos en un solo PDF --------------------------
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
