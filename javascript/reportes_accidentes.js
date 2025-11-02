// ReportesAccidentes.js — gestión de formulario de reportes de accidentes con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-accidentes');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idAccidenteInput = document.getElementById('id_accidente');
    const descripcionInput = document.getElementById('descripcion_accidente');
    const fechaHoraInput = document.getElementById('fecha_hora');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para validar descripción
    function validarDescripcion(descripcion) {
        const descripcionRegex = /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\$\&\+\=\/\@\"\'\n\r]+$/;
        return descripcion.length >= 50 && descripcion.length <= 2000 && descripcionRegex.test(descripcion);
    }

    // Función para validar fecha y hora
    function validarFechaHora(fechaHora) {
        const fechaRegex = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/;
        if (!fechaRegex.test(fechaHora)) {
            return false;
        }
        
        const fechaObj = new Date(fechaHora);
        const ahora = new Date();
        const haceUnAnio = new Date();
        haceUnAnio.setFullYear(ahora.getFullYear() - 1);
        
        return fechaObj <= ahora && fechaObj >= haceUnAnio;
    }

    // Validación de descripción en tiempo real
    if (descripcionInput) {
        descripcionInput.addEventListener('input', function () {
            const valor = this.value;
            if (valor.length > 2000) {
                this.value = valor.substring(0, 2000);
                showWarning('La descripción no puede exceder los 2000 caracteres');
            }
            
            // Actualizar contador de caracteres
            actualizarContadorDescripcion(valor.length);
        });

        descripcionInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarDescripcion(valor)) {
                showWarning('La descripción debe tener entre 50 y 2000 caracteres y solo puede contener letras, números, espacios y caracteres comunes de puntuación');
                this.focus();
            }
        });
    }

    // Función para actualizar contador de caracteres
    function actualizarContadorDescripcion(cantidad) {
        let contador = document.getElementById('contador-descripcion');
        if (!contador) {
            contador = document.createElement('div');
            contador.id = 'contador-descripcion';
            contador.className = 'form-text';
            descripcionInput.parentNode.appendChild(contador);
        }
        
        const minCaracteres = 50;
        const maxCaracteres = 2000;
        let color = 'text-muted';
        
        if (cantidad < minCaracteres) {
            color = 'text-danger';
        } else if (cantidad > maxCaracteres * 0.9) {
            color = 'text-warning';
        } else if (cantidad >= minCaracteres) {
            color = 'text-success';
        }
        
        contador.innerHTML = `<span class="${color}">${cantidad}/${maxCaracteres} caracteres</span>`;
        
        if (cantidad < minCaracteres) {
            contador.innerHTML += ` <span class="text-danger">(mínimo ${minCaracteres})</span>`;
        }
    }

    // Validación de fecha y hora en tiempo real
    if (fechaHoraInput) {
        fechaHoraInput.addEventListener('change', function() {
            const fechaSeleccionada = this.value;
            
            if (!validarFechaHora(fechaSeleccionada)) {
                showWarning('La fecha y hora del accidente no pueden ser futuras ni mayores a 1 año atrás');
                const ahora = new Date();
                ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
                this.value = ahora.toISOString().slice(0, 16);
            }
        });
    }

    // Validación de selecciones
    const viajeSelect = document.getElementById('id_viaje');
    const empleadoSelect = document.getElementById('id_empleado');

    if (viajeSelect) {
        viajeSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un viaje válido');
                this.value = '';
            }
        });
    }

    if (empleadoSelect) {
        empleadoSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un empleado válido');
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
                if (operacionInput) operacionInput.value = 'crear_accidente';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar reporte de accidente',
                    html: `¿Estás seguro de que deseas registrar este reporte de accidente?<br><br>
                          <span class="text-warning">⚠️ Esta acción registrará oficialmente un incidente en el sistema.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    iconColor: '#dc3545'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas registrar este reporte de accidente?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_accidente';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar reporte de accidente',
                    text: '¿Deseas guardar los cambios en este reporte?',
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
            const viaje = sanitizarInput(this.getAttribute('data-viaje'));
            const empleado = sanitizarInput(this.getAttribute('data-empleado'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));
            const fecha = sanitizarInput(this.getAttribute('data-fecha'));

            const doFill = () => {
                if (idAccidenteInput) idAccidenteInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_viaje').value = viaje || '';
                document.getElementById('id_empleado').value = empleado || '';
                descripcionInput.value = descripcion || '';
                
                // Formatear fecha para el input datetime-local
                fechaHoraInput.value = fecha || '';

                // Actualizar contador de caracteres
                actualizarContadorDescripcion(descripcion.length);

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar reporte de accidente',
                    text: `¿Deseas editar el reporte de accidente #${id || ''}?`,
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
        if (idAccidenteInput) idAccidenteInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_accidente';
        // Establecer fecha y hora actual por defecto
        const now = new Date();
        // Ajustar a la zona horaria local
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        fechaHoraInput.value = now.toISOString().slice(0, 16);
        
        // Actualizar contador de caracteres
        actualizarContadorDescripcion(0);
        
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_viaje').focus();
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
        const viaje = document.getElementById('id_viaje').value;
        const empleado = document.getElementById('id_empleado').value;
        const descripcion = descripcionInput.value.trim();
        const fecha = fechaHoraInput.value;

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Validación requerida', 
                    text: msg,
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert(msg);
            }
            return false;
        };

        // Validar viaje
        if (!viaje) { 
            return showWarning('El viaje relacionado es requerido'); 
        }
        if (!/^\d+$/.test(viaje)) {
            return showWarning('El ID del viaje debe ser un número válido');
        }

        // Validar empleado
        if (!empleado) { 
            return showWarning('El empleado que reporta es requerido'); 
        }
        if (!/^\d+$/.test(empleado)) {
            return showWarning('El ID del empleado debe ser un número válido');
        }

        // Validar descripción
        if (!descripcion) { 
            return showWarning('La descripción del accidente es requerida'); 
        }
        
        if (descripcion.length < 50) {
            return showWarning('La descripción debe tener al menos 50 caracteres');
        }
        
        if (descripcion.length > 2000) {
            return showWarning('La descripción no puede exceder los 2000 caracteres');
        }
        
        if (!validarDescripcion(descripcion)) {
            return showWarning('La descripción contiene caracteres no permitidos');
        }

        // Validar fecha y hora
        if (!fecha) { 
            return showWarning('La fecha y hora del accidente son requeridas'); 
        }
        
        if (!validarFechaHora(fecha)) {
            return showWarning('La fecha y hora del accidente no pueden ser futuras ni mayores a 1 año atrás');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idAccidente = this.querySelector('input[name="id_accidente"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar reporte de accidente?',
                    html: `¿Estás seguro de que deseas eliminar este reporte de accidente del sistema?<br><br>
                          <span class="text-danger">⚠️ Esta acción no se puede deshacer y eliminará permanentemente el registro del accidente.</span>`,
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
                if (confirm('¿Eliminar reporte de accidente? Esta acción no se puede deshacer.')) {
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