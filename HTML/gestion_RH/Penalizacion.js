// Penalizacion.js — gestión del formulario de penalizaciones
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Penalizacion.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-penalizacion');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idPenalizacionInput = document.getElementById('id_penalizacion');

    // Campos del formulario
    const empleadoInput = document.getElementById('id_empleado');
    const fechaInput = document.getElementById('fecha_penalizacion');
    const descripcionInput = document.getElementById('descripcion_penalizacion');
    const descuentoInput = document.getElementById('descuento_penalizacion');

    // ---------- Validaciones ----------
    function validarFormulario() {
        const empleado = empleadoInput?.value.trim();
        const fecha = fechaInput?.value.trim();
        const descripcion = descripcionInput?.value.trim();
        const descuento = descuentoInput?.value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Atención', text: msg });
            else alert(msg);
        };

        if (!empleado) { showWarning('Debe seleccionar un empleado.'); empleadoInput.focus(); return false; }
        if (!fecha) { showWarning('Debe seleccionar la fecha de la penalización.'); fechaInput.focus(); return false; }
        if (!descripcion) { showWarning('Debe ingresar una descripción.'); descripcionInput.focus(); return false; }
        if (!descuento) { showWarning('Debe ingresar el descuento.'); descuentoInput.focus(); return false; }

        const regexDescripcion = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9\s.,()-]+$/;
        if (!regexDescripcion.test(descripcion)) {
            showWarning('La descripción contiene caracteres no válidos.');
            descripcionInput.focus();
            return false;
        }

        const valorDescuento = parseFloat(descuento);
        if (isNaN(valorDescuento) || valorDescuento < 0) {
            showWarning('El descuento debe ser un valor numérico positivo.');
            descuentoInput.focus();
            return false;
        }

        return true;
    }

    // ---------- Helpers UI ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idPenalizacionInput) idPenalizacionInput.value = '';
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
                title: 'Guardar penalización',
                text: '¿Deseas registrar esta penalización?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
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
                title: 'Actualizar penalización',
                text: '¿Deseas guardar los cambios?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
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
            const empleado = this.getAttribute('data-empleado');
            const fecha = this.getAttribute('data-fecha');
            const descripcion = this.getAttribute('data-descripcion');
            const descuento = this.getAttribute('data-descuento');

            const doFill = () => {
                idPenalizacionInput.value = id || '';
                empleadoInput.value = empleado || '';
                fechaInput.value = fecha || '';
                descripcionInput.value = descripcion || '';
                descuentoInput.value = descuento || '';
                habilitarCampos();
                mostrarBotonesActualizar();
            };

            Swal.fire({
                title: 'Editar penalización',
                text: '¿Deseas editar esta penalización?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
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
                title: '¿Eliminar penalización?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí',
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

    console.log('[Penalizacion.js] DOMContentLoaded: fin');
});
