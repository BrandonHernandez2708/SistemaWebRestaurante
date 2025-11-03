// Correo_Empleados.js — gestión del formulario de correos de empleados
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Correo_Empleados.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-correo');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCorreoInput = document.getElementById('id_correo');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const correoInput = document.getElementById('direccion_correo');

    // ---------- Funciones UI ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idCorreoInput) idCorreoInput.value = '';
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
        const correo = correoInput ? correoInput.value.trim() : '';

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

        if (!correo) {
            showWarning('Debe ingresar una dirección de correo.');
            if (correoInput) correoInput.focus();
            return false;
        }

        // Validar formato de correo
        const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexCorreo.test(correo)) {
            showWarning('Formato de correo inválido. Ejemplo: usuario@dominio.com');
            if (correoInput) correoInput.focus();
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
        if (evt && typeof evt.preventDefault === 'function') evt.preventDefault();
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar correo',
                    text: '¿Deseas registrar este correo electrónico?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((res) => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas registrar este correo electrónico?')) doSubmit();
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
                    title: 'Actualizar correo',
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

    // ---------- Editar por empleado (selección de correo con SweetAlert) ----------
    document.querySelectorAll('.editar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const correosJson = this.getAttribute('data-correos') || '[]';
            let correos = [];
            try { correos = JSON.parse(correosJson); } catch (e) { correos = []; }
            if (!correos.length) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Sin correos', 'Este empleado no tiene correos registrados.', 'info');
                } else {
                    alert('Este empleado no tiene correos registrados.');
                }
                return;
            }

            const inputOptions = {};
            correos.forEach(c => { inputOptions[c.id_correo] = c.direccion; });

            Swal.fire({
                title: 'Seleccione correo a editar',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: '-- Seleccione correo --',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed && res.value) {
                    const selId = res.value;
                    const sel = correos.find(x => String(x.id_correo) === String(selId));
                    if (sel) {
                        idCorreoInput.value = sel.id_correo || '';
                        idEmpleadoInput.value = this.getAttribute('data-id_empleado') || '';
                        correoInput.value = sel.direccion || '';
                        habilitarCampos();
                        operacionInput.value = 'actualizar';
                        mostrarBotonesActualizar();
                    }
                }
            });
        });
    });

    // ---------- Eliminar por empleado (selección con SweetAlert) ----------
    document.querySelectorAll('.eliminar-emp-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const correosJson = this.getAttribute('data-correos') || '[]';
            let correos = [];
            try { correos = JSON.parse(correosJson); } catch (e) { correos = []; }
            if (!correos.length) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Sin correos', 'Este empleado no tiene correos registrados.', 'info');
                } else {
                    alert('Este empleado no tiene correos registrados.');
                }
                return;
            }

            const inputOptions = {};
            correos.forEach(c => { inputOptions[c.id_correo] = c.direccion; });

            Swal.fire({
                title: 'Seleccione correo a eliminar',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: '-- Seleccione correo --',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed && res.value) {
                    const selId = res.value;
                    Swal.fire({
                        title: '¿Eliminar correo?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si',
                        cancelButtonText: 'Cancelar'
                    }).then((r2) => {
                        if (r2.isConfirmed) {
                            const f = document.createElement('form');
                            f.method = 'post';
                            f.style.display = 'none';
                            const op = document.createElement('input');
                            op.name = 'operacion'; op.value = 'eliminar';
                            const idf = document.createElement('input');
                            idf.name = 'id_correo'; idf.value = selId;
                            f.appendChild(op); f.appendChild(idf);
                            document.body.appendChild(f);
                            f.submit();
                        }
                    });
                }
            });
        });
    });

    // ---------- Mostrar mensaje desde el servidor ----------
    try {
            if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : (m.tipo === 'warning' ? 'warning' : 'error');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: icon === 'success' ? 'Éxito' : 'Atención',
                    text: m.text,
                    icon: icon
                });
            } else {
                alert(m.text);
            }
            try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
        }
    } catch (e) {
        console.warn('Error mostrando mensaje del servidor', e);
    }

    console.log('[Correo_Empleados.js] DOMContentLoaded: fin');
});

    (function(){

        function initFormTextToggle() {
            var form = document.getElementById('form-correo');
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
