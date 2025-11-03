// TelefonoEmpleados.js — gestión del formulario de teléfonos de empleados

document.addEventListener('DOMContentLoaded', function () {
    console.log('[Telefono_Empleados.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-telefono');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idTelefonoInput = document.getElementById('id_telefono');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const telefonoInput = document.getElementById('numero_telefono');

    // ------------------ Formato y comportamiento del campo teléfono (4-4 -> 9 chars con '-') ------------------
    if (telefonoInput) {
        // Forzar maxlength a 9 (4 dígitos + '-' + 4 dígitos)
        telefonoInput.maxLength = 9;

        // Normaliza la entrada: permite sólo dígitos, inserta '-' automáticamente después de 4 dígitos
        telefonoInput.addEventListener('input', function (evt) {
            const el = this;
            // Obtener sólo los dígitos
            let digits = el.value.replace(/\D/g, '');
            // Limitar a 8 dígitos (4 + 4)
            if (digits.length > 8) digits = digits.slice(0, 8);
            // Insertar guion después de 4 dígitos
            if (digits.length > 4) {
                el.value = digits.slice(0, 4) + '-' + digits.slice(4);
            } else {
                el.value = digits;
            }
        });

        // Evitar teclas no numéricas en keydown (permitir control/backspace/arrow)
        telefonoInput.addEventListener('keydown', function (evt) {
            const allowed = [8, 9, 13, 27, 37, 38, 39, 40, 46]; // backspace, tab, enter, esc, arrows, del
            if (allowed.indexOf(evt.keyCode) !== -1) return;
            // permitir Ctrl/Cmd + teclas
            if (evt.ctrlKey || evt.metaKey) return;
            // números (0-9)
            if ((evt.keyCode >= 48 && evt.keyCode <= 57) || (evt.keyCode >= 96 && evt.keyCode <= 105)) return;
            // bloquear el resto
            evt.preventDefault();
        });
    }

    // ---------- Funciones UI ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idTelefonoInput) idTelefonoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) { btnGuardar.style.display = 'inline-block'; btnGuardar.disabled = false; }
        if (btnActualizar) { btnActualizar.style.display = 'none'; btnActualizar.disabled = true; }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) { btnGuardar.style.display = 'none'; btnGuardar.disabled = true; }
        if (btnActualizar) { btnActualizar.style.display = 'inline-block'; btnActualizar.disabled = false; }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    // ---------- Validaciones ----------
    function validarFormulario() {
        const empleado = idEmpleadoInput ? idEmpleadoInput.value.trim() : '';
        const numero = telefonoInput ? telefonoInput.value.trim() : '';

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
            } else {
                alert(msg);
            }
        };

        if (!empleado) {
            showWarning('Debe seleccionar un empleado.');
            if (idEmpleadoInput) idEmpleadoInput.focus();
            return false;
        }

        if (!numero) {
            showWarning('Debe ingresar un número de teléfono.');
            if (telefonoInput) telefonoInput.focus();
            return false;
        }

        // Formato requerido: 4 dígitos, guion, 4 dígitos -> Ej: 5460-0412
        const regexTelefono = /^\d{4}-\d{4}$/;
        if (!regexTelefono.test(numero)) {
            showWarning('Número inválido. Use el formato 5460-0412 (4 dígitos, guion, 4 dígitos).');
            if (telefonoInput) telefonoInput.focus();
            return false;
        }

        return true;
    }

    // ---------- Botones ----------
    if (btnNuevo) btnNuevo.addEventListener('click', function () {
        limpiarFormulario();
        habilitarCampos();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function (evt) {
        // Evitar el submit por defecto para mostrar primero la confirmación (SweetAlert)
        if (evt && typeof evt.preventDefault === 'function') evt.preventDefault();
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar teléfono',
                    text: '¿Deseas registrar este número?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas registrar este número?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar teléfono',
                    text: '¿Deseas guardar los cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas guardar los cambios?')) doSubmit();
            }
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function () {
        limpiarFormulario();
        if (btnCancelar) btnCancelar.style.display = 'none';
    });

    // ---------- Editar por empleado (selección de número en SweetAlert) ----------
    document.querySelectorAll('.editar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const phonesJson = this.getAttribute('data-phones') || '[]';
            let phones = [];
            try { phones = JSON.parse(phonesJson); } catch (e) { phones = []; }
            if (!phones.length) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('No hay números', 'Este empleado no tiene teléfonos registrados.', 'info');
                } else {
                    alert('Este empleado no tiene teléfonos registrados.');
                }
                return;
            }

            const inputOptions = {};
            phones.forEach(p => { inputOptions[p.id_telefono] = p.numero; });

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Seleccione número a editar',
                    input: 'select',
                    inputOptions: inputOptions,
                    inputPlaceholder: '-- Seleccione número --',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed && res.value) {
                        const selId = res.value;
                        const sel = phones.find(x => String(x.id_telefono) === String(selId));
                        if (sel) {
                            if (idTelefonoInput) idTelefonoInput.value = sel.id_telefono || '';
                            if (idEmpleadoInput) idEmpleadoInput.value = this.getAttribute('data-id_empleado') || '';
                            if (telefonoInput) telefonoInput.value = sel.numero || '';
                            habilitarCampos();
                            if (operacionInput) operacionInput.value = 'actualizar';
                            mostrarBotonesActualizar();
                        }
                    }
                });
            } else {
                // Fallback: prompt simple
                const choices = phones.map(p => p.numero).join('\n');
                const pick = prompt('Números:\n' + choices + '\nEscriba el número a editar:');
                const sel = phones.find(x => x.numero === pick);
                if (sel) {
                    if (idTelefonoInput) idTelefonoInput.value = sel.id_telefono || '';
                    if (idEmpleadoInput) idEmpleadoInput.value = this.getAttribute('data-id_empleado') || '';
                    if (telefonoInput) telefonoInput.value = sel.numero || '';
                    habilitarCampos();
                    if (operacionInput) operacionInput.value = 'actualizar';
                    mostrarBotonesActualizar();
                }
            }
        });
    });

    // ---------- Eliminar por empleado (selección de número) ----------
    document.querySelectorAll('.eliminar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const phonesJson = this.getAttribute('data-phones') || '[]';
            let phones = [];
            try { phones = JSON.parse(phonesJson); } catch (e) { phones = []; }
            if (!phones.length) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('No hay números', 'Este empleado no tiene teléfonos registrados.', 'info');
                } else {
                    alert('Este empleado no tiene teléfonos registrados.');
                }
                return;
            }

            const inputOptions = {};
            phones.forEach(p => { inputOptions[p.id_telefono] = p.numero; });

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Seleccione número a eliminar',
                    input: 'select',
                    inputOptions: inputOptions,
                    inputPlaceholder: '-- Seleccione número --',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed && res.value) {
                        const selId = res.value;
                        // Confirmar eliminación
                        Swal.fire({
                            title: '¿Eliminar teléfono?',
                            text: 'Esta acción no se puede deshacer.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar'
                        }).then((r2) => {
                            if (r2.isConfirmed) {
                                // Crear formulario y enviarlo
                                const f = document.createElement('form');
                                f.method = 'post';
                                f.style.display = 'none';
                                const op = document.createElement('input');
                                op.name = 'operacion'; op.value = 'eliminar';
                                const idf = document.createElement('input');
                                idf.name = 'id_telefono'; idf.value = selId;
                                f.appendChild(op); f.appendChild(idf);
                                document.body.appendChild(f);
                                f.submit();
                            }
                        });
                    }
                });
            } else {
                const choices = phones.map(p => p.numero).join('\n');
                const pick = prompt('Números:\n' + choices + '\nEscriba el número a eliminar:');
                const sel = phones.find(x => x.numero === pick);
                if (sel && confirm('¿Eliminar número ' + sel.numero + '?')) {
                    const f = document.createElement('form');
                    f.method = 'post'; f.style.display='none';
                    const op = document.createElement('input'); op.name='operacion'; op.value='eliminar';
                    const idf = document.createElement('input'); idf.name='id_telefono'; idf.value = sel.id_telefono;
                    f.appendChild(op); f.appendChild(idf); document.body.appendChild(f); f.submit();
                }
            }
        });
    });

    // ---------- Confirmar eliminación ----------
    // Soporte para formularios que no tienen atributo action (los que genera la tabla)
    document.querySelectorAll('form').forEach(f => {
        const isDelete = f.querySelector('input[name="operacion"][value="eliminar"]');
        if (!isDelete) return;
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar teléfono?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) frm.submit(); });
            } else {
                if (confirm('¿Eliminar teléfono?')) frm.submit();
            }
        });
    });

    // ---------- Mostrar mensaje desde el servidor ----------
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : (m.tipo === 'warning' ? 'warning' : 'error');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: icon === 'success' ? 'Éxito' : 'Atención', text: m.text, icon: icon });
            } else {
                alert(m.text);
            }
            try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
        }
    } catch (e) {
        console.warn('Error mostrando mensaje del servidor', e);
    }

    console.log('[Telefono_Empleados.js] DOMContentLoaded: fin');
});

    (function(){

        function initFormTextToggle() {
            var form = document.getElementById('form-telefono');
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