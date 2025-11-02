// ViajesVehiculos.js — gestión de formulario de viajes con SweetAlert2
// CON VALIDACIONES MEJORADAS Y FORMATO UNIFICADO

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-viaje');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idViajeInput = document.getElementById('id_viaje');

    // Configuración de validaciones
    const configValidaciones = {
        descripcion_viaje: {
            min: 0,
            max: 500,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , ; : ! ? ( ) # &",
            opcional: true
        },
        tiempo_aproximado_min: {
            min: 1,
            max: 10080, // 7 días en minutos
            opcional: true
        }
    };

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para sanitizar y validar campos de texto
    function sanitizarTexto(texto, campo) {
        if (!texto) return '';
        
        // Eliminar espacios en blanco al inicio y final
        texto = texto.trim();
        
        // Reemplazar múltiples espacios por uno solo
        texto = texto.replace(/\s+/g, ' ');
        
        // Validar contra XSS (eliminar etiquetas HTML)
        texto = texto.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        texto = texto.replace(/<[^>]*>/g, '');
        
        // Validar caracteres permitidos según el campo
        const config = configValidaciones[campo];
        if (config && config.regex && !config.regex.test(texto)) {
            return null; // Indica que el texto contiene caracteres no permitidos
        }
        
        return texto;
    }

    // Función para mostrar advertencias (formato unificado)
    function showWarning(msg) {
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
    }

    // Validación en tiempo real
    function configurarValidacionEnTiempoReal() {
        // Validación de descripción
        const descripcionInput = document.getElementById('descripcion_viaje');
        if (descripcionInput) {
            descripcionInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'descripcion_viaje');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.descripcion_viaje.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud
                if (valorSanitizado && valorSanitizado.length > configValidaciones.descripcion_viaje.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.descripcion_viaje.max} caracteres`;
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                }
            });
        }

        // Validación de tiempo aproximado
        const tiempoInput = document.getElementById('tiempo_aproximado_min');
        if (tiempoInput) {
            tiempoInput.addEventListener('input', function() {
                const valor = this.value;
                
                if (valor && (valor < configValidaciones.tiempo_aproximado_min.min || valor > configValidaciones.tiempo_aproximado_min.max)) {
                    this.style.borderColor = '#dc3545';
                    this.title = `El tiempo debe estar entre ${configValidaciones.tiempo_aproximado_min.min} y ${configValidaciones.tiempo_aproximado_min.max} minutos`;
                } else if (valor) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                }
            });
        }

        // Validación de fecha (no puede ser en el pasado)
        const fechaInput = document.getElementById('fecha_hora_salida');
        if (fechaInput) {
            fechaInput.addEventListener('change', function() {
                const fechaSeleccionada = new Date(this.value);
                const ahora = new Date();
                
                if (fechaSeleccionada < ahora) {
                    this.style.borderColor = '#dc3545';
                    this.title = 'La fecha y hora no pueden ser en el pasado';
                } else {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                }
            });
        }
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
                    title: 'Registrar viaje',
                    text: '¿Deseas registrar este viaje en el sistema?',
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
                if (confirm('¿Deseas registrar este viaje en el sistema?')) doSubmit();
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
                    title: 'Actualizar viaje',
                    text: '¿Deseas guardar los cambios en este viaje?',
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
            const ruta = sanitizarInput(this.getAttribute('data-ruta'));
            const vehiculo = sanitizarInput(this.getAttribute('data-vehiculo'));
            const piloto = sanitizarInput(this.getAttribute('data-piloto'));
            const acompanante = sanitizarInput(this.getAttribute('data-acompanante'));
            const fecha = sanitizarInput(this.getAttribute('data-fecha'));
            const tiempo = sanitizarInput(this.getAttribute('data-tiempo'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));

            const doFill = () => {
                if (idViajeInput) idViajeInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_ruta').value = ruta || '';
                document.getElementById('id_vehiculo').value = vehiculo || '';
                document.getElementById('id_empleado_piloto').value = piloto || '';
                document.getElementById('id_empleado_acompanante').value = acompanante || '';
                document.getElementById('fecha_hora_salida').value = fecha || '';
                document.getElementById('tiempo_aproximado_min').value = tiempo || '';
                document.getElementById('descripcion_viaje').value = descripcion || '';

                // Disparar eventos de validación para actualizar estilos
                ['descripcion_viaje', 'tiempo_aproximado_min', 'fecha_hora_salida'].forEach(campoId => {
                    const input = document.getElementById(campoId);
                    if (input) {
                        const evento = new Event('input', { bubbles: true });
                        input.dispatchEvent(evento);
                    }
                });

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar viaje',
                    text: `¿Deseas editar el viaje #${id || ''}?`,
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
        if (idViajeInput) idViajeInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        
        // Establecer fecha y hora actual por defecto
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha_hora_salida').value = now.toISOString().slice(0, 16);
        
        // Limpiar estilos de validación
        ['descripcion_viaje', 'tiempo_aproximado_min', 'fecha_hora_salida'].forEach(campoId => {
            const input = document.getElementById(campoId);
            if (input) {
                input.style.borderColor = '';
                input.title = '';
            }
        });
        
        mostrarBotonesGuardar();
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
        const ruta = document.getElementById('id_ruta').value;
        const vehiculo = document.getElementById('id_vehiculo').value;
        const piloto = document.getElementById('id_empleado_piloto').value;
        const fecha = document.getElementById('fecha_hora_salida').value;
        const tiempo = document.getElementById('tiempo_aproximado_min').value;
        const descripcion = document.getElementById('descripcion_viaje');
        const descripcionValor = descripcion.value.trim();
        const descripcionSanitizada = sanitizarTexto(descripcionValor, 'descripcion_viaje');

        // Validar campos requeridos
        if (!ruta) { 
            return showWarning('Seleccione una ruta'); 
        }
        if (!vehiculo) { 
            return showWarning('Seleccione un vehículo'); 
        }
        if (!piloto) { 
            return showWarning('Seleccione un empleado piloto'); 
        }
        if (!fecha) { 
            return showWarning('La fecha y hora de salida son requeridas'); 
        }

        // Validar que la fecha no sea en el pasado
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        if (fechaSeleccionada < ahora) {
            return showWarning('La fecha y hora de salida no pueden ser en el pasado');
        }

        // Validar tiempo aproximado si se proporciona
        if (tiempo && (tiempo < configValidaciones.tiempo_aproximado_min.min || tiempo > configValidaciones.tiempo_aproximado_min.max)) {
            return showWarning(`El tiempo aproximado debe estar entre ${configValidaciones.tiempo_aproximado_min.min} y ${configValidaciones.tiempo_aproximado_min.max} minutos`);
        }

        // Validar descripción (opcional)
        if (descripcionValor && descripcionSanitizada === null) {
            return showWarning(configValidaciones.descripcion_viaje.mensaje);
        }
        
        if (descripcionValor.length > configValidaciones.descripcion_viaje.max) {
            return showWarning(`La descripción no puede exceder los ${configValidaciones.descripcion_viaje.max} caracteres`);
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert (formato unificado)
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            
            // Obtener información del viaje desde la fila de la tabla
            const fila = this.closest('tr');
            const idViaje = fila ? fila.querySelector('td:first-child').textContent.trim() : '';
            const ruta = fila ? fila.querySelector('td:nth-child(2)').textContent.trim() : '';
            const fecha = fila ? fila.querySelector('td:nth-child(6)').textContent.trim() : '';
            
            const nombreViaje = `Viaje #${idViaje} - ${ruta} (${fecha})`;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar viaje?',
                    html: `¿Estás seguro de que deseas eliminar ${nombreViaje} del sistema?<br><br>
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
                if (confirm(`¿Eliminar ${nombreViaje}? Esta acción no se puede deshacer.`)) {
                    frm.submit();
                }
            }
        });
    });

    // Mostrar mensaje enviado desde el servidor (si existe) - Formato unificado
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

    // Inicializar estado del formulario y validaciones
    mostrarBotonesGuardar();
    configurarValidacionEnTiempoReal();

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