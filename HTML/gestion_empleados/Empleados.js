// Empleados.js — gestión de formulario de empleados

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-empleado');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const idDepartamentoInput = document.getElementById('id_departamento');
    const idPuestoInput = document.getElementById('id_puesto');
    const contenedorLista = document.getElementById('lista-empleados');

    // DPI: formato 4-5-4
    const dpiInput = document.getElementById('dpi');
    function formatDPIValue(value) {
        const digits = (value || '').toString().replace(/\D/g, '');
        const part1 = digits.slice(0, 4);
        const part2 = digits.slice(4, 9);
        const part3 = digits.slice(9, 13);
        return [part1, part2, part3].filter(Boolean).join(' ');
    }
    if (dpiInput) {
        dpiInput.addEventListener('input', function () {
            const formatted = formatDPIValue(this.value);
            this.value = formatted;
            try { this.setSelectionRange(this.value.length, this.value.length); } catch (err) {}
        });
        dpiInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = formatDPIValue(text);
        });
    }

    // Nombre / Apellido: sanitizar y formatear
    const nombreInput = document.getElementById('nombre_empleado');
    const apellidoInput = document.getElementById('apellido_empleado');
    const nameSanitizeRegex = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g;

    function sanitizeAndFormatNameField(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            let v = this.value || '';
            v = v.replace(nameSanitizeRegex, '');
            v = v.replace(/\s+/g, ' ');
            this.value = v;
        });
        el.addEventListener('blur', function () {
            let v = (this.value || '').trim();
            if (!v) return;
            // conservar si el usuario ingresó todo en mayúsculas (por ejemplo: ERNESTO DAVID)
            if (v === v.toUpperCase()) {
                this.value = v.replace(/\s+/g, ' ');
                return;
            }
            // Si no, convertir a Title Case (primera letra mayúscula, resto minúsculas)
            const parts = v.split(' ').filter(Boolean);
            const formatted = parts.map(p => {
                const first = p.charAt(0).toLocaleUpperCase('es-ES');
                const rest = p.slice(1).toLocaleLowerCase('es-ES');
                return first + rest;
            }).join(' ');
            this.value = formatted;
        });
    }
    sanitizeAndFormatNameField(nombreInput);
    sanitizeAndFormatNameField(apellidoInput);

    // Helpers UI
    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
        // btnNuevo no se deshabilita
        // enfocar el primer campo no hidden
        for (let i = 0; i < inputs.length; i++) {
            if (inputs[i].type !== 'hidden') { inputs[i].focus(); break; }
        }
    }

    // Botones
    if (btnNuevo) btnNuevo.addEventListener('click', function () {
        limpiarFormulario();
        habilitarCampos();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar empleado',
                    text: '¿Deseas guardar este empleado?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas guardar este empleado?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar empleado',
                    text: '¿Deseas guardar los cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) doSubmit(); });
            } else {
                if (confirm('¿Deseas guardar los cambios?')) doSubmit();
            }
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function() {
        limpiarFormulario();
        // Al cancelar no deshabilitamos la interfaz: simplemente ocultamos el botón Cancelar
        try { if (btnCancelar) btnCancelar.style.display = 'none'; } catch(e) {}
    });

    // Editar desde la tabla
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const dpi = this.getAttribute('data-dpi');
            const nombre = this.getAttribute('data-nombre');
            const apellido = this.getAttribute('data-apellido');
            const departamento = this.getAttribute('data-departamento');
            const puesto = this.getAttribute('data-puesto');

            const doFill = () => {
                if (idEmpleadoInput) idEmpleadoInput.value = id || '';
                const dpiEl = document.getElementById('dpi');
                const nombreEl = document.getElementById('nombre_empleado');
                const apellidoEl = document.getElementById('apellido_empleado');
                if (dpiEl) dpiEl.value = dpi || '';
                if (nombreEl) nombreEl.value = nombre || '';
                if (apellidoEl) apellidoEl.value = apellido || '';

                // Los <select> usan sólo el ID como value; asignar directamente
                if (idDepartamentoInput) {
                    idDepartamentoInput.value = departamento || '';
                }
                if (idPuestoInput) {
                    idPuestoInput.value = puesto || '';
                }

                habilitarCampos();
                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar empleado',
                    text: '¿Deseas editar al empleado "' + (nombre || id) + '"?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) doFill();
                });
            } else {
                doFill();
            }
        });
    });

    function limpiarFormulario() {
        if (form) form.reset();
        if (idEmpleadoInput) idEmpleadoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) { btnGuardar.style.display = 'inline-block'; btnGuardar.disabled = false; }
        if (btnActualizar) { btnActualizar.style.display = 'none'; btnActualizar.disabled = true; }
        // Mostrar también el botón cancelar junto a guardar para que el usuario pueda
        // volver atrás sin recargar la página.
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) { btnGuardar.style.display = 'none'; btnGuardar.disabled = true; }
        if (btnActualizar) { btnActualizar.style.display = 'inline-block'; btnActualizar.disabled = false; }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function validarFormulario() {
        const dpiEl = document.getElementById('dpi');
        const nombreEl = document.getElementById('nombre_empleado');
        const apellidoEl = document.getElementById('apellido_empleado');
        const departamentoEl = document.getElementById('id_departamento');
        const puestoEl = document.getElementById('id_puesto');

        const dpi = dpiEl ? dpiEl.value.trim() : '';
        const nombre = nombreEl ? nombreEl.value.trim() : '';
        const apellido = apellidoEl ? apellidoEl.value.trim() : '';
        const departamento = departamentoEl ? departamentoEl.value : '';
        const puesto = puestoEl ? puestoEl.value : '';

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
            } else {
                alert(msg);
            }
        };

    if (!dpi) { showWarning('El DPI es requerido'); if (dpiEl) dpiEl.focus(); return false; }

    // Comprobar que tenga 13 dígitos (ignorando espacios)
    const dpiDigits = dpi.replace(/\D/g, '');
    if (dpiDigits.length !== 13) { showWarning('El DPI debe contener 13 dígitos'); if (dpiEl) dpiEl.focus(); return false; }
        
    // Validar nombre y apellido: sólo letras y espacios
        const nameRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/;
        if (!nombre) { showWarning('El nombre es requerido'); if (nombreEl) nombreEl.focus(); return false; }
        const nombreNorm = nombre.replace(/\s+/g, ' ');
        if (!nameRegex.test(nombreNorm)) { showWarning('El nombre sólo debe contener letras y espacios'); if (nombreEl) nombreEl.focus(); return false; }
        if (!apellido) { showWarning('El apellido es requerido'); if (apellidoEl) apellidoEl.focus(); return false; }
        const apellidoNorm = apellido.replace(/\s+/g, ' ');
        if (!nameRegex.test(apellidoNorm)) { showWarning('El apellido sólo debe contener letras y espacios'); if (apellidoEl) apellidoEl.focus(); return false; }
        if (!departamento) { showWarning('El departamento es requerido'); if (departamentoEl) departamentoEl.focus(); return false; }
        if (!puesto) { showWarning('El puesto es requerido'); if (puestoEl) puestoEl.focus(); return false; }

        return true;
    }

    // Confirmar eliminación con SweetAlert si existe
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar empleado?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        frm.submit();
                    }
                });
            } else {
                if (confirm('¿Eliminar empleado?')) frm.submit();
            }
        });
    });

    // Mostrar mensaje enviado desde el servidor (si existe)
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : 'error';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: icon === 'success' ? 'Éxito' : 'Atención', text: m.text, icon: icon });
            } else {
                alert(m.text);
            }
            // limpiar para no mostrar de nuevo
            try { delete window.__mensaje; } catch(e) { window.__mensaje = null; }
        }
    } catch (e) { /* no bloquear la carga si falla */ }

    // La lista de empleados es fija en la interfaz; asegurar visibilidad
    if (contenedorLista) { try { contenedorLista.style.display = 'block'; } catch (e) {} }

});

    (function(){
        // Muestra el small.help-text dentro del mismo contenedor (.col-*) cuando el input/select/textarea recibe foco
        function initFormTextToggle() {
            var form = document.getElementById('form-empleado');
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