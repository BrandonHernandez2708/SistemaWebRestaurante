document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-planilla');
    const btnGenerar = document.getElementById('btn-generar');

    if (btnGenerar && form) {
        btnGenerar.addEventListener('click', (e) => {
            e.preventDefault();
            const mes = document.getElementById('mes_planilla').value;
            const anio = document.getElementById('anio_planilla').value;

            if (!mes || !anio) {
                Swal.fire('Campos requeridos', 'Seleccione el mes y el año antes de continuar.', 'warning');
                return;
            }

            Swal.fire({
                title: '¿Generar planilla?',
                text: `Se generará la planilla del mes ${mes} del año ${anio}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, generar',
                cancelButtonText: 'Cancelar'
            }).then((res) => {
                if (res.isConfirmed) form.submit();
            });
        });
    }

    // Mostrar mensaje del servidor
    if (window.__mensaje) {
        const m = window.__mensaje;
        Swal.fire({
            icon: m.tipo === 'success' ? 'success' : (m.tipo === 'warning' ? 'warning' : 'error'),
            title: m.tipo === 'success' ? 'Éxito' : 'Atención',
            text: m.text
        });
        delete window.__mensaje;
    }
});
