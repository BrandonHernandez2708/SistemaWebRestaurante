document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-asignacion');
    const operacionInput = document.getElementById('operacion');
    const idAsignacionInput = document.getElementById('id_asignacion');
    const empleadoInput = document.getElementById('id_empleado');
    const sucursalInput = document.getElementById('id_sucursal');
    const fechaInput = document.getElementById('fecha_asignacion');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnCancelar = document.getElementById('btn-cancelar');

    function limpiar() {
        form.reset();
        operacionInput.value = 'crear';
        idAsignacionInput.value = '';
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
    }

    // --- Guardar ---
    btnGuardar.addEventListener('click', function (e) {
        e.preventDefault();
        if (!empleadoInput.value || !sucursalInput.value || !fechaInput.value) {
            Swal.fire('Campos requeridos', 'Debe llenar todos los campos antes de guardar.', 'warning');
            return;
        }
        Swal.fire({
            title: '¿Guardar asignación?',
            text: '¿Deseas registrar esta asignación?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí'
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });

    // --- Actualizar ---
    btnActualizar.addEventListener('click', function (e) {
        e.preventDefault();
        if (!empleadoInput.value || !sucursalInput.value || !fechaInput.value) {
            Swal.fire('Campos requeridos', 'Debe llenar todos los campos antes de actualizar.', 'warning');
            return;
        }
        Swal.fire({
            title: '¿Actualizar asignación?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí'
        }).then(r => {
            if (r.isConfirmed) {
                operacionInput.value = 'actualizar';
                form.submit();
            }
        });
    });

    // --- Editar ---
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            idAsignacionInput.value = btn.dataset.id;
            empleadoInput.value = btn.dataset.empleado;
            sucursalInput.value = btn.dataset.sucursal;
            fechaInput.value = btn.dataset.fecha;
            operacionInput.value = 'actualizar';
            btnGuardar.style.display = 'none';
            btnActualizar.style.display = 'inline-block';
        });
    });

    // --- Eliminar ---
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            Swal.fire({
                title: '¿Eliminar asignación?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí'
            }).then(r => { if (r.isConfirmed) frm.submit(); });
        });
    });

    btnNuevo.addEventListener('click', limpiar);
    btnCancelar.addEventListener('click', limpiar);

    // --- Mensaje del servidor ---
    if (window.__mensaje) {
        const m = window.__mensaje;
        Swal.fire({
            title: m.tipo === 'success' ? 'Éxito' : 'Atención',
            text: m.text,
            icon: m.tipo
        });
        window.__mensaje = null;
    }
});
