// Contacto_Emergencias.js — gestión del formulario de contactos de emergencia
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Contacto_Emergencias.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-contacto');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idContactoInput = document.getElementById('id_contacto');

    // Campos
    const nombreInput = document.getElementById('nombre_contacto');
    const relacionInput = document.getElementById('relacion');
    const telefonoInput = document.getElementById('numero_telefono');
    const empleadoInput = document.getElementById('id_empleado');

    // ---------- Formato del campo teléfono ----------
    if (telefonoInput) {
        telefonoInput.maxLength = 9;
        telefonoInput.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '');
            if (digits.length > 8) digits = digits.slice(0, 8);
            if (digits.length > 4) this.value = digits.slice(0, 4) + '-' + digits.slice(4);
            else this.value = digits;
        });
    }

    // ---------- Validaciones ----------
    function validarFormulario() {
        const nombre = nombreInput?.value.trim();
        const relacion = relacionInput?.value.trim();
        const telefono = telefonoInput?.value.trim();
        const empleado = empleadoInput?.value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined')
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
            else alert(msg);
        };

    if (!nombre) { showWarning('Debe ingresar el nombre del contacto.'); nombreInput.focus(); return false; }
        if (!empleado) { showWarning('Debe seleccionar el empleado asociado.'); empleadoInput.focus(); return false; }

        // Validar formato del teléfono
        if (telefono && !/^\d{4}-\d{4}$/.test(telefono)) {
            showWarning('El número de teléfono debe tener formato 0000-0000.');
            telefonoInput.focus();
            return false;
        }

        // Validar nombre: usar misma regla que Empleados/Puestos (solo letras y espacios)
        const nameRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/;
        const nombreNorm = nombre.replace(/\s+/g, ' ');
        if (!nameRegex.test(nombreNorm) || nombreNorm.length < 2 || nombreNorm.length > 60) {
            showWarning('El nombre solo debe contener letras y espacios (2-60 caracteres).');
            nombreInput.focus();
            return false;
        }

        // Validar relación (opcional) usando misma regla que nombres (2-40)
        if (relacion) {
            const relNorm = relacion.replace(/\s+/g, ' ');
            const nameRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/;
            if (!nameRegex.test(relNorm) || relNorm.length < 2 || relNorm.length > 40) {
                showWarning('La relación solo debe contener letras y espacios (2-40 caracteres).');
                relacionInput.focus();
                return false;
            }
        }

        return true;
    }

    // ---------- UI Helpers ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idContactoInput) idContactoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    // Sanitizar y formatear el campo nombre_contacto (igual que Empleados/Puestos)
    (function initNombreSanitizer() {
        if (!nombreInput) return;
        const nameSanitizeRegex = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g;
        nombreInput.addEventListener('input', function () {
            let v = this.value || '';
            v = v.replace(nameSanitizeRegex, '');
            v = v.replace(/\s+/g, ' ');
            this.value = v;
        });
        nombreInput.addEventListener('blur', function () {
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

    // Sanitizar y formatear el campo relacion (igual que nombre)
    (function initRelacionSanitizer() {
        if (!relacionInput) return;
        const nameSanitizeRegex = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g;
        relacionInput.addEventListener('input', function () {
            let v = this.value || '';
            v = v.replace(nameSanitizeRegex, '');
            v = v.replace(/\s+/g, ' ');
            this.value = v;
        });
        relacionInput.addEventListener('blur', function () {
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
        desbloquearEmpleadoEnCreacion();
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
                title: 'Guardar contacto',
                text: '¿Deseas registrar este contacto de emergencia?',
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
                title: 'Actualizar contacto',
                text: '¿Deseas guardar los cambios?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si',
                cancelButtonText: 'Cancelar'
            }).then(res => { if (res.isConfirmed) doSubmit(); });
        }
    });

    btnCancelar?.addEventListener('click', function () {
        limpiarFormulario();
        desbloquearEmpleadoEnCreacion();
        btnCancelar.style.display = 'none';
    });

    // ---------- Helpers para bloqueo de empleado en edición ----------
    function bloquearEmpleadoEnEdicion() {
        if (!empleadoInput) return;
        try {
            empleadoInput.disabled = true;
            // Añadir input oculto para enviar id_empleado
            let hidden = document.getElementById('id_empleado_hidden');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'id_empleado';
                hidden.id = 'id_empleado_hidden';
                form.appendChild(hidden);
            }
            hidden.value = empleadoInput.value || '';
        } catch (_) {}
    }

    function desbloquearEmpleadoEnCreacion() {
        if (!empleadoInput) return;
        try {
            empleadoInput.disabled = false;
            const hidden = document.getElementById('id_empleado_hidden');
            if (hidden && hidden.parentNode) hidden.parentNode.removeChild(hidden);
        } catch (_) {}
    }

    // ---------- Editar por empleado (seleccionar contacto) ----------
    document.querySelectorAll('.editar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const contactsJson = this.getAttribute('data-contacts') || '[]';
            let contacts = [];
            try { contacts = JSON.parse(contactsJson); } catch (e) { contacts = []; }
            if (!contacts.length) {
                Swal.fire('Sin contactos', 'Este empleado no tiene contactos registrados.', 'info');
                return;
            }

            const inputOptions = {};
            contacts.forEach(c => {
                const label = `${c.nombre}${c.relacion ? ' (' + c.relacion + ')' : ''}${c.telefono ? ' — ' + c.telefono : ''}`;
                inputOptions[c.id_contacto] = label;
            });

            Swal.fire({
                title: 'Seleccione contacto a editar',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: '-- Seleccione --',
                showCancelButton: true
            }).then(res => {
                if (res.isConfirmed && res.value) {
                    const selId = res.value;
                    const sel = contacts.find(x => String(x.id_contacto) === String(selId));
                    if (!sel) return;
                    // Llenar formulario
                    idContactoInput.value = sel.id_contacto || '';
                    nombreInput.value = sel.nombre || '';
                    relacionInput.value = sel.relacion || '';
                    telefonoInput.value = sel.telefono || '';
                    empleadoInput.value = this.getAttribute('data-id_empleado') || '';

                    habilitarCampos();
                    bloquearEmpleadoEnEdicion();
                    mostrarBotonesActualizar();
                }
            });
        });
    });

    // ---------- Eliminar por empleado (seleccionar contacto) ----------
    document.querySelectorAll('.eliminar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const contactsJson = this.getAttribute('data-contacts') || '[]';
            let contacts = [];
            try { contacts = JSON.parse(contactsJson); } catch (e) { contacts = []; }
            if (!contacts.length) {
                Swal.fire('Sin contactos', 'Este empleado no tiene contactos registrados.', 'info');
                return;
            }

            const inputOptions = {};
            contacts.forEach(c => {
                const label = `${c.nombre}${c.relacion ? ' (' + c.relacion + ')' : ''}${c.telefono ? ' — ' + c.telefono : ''}`;
                inputOptions[c.id_contacto] = label;
            });

            Swal.fire({
                title: 'Seleccione contacto a eliminar',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: '-- Seleccione --',
                showCancelButton: true
            }).then(res => {
                if (res.isConfirmed && res.value) {
                    const selId = res.value;
                    Swal.fire({
                        title: '¿Eliminar contacto?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si',
                        cancelButtonText: 'Cancelar'
                    }).then(r2 => {
                        if (r2.isConfirmed) {
                            const f = document.createElement('form');
                            f.method = 'post';
                            f.style.display = 'none';
                            const op = document.createElement('input');
                            op.name = 'operacion'; op.value = 'eliminar';
                            const idf = document.createElement('input');
                            idf.name = 'id_contacto'; idf.value = selId;
                            f.appendChild(op); f.appendChild(idf);
                            document.body.appendChild(f);
                            f.submit();
                        }
                    });
                }
            });
        });
    });

    // ---------- Mostrar mensaje desde servidor ----------
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

    desbloquearEmpleadoEnCreacion();

    console.log('[Contacto_Emergencias.js] DOMContentLoaded: fin');
});

   (function(){

        function initFormTextToggle() {
            var form = document.getElementById('form-contacto');
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
