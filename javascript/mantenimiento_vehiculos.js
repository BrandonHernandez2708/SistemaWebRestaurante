// MantenimientoVehiculos.js — gestión de formulario de mantenimiento con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-mantenimiento');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idMantenimientoInput = document.getElementById('id_mantenimiento');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para validar descripción
    function validarDescripcion(descripcion) {
        const descripcionRegex = /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\$\&\+\=\/\@]+$/;
        return descripcion.length >= 5 && descripcion.length <= 500 && descripcionRegex.test(descripcion);
    }

    // Función para validar fecha
    function validarFecha(fecha) {
        const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!fechaRegex.test(fecha)) {
            return false;
        }
        
        const fechaObj = new Date(fecha);
        const hoy = new Date();
        const hace10Anios = new Date();
        hace10Anios.setFullYear(hoy.getFullYear() - 10);
        
        return fechaObj <= hoy && fechaObj >= hace10Anios;
    }

    // Validación de descripción en tiempo real
    const descripcionInput = document.getElementById('descripcion_mantenimiento');
    if (descripcionInput) {
        descripcionInput.addEventListener('input', function () {
            const valor = this.value;
            if (valor.length > 500) {
                this.value = valor.substring(0, 500);
                showWarning('La descripción no puede exceder los 500 caracteres');
            }
        });

        descripcionInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarDescripcion(valor)) {
                showWarning('La descripción debe tener entre 5 y 500 caracteres y solo puede contener letras, números, espacios y caracteres comunes de puntuación');
                this.focus();
            }
        });
    }

    // Validación de costo en tiempo real
    const costoInput = document.getElementById('costo_mantenimiento');
    if (costoInput) {
        costoInput.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9.]/g, '');
            
            // Permitir solo un punto decimal
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Limitar a 2 decimales
            if (parts.length === 2 && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            
            this.value = value;
            
            // Asegurar que el valor sea positivo y dentro de límites
            const valorNum = parseFloat(value) || 0;
            if (valorNum < 0) {
                this.value = '0.01';
            } else if (valorNum > 1000000) {
                this.value = '1000000';
                showWarning('El costo no puede ser mayor a Q 1,000,000.00');
            }
        });

        costoInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseFloat(this.value))) {
                const valor = parseFloat(this.value);
                if (valor <= 0) {
                    this.value = '0.01';
                } else if (valor > 1000000) {
                    this.value = '1000000';
                } else {
                    this.value = valor.toFixed(2);
                }
            } else if (this.value === '') {
                this.value = '0.01';
            }
        });
    }

    // Validación de fecha en tiempo real
    const fechaInput = document.getElementById('fecha_mantenimiento');
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const fechaSeleccionada = this.value;
            
            if (!validarFecha(fechaSeleccionada)) {
                showWarning('La fecha de mantenimiento no puede ser futura ni mayor a 10 años atrás');
                const hoy = new Date().toISOString().split('T')[0];
                this.value = hoy;
            }
        });
    }

    // Validación de selecciones
    const vehiculoSelect = document.getElementById('id_vehiculo');
    const tallerSelect = document.getElementById('id_taller');

    if (vehiculoSelect) {
        vehiculoSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un vehículo válido');
                this.value = '';
            }
        });
    }

    if (tallerSelect) {
        tallerSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un taller válido');
                this.value = '';
            }
        });
    }

    // Botones
    if (btnNuevo) btnNuevo.addEventListener('click', function () {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar mantenimiento',
                    text: '¿Deseas registrar este mantenimiento en el sistema?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas registrar este mantenimiento en el sistema?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar mantenimiento',
                    text: '¿Deseas guardar los cambios en este mantenimiento?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas guardar los cambios?')) doSubmit();
            }
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Cancelar cambios',
                text: '¿Estás seguro de que deseas cancelar? Se perderán los cambios no guardados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Continuar editando',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    limpiarFormulario();
                    mostrarBotonesGuardar();
                }
            });
        } else {
            if (confirm('¿Cancelar cambios?')) {
                limpiarFormulario();
                mostrarBotonesGuardar();
            }
        }
    });

    // Editar desde la tabla
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = sanitizarInput(this.getAttribute('data-id'));
            const vehiculo = sanitizarInput(this.getAttribute('data-vehiculo'));
            const taller = sanitizarInput(this.getAttribute('data-taller'));
            const fecha = sanitizarInput(this.getAttribute('data-fecha'));
            const costo = sanitizarInput(this.getAttribute('data-costo'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));

            const doFill = () => {
                if (idMantenimientoInput) idMantenimientoInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_vehiculo').value = vehiculo || '';
                document.getElementById('id_taller').value = taller || '';
                document.getElementById('fecha_mantenimiento').value = fecha || '';
                
                // Formatear costo a 2 decimales
                if (costo && !isNaN(parseFloat(costo))) {
                    document.getElementById('costo_mantenimiento').value = parseFloat(costo).toFixed(2);
                } else {
                    document.getElementById('costo_mantenimiento').value = '0.01';
                }
                
                document.getElementById('descripcion_mantenimiento').value = descripcion || '';

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar mantenimiento',
                    text: `¿Deseas editar el mantenimiento #${id || ''}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, editar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d'
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
        if (idMantenimientoInput) idMantenimientoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Establecer valores por defecto
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_mantenimiento').value = hoy;
        document.getElementById('costo_mantenimiento').value = '0.01';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_vehiculo').focus();
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) { 
            btnGuardar.style.display = 'inline-block'; 
            btnGuardar.disabled = false; 
        }
        if (btnActualizar) { 
            btnActualizar.style.display = 'none'; 
            btnActualizar.disabled = true; 
        }
        if (btnCancelar) btnCancelar.style.display = 'none';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) { 
            btnGuardar.style.display = 'none'; 
            btnGuardar.disabled = true; 
        }
        if (btnActualizar) { 
            btnActualizar.style.display = 'inline-block'; 
            btnActualizar.disabled = false; 
        }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    // FUNCIÓN DE VALIDACIÓN COMPLETA DEL FORMULARIO
    function validarFormularioCompleto() {
        const vehiculo = document.getElementById('id_vehiculo').value;
        const taller = document.getElementById('id_taller').value;
        const fecha = document.getElementById('fecha_mantenimiento').value;
        const costo = document.getElementById('costo_mantenimiento').value;
        const descripcion = document.getElementById('descripcion_mantenimiento').value.trim();

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Validación', 
                    text: msg,
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert(msg);
            }
            return false;
        };

        // Validar vehículo
        if (!vehiculo) { 
            return showWarning('Seleccione un vehículo'); 
        }
        if (!/^\d+$/.test(vehiculo)) {
            return showWarning('El ID del vehículo debe ser un número válido');
        }

        // Validar taller
        if (!taller) { 
            return showWarning('Seleccione un taller'); 
        }
        if (!/^\d+$/.test(taller)) {
            return showWarning('El ID del taller debe ser un número válido');
        }

        // Validar fecha
        if (!fecha) { 
            return showWarning('La fecha de mantenimiento es requerida'); 
        }
        
        if (!validarFecha(fecha)) {
            return showWarning('La fecha de mantenimiento no puede ser futura ni mayor a 10 años atrás');
        }

        // Validar costo
        if (!costo) { 
            return showWarning('El costo es requerido'); 
        }
        
        const costoNum = parseFloat(costo);
        if (isNaN(costoNum)) {
            return showWarning('El costo debe ser un número válido');
        }
        
        if (costoNum <= 0) {
            return showWarning('El costo debe ser mayor a cero');
        }
        
        // Validar formato decimal del costo
        const partesCosto = costo.toString().split('.');
        if (partesCosto.length > 1 && partesCosto[1].length > 2) {
            return showWarning('El costo no puede tener más de 2 decimales');
        }

        // Validar que el costo no sea excesivamente alto
        if (costoNum > 1000000) {
            return showWarning('El costo no puede ser mayor a Q 1,000,000.00');
        }

        // Validar descripción
        if (!descripcion) { 
            return showWarning('La descripción del mantenimiento es requerida'); 
        }
        
        if (descripcion.length < 5) {
            return showWarning('La descripción debe tener al menos 5 caracteres');
        }
        
        if (descripcion.length > 500) {
            return showWarning('La descripción no puede exceder los 500 caracteres');
        }
        
        if (!validarDescripcion(descripcion)) {
            return showWarning('La descripción contiene caracteres no permitidos');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idMantenimiento = this.querySelector('input[name="id_mantenimiento"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar mantenimiento?',
                    html: `¿Estás seguro de que deseas eliminar este mantenimiento del sistema?<br><br>
                          <span class="text-danger">⚠️ Esta acción no se puede deshacer.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        frm.submit();
                    }
                });
            } else {
                if (confirm('¿Eliminar mantenimiento? Esta acción no se puede deshacer.')) {
                    frm.submit();
                }
            }
        });
    });

    // Mostrar mensaje enviado desde el servidor (si existe)
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : 'error';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    title: icon === 'success' ? 'Éxito' : 'Atención', 
                    text: m.text, 
                    icon: icon,
                    confirmButtonColor: icon === 'success' ? '#28a745' : '#dc3545'
                });
            } else {
                alert(m.text);
            }
            // limpiar para no mostrar de nuevo
            try { delete window.__mensaje; } catch(e) { window.__mensaje = null; }
        }
    } catch (e) { /* no bloquear la carga si falla */ }

    // Inicializar estado del formulario
    limpiarFormulario();

    // Prevenir envío de formulario con Enter en campos individuales
    if (form) {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.tagName === 'TEXTAREA') {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});