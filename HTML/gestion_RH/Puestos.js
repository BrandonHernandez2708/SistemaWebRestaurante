// Puestos.js — gestión del formulario de puestos
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Puestos.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-puesto');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idPuestoInput = document.getElementById('id_puesto');

    // Campos del formulario
    const puestoInput = document.getElementById('puesto');
    const descripcionInput = document.getElementById('descripcion');
    const sueldoInput = document.getElementById('sueldo_base');

    // ---------- Validaciones ----------
    function validarFormulario() {
        const puesto = puestoInput?.value.trim();
        const descripcion = descripcionInput?.value.trim();
        const sueldo = sueldoInput?.value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Atención', text: msg });
            else alert(msg);
        };

        if (!puesto) { showWarning('Debe ingresar el nombre del puesto.'); puestoInput.focus(); return false; }
        if (!descripcion) { showWarning('Debe ingresar la descripción del puesto.'); descripcionInput.focus(); return false; }
        if (!sueldo) { showWarning('Debe ingresar el sueldo base.'); sueldoInput.focus(); return false; }

    // Validación estricta para el nombre del puesto: sólo letras y espacios (igual que en Empleados)
    const nameRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/;
    const puestoNorm = puesto.replace(/\s+/g, ' ');
    if (!nameRegex.test(puestoNorm)) { showWarning('El nombre del puesto sólo debe contener letras y espacios.'); puestoInput.focus(); return false; }

    // Para la descripción permitimos más caracteres (letras, números y signos básicos)
    const descRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9\s.,()\-]+$/;
    if (!descRegex.test(descripcion)) { showWarning('La descripción contiene caracteres no válidos.'); descripcionInput.focus(); return false; }

        const sueldoVal = parseFloat(sueldo);
        if (isNaN(sueldoVal) || sueldoVal < 0) { showWarning('El sueldo base debe ser un número positivo.'); sueldoInput.focus(); return false; }

        return true;
    }

    // ---------- UI Helpers ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idPuestoInput) idPuestoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    // Sanitizar y formatear el campo nombre del puesto
    (function initPuestoSanitizer() {
        if (!puestoInput) return;
        const nameSanitizeRegex = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g;
        puestoInput.addEventListener('input', function () {
            let v = this.value || '';
            v = v.replace(nameSanitizeRegex, '');
            v = v.replace(/\s+/g, ' ');
            this.value = v;
        });
        puestoInput.addEventListener('blur', function () {
            let v = (this.value || '').trim();
            if (!v) return;
            if (v === v.toUpperCase()) { this.value = v.replace(/\s+/g, ' '); return; }
            const parts = v.split(' ').filter(Boolean);
            const formatted = parts.map(p => {
                const first = p.charAt(0).toLocaleUpperCase('es-ES');
                const rest = p.slice(1).toLocaleLowerCase('es-ES');
                return first + rest;
            }).join(' ');
            this.value = formatted;
        });
    })();

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
                title: 'Guardar puesto',
                text: '¿Deseas registrar este puesto?',
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
                title: 'Actualizar puesto',
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
            const puesto = this.getAttribute('data-puesto');
            const descripcion = this.getAttribute('data-descripcion');
            const sueldo = this.getAttribute('data-sueldo');

            const doFill = () => {
                idPuestoInput.value = id || '';
                puestoInput.value = puesto || '';
                descripcionInput.value = descripcion || '';
                sueldoInput.value = sueldo || '';

                habilitarCampos();
                mostrarBotonesActualizar();
            };

            Swal.fire({
                title: 'Editar puesto',
                text: '¿Deseas editar este puesto?',
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
                title: '¿Eliminar puesto?',
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

    console.log('[Puestos.js] DOMContentLoaded: fin');
});
