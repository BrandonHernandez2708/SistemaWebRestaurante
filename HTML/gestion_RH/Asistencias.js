// Asistencias.js — gestión del formulario de asistencias
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Asistencias.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-asistencia');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idAsistenciaInput = document.getElementById('id_asistencia');

    // Campos del formulario
    const fechaInput = document.getElementById('fecha_asistencia');
    const entradaInput = document.getElementById('hora_entrada');
    const salidaInput = document.getElementById('hora_salida');
    const empleadoInput = document.getElementById('id_empleado');

    // ---------- Validaciones ----------
    function validarFormulario() {
        const fecha = fechaInput?.value.trim();
        const entrada = entradaInput?.value.trim();
        const salida = salidaInput?.value.trim();
        const empleado = empleadoInput?.value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Atención', text: msg });
            else alert(msg);
        };

        // Validar campos obligatorios
        if (!fecha) { showWarning('Debe seleccionar la fecha de asistencia.'); fechaInput.focus(); return false; }
        if (!empleado) { showWarning('Debe seleccionar un empleado.'); empleadoInput.focus(); return false; }

        // Validar formato de horas si se llenan
        const horaRegex = /^([01]\d|2[0-3]):([0-5]\d)$/;
        if (entrada && !horaRegex.test(entrada)) {
            showWarning('Formato de hora de entrada no válido. Debe ser HH:MM.');
            entradaInput.focus();
            return false;
        }
        if (salida && !horaRegex.test(salida)) {
            showWarning('Formato de hora de salida no válido. Debe ser HH:MM.');
            salidaInput.focus();
            return false;
        }

        // Validar coherencia entre entrada y salida
        if (entrada && salida && entrada > salida) {
            showWarning('La hora de salida no puede ser menor que la hora de entrada.');
            salidaInput.focus();
            return false;
        }

        return true;
    }

    // ---------- UI Helpers ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idAsistenciaInput) idAsistenciaInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        btnGuardar.disabled = false;
        btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesGuardar() {
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesActualizar() {
        btnGuardar.style.display = 'none';
        btnActualizar.style.display = 'inline-block';
        btnCancelar.style.display = 'inline-block';
    }

    // ---------- Botones ----------
    btnNuevo?.addEventListener('click', function () {
        limpiarFormulario();
        habilitarCampos();
        mostrarBotonesGuardar();
    });

    btnGuardar?.addEventListener('click', function (evt) {
        evt.preventDefault();
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                operacionInput.value = 'crear';
                form.submit();
            };
            Swal.fire({
                title: 'Guardar asistencia',
                text: '¿Deseas registrar esta asistencia?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then(res => { if (res.isConfirmed) doSubmit(); });
        }
    });

    btnActualizar?.addEventListener('click', function () {
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                operacionInput.value = 'actualizar';
                form.submit();
            };
            Swal.fire({
                title: 'Actualizar asistencia',
                text: '¿Deseas guardar los cambios?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then(res => { if (res.isConfirmed) doSubmit(); });
        }
    });

    btnCancelar?.addEventListener('click', function () {
        limpiarFormulario();
        btnCancelar.style.display = 'none';
    });

    // ---------- Editar ----------
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const fecha = this.getAttribute('data-fecha');
            const entrada = this.getAttribute('data-entrada');
            const salida = this.getAttribute('data-salida');
            const empleado = this.getAttribute('data-empleado');

            const doFill = () => {
                idAsistenciaInput.value = id || '';
                fechaInput.value = fecha || '';
                entradaInput.value = entrada || '';
                salidaInput.value = salida || '';
                empleadoInput.value = empleado || '';
                habilitarCampos();
                mostrarBotonesActualizar();
            };

            Swal.fire({
                title: 'Editar asistencia',
                text: '¿Deseas editar este registro?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, editar',
                cancelButtonText: 'Cancelar'
            }).then(res => { if (res.isConfirmed) doFill(); });
        });
    });

    // ---------- Confirmar eliminación ----------
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            Swal.fire({
                title: '¿Eliminar asistencia?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(res => { if (res.isConfirmed) frm.submit(); });
        });
    });

    // ---------- Mostrar mensaje desde el servidor ----------
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok')
                ? 'success' : (m.tipo === 'warning' ? 'warning' : 'error');
            Swal.fire({
                title: icon === 'success' ? 'Éxito' : 'Atención',
                text: m.text,
                icon: icon
            });
            delete window.__mensaje;
        }
    } catch (e) {
        console.warn('Error mostrando mensaje del servidor', e);
    }

    console.log('[Asistencias.js] DOMContentLoaded: fin');
});
