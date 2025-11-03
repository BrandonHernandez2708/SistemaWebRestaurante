// Sucursales.js — Gestión del formulario de sucursales
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Sucursales.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-sucursal');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idSucursalInput = document.getElementById('id_sucursal');

    // Campos del formulario
    const direccionInput = document.getElementById('direccion_sucursal');
    const aperturaInput = document.getElementById('horario_apertura');
    const cierreInput = document.getElementById('hora_cierre');
    const capacidadInput = document.getElementById('capacidad_empleados');
    const telefonoInput = document.getElementById('telefono_sucursal');
    const correoInput = document.getElementById('correo_sucursal');
    const departamentoInput = document.getElementById('id_departamento');

    // ---------- Formato teléfono (4-4) en sucursales ----------
    if (telefonoInput) {
        telefonoInput.maxLength = 9; // 4 dígitos + '-' + 4 dígitos
        telefonoInput.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '');
            if (digits.length > 8) digits = digits.slice(0, 8);
            if (digits.length > 4) this.value = digits.slice(0,4) + '-' + digits.slice(4);
            else this.value = digits;
        });
        telefonoInput.addEventListener('keydown', function (evt) {
            const allowed = [8,9,13,27,37,38,39,40,46];
            if (allowed.indexOf(evt.keyCode) !== -1) return;
            if (evt.ctrlKey || evt.metaKey) return;
            if ((evt.keyCode >= 48 && evt.keyCode <= 57) || (evt.keyCode >= 96 && evt.keyCode <= 105)) return;
            evt.preventDefault();
        });
    }

    // ---------- Validaciones ----------
    function validarFormulario() {
        const direccion = direccionInput?.value.trim();
        const apertura = aperturaInput?.value.trim();
        const cierre = cierreInput?.value.trim();
        const capacidad = capacidadInput?.value.trim();
        const telefono = telefonoInput?.value.trim();
        const correo = correoInput?.value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Atención', text: msg });
            else alert(msg);
        };

    if (!direccion) { showWarning('Debe ingresar la dirección de la sucursal.'); if (direccionInput) direccionInput.focus(); return false; }
    if (!apertura) { showWarning('Debe ingresar el horario de apertura.'); if (aperturaInput) aperturaInput.focus(); return false; }
    if (!cierre) { showWarning('Debe ingresar la hora de cierre.'); if (cierreInput) cierreInput.focus(); return false; }
    if (!capacidad || parseInt(capacidad) <= 0) { showWarning('Debe indicar una capacidad de empleados válida.'); if (capacidadInput) capacidadInput.focus(); return false; }

        // Validar formato de teléfono (se requiere 4 dígitos + '-' + 4 dígitos, p.ej. 5460-1234)
        if (telefono && !/^\d{4}-\d{4}$/.test(telefono)) {
            showWarning('El número de teléfono no es válido. Use el formato 5460-1234');
            if (telefonoInput) telefonoInput.focus();
            return false;
        }

        // Validar correo
        if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            showWarning('El formato del correo electrónico no es válido.');
            if (correoInput) correoInput.focus();
            return false;
        }

        return true;
    }

    // ---------- UI Helpers ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idSucursalInput) idSucursalInput.value = '';
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
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar sucursal',
                    text: '¿Deseas registrar esta sucursal?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then(res => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas registrar esta sucursal?')) doSubmit();
            }
        }
    });

    btnActualizar?.addEventListener('click', function () {
        if (!form) return;
            if (validarFormulario()) {
            const doSubmit = () => {
                operacionInput.value = 'actualizar';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar sucursal',
                    text: '¿Deseas guardar los cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then(res => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas guardar los cambios?')) doSubmit();
            }
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
            const direccion = this.getAttribute('data-direccion');
            const apertura = this.getAttribute('data-apertura');
            const cierre = this.getAttribute('data-cierre');
            const capacidad = this.getAttribute('data-capacidad');
            const telefono = this.getAttribute('data-telefono');
            const correo = this.getAttribute('data-correo');
            const departamento = this.getAttribute('data-departamento');

            const doFill = () => {
                if (idSucursalInput) idSucursalInput.value = id || '';
                if (direccionInput) direccionInput.value = direccion || '';
                if (aperturaInput) aperturaInput.value = apertura || '';
                if (cierreInput) cierreInput.value = cierre || '';
                if (capacidadInput) capacidadInput.value = capacidad || '';
                if (telefonoInput) telefonoInput.value = telefono || '';
                if (correoInput) correoInput.value = correo || '';
                if (departamentoInput) departamentoInput.value = departamento || '';

                habilitarCampos();
                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar sucursal',
                    text: '¿Deseas editar esta sucursal?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) doFill(); });
            } else {
                if (confirm('¿Deseas editar esta sucursal?')) doFill();
            }
        });
    });

    // ---------- Confirmar eliminación ----------
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar sucursal?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) frm.submit(); });
            } else {
                if (confirm('¿Eliminar sucursal?')) frm.submit();
            }
        });
    });

    // ---------- Mostrar mensaje desde servidor ----------
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok')
                ? 'success' : (m.tipo === 'warning' ? 'warning' : 'error');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: icon === 'success' ? 'Éxito' : 'Atención',
                    text: m.text,
                    icon: icon
                });
            } else {
                alert(m.text);
            }
            delete window.__mensaje;
        }
    } catch (e) {
        console.warn('Error mostrando mensaje del servidor', e);
    }

    console.log('[Sucursales.js] DOMContentLoaded: fin');
});

    (function(){

        function initFormTextToggle() {
            var form = document.getElementById('form-sucursal');
            if (!form) return;

            var fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function(f){
                f.addEventListener('focus', function(){
                    var container = f.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col, .form-group') || f.parentElement;
                    if (!container) return;
                    var help = container.querySelector('small.form-text.help-text');
                    if (help) help.classList.add('visible');
                });
                f.addEventListener('blur', function(){
                    var container = f.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col, .form-group') || f.parentElement;
                    if (!container) return;
                    var help = container.querySelector('small.form-text.help-text');
                    if (help) help.classList.remove('visible');
                });
            });
        }

        // Inicializar cuando DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFormTextToggle);
        } else {
            initFormTextToggle();
        }
    })();