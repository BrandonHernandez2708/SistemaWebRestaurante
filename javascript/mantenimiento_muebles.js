// MantenimientoMuebles.js — gestión de formulario de mantenimiento de muebles con SweetAlert2
// CON VALIDACIONES MEJORADAS Y FORMATO UNIFICADO

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-mantenimiento');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idMantenimientoInput = document.getElementById('id_mantenimiento_muebles');

    // Configuración de validaciones
    const configValidaciones = {
        descripcion_mantenimiento: {
            min: 10,
            max: 1000,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , ; : ! ? ( ) # &"
        },
        codigo_serie: {
            min: 3,
            max: 50,
            regex: /^[A-Za-z0-9\-\_\.]+$/,
            mensaje: "Solo letras, números, guiones, puntos y guiones bajos"
        },
        costo_mantenimiento: {
            min: 0,
            max: 1000000,
            decimales: 2
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

    // Función para validar número decimal
    function validarNumeroDecimal(valor, campo) {
        if (!valor) return false;
        
        if (valor === '' || isNaN(valor) || !isFinite(valor)) {
            return false;
        }
        
        const num = parseFloat(valor);
        const config = configValidaciones[campo];
        
        // Validar rango
        if (num < config.min || num > config.max) {
            return false;
        }
        
        // Validar decimales
        if (config.decimales) {
            const partes = valor.toString().split('.');
            if (partes.length > 1 && partes[1].length > config.decimales) {
                return false;
            }
        }
        
        return true;
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
        const descripcionInput = document.getElementById('descripcion_mantenimiento');
        if (descripcionInput) {
            descripcionInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'descripcion_mantenimiento');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.descripcion_mantenimiento.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud
                if (valorSanitizado && valorSanitizado.length < configValidaciones.descripcion_mantenimiento.min) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Mínimo ${configValidaciones.descripcion_mantenimiento.min} caracteres`;
                } else if (valorSanitizado && valorSanitizado.length > configValidaciones.descripcion_mantenimiento.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.descripcion_mantenimiento.max} caracteres`;
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '#dc3545';
                    this.title = 'Campo requerido';
                }
            });
        }

        // Validación de código de serie
        const codigoInput = document.getElementById('codigo_serie');
        if (codigoInput) {
            codigoInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'codigo_serie');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.codigo_serie.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud
                if (valorSanitizado && valorSanitizado.length < configValidaciones.codigo_serie.min) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Mínimo ${configValidaciones.codigo_serie.min} caracteres`;
                } else if (valorSanitizado && valorSanitizado.length > configValidaciones.codigo_serie.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.codigo_serie.max} caracteres`;
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '#dc3545';
                    this.title = 'Campo requerido';
                }
            });
        }

        // Validación de costo
        const costoInput = document.getElementById('costo_mantenimiento');
        if (costoInput) {
            costoInput.addEventListener('input', function() {
                const valor = this.value;
                
                if (valor && !validarNumeroDecimal(valor, 'costo_mantenimiento')) {
                    this.style.borderColor = '#dc3545';
                    this.title = `El costo debe estar entre Q${configValidaciones.costo_mantenimiento.min} y Q${configValidaciones.costo_mantenimiento.max} con máximo ${configValidaciones.costo_mantenimiento.decimales} decimales`;
                } else if (valor) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                    
                    // Formatear a 2 decimales
                    const num = parseFloat(valor);
                    if (!isNaN(num)) {
                        this.value = num.toFixed(2);
                    }
                } else {
                    this.style.borderColor = '#dc3545';
                    this.title = 'Campo requerido';
                }
            });
        }

        // Validación de fecha (no puede ser en el futuro)
        const fechaInput = document.getElementById('fecha_mantenimiento');
        if (fechaInput) {
            fechaInput.addEventListener('change', function() {
                const fechaSeleccionada = new Date(this.value);
                const ahora = new Date();
                ahora.setHours(0, 0, 0, 0); // Solo comparar fechas, no horas
                
                if (fechaSeleccionada > ahora) {
                    this.style.borderColor = '#dc3545';
                    this.title = 'La fecha de mantenimiento no puede ser en el futuro';
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
                if (operacionInput) operacionInput.value = 'crear_mantenimiento';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar mantenimiento',
                    text: '¿Deseas registrar este mantenimiento de muebles en el sistema?',
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
                if (confirm('¿Deseas registrar este mantenimiento de muebles en el sistema?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_mantenimiento';
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
            const mobiliario = sanitizarInput(this.getAttribute('data-mobiliario'));
            const taller = sanitizarInput(this.getAttribute('data-taller'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));
            const fecha = sanitizarInput(this.getAttribute('data-fecha'));
            const codigo = sanitizarInput(this.getAttribute('data-codigo'));
            const costo = sanitizarInput(this.getAttribute('data-costo'));

            const doFill = () => {
                if (idMantenimientoInput) idMantenimientoInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_mobiliario').value = mobiliario || '';
                document.getElementById('id_taller').value = taller || '';
                document.getElementById('descripcion_mantenimiento').value = descripcion || '';
                document.getElementById('fecha_mantenimiento').value = fecha || '';
                document.getElementById('codigo_serie').value = codigo || '';
                document.getElementById('costo_mantenimiento').value = costo || '0.00';

                // Disparar eventos de validación para actualizar estilos
                ['descripcion_mantenimiento', 'codigo_serie', 'costo_mantenimiento', 'fecha_mantenimiento'].forEach(campoId => {
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
        if (operacionInput) operacionInput.value = 'crear_mantenimiento';
        
        // Establecer fecha actual por defecto
        const fechaInput = document.getElementById('fecha_mantenimiento');
        const now = new Date();
        fechaInput.valueAsDate = now;
        
        // Limpiar estilos de validación
        ['descripcion_mantenimiento', 'codigo_serie', 'costo_mantenimiento', 'fecha_mantenimiento'].forEach(campoId => {
            const input = document.getElementById(campoId);
            if (input) {
                input.style.borderColor = '';
                input.title = '';
            }
        });
        
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_mobiliario').focus();
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
        const mobiliario = document.getElementById('id_mobiliario').value;
        const descripcion = document.getElementById('descripcion_mantenimiento');
        const descripcionValor = descripcion.value.trim();
        const descripcionSanitizada = sanitizarTexto(descripcionValor, 'descripcion_mantenimiento');
        const fechaInput = document.getElementById('fecha_mantenimiento');
        const fecha = fechaInput.value;
        const codigo = document.getElementById('codigo_serie');
        const codigoValor = codigo.value.trim();
        const codigoSanitizado = sanitizarTexto(codigoValor, 'codigo_serie');
        const costo = document.getElementById('costo_mantenimiento').value;

        // Validar campos requeridos
        if (!mobiliario) { 
            return showWarning('Seleccione un mobiliario'); 
        }
        if (!descripcionValor) { 
            return showWarning('La descripción del mantenimiento es requerida'); 
        }
        if (!fecha) { 
            return showWarning('La fecha de mantenimiento es requerida'); 
        }
        if (!codigoValor) { 
            return showWarning('El código de serie es requerido'); 
        }
        if (!costo) { 
            return showWarning('El costo es requerido'); 
        }

        // Validar descripción
        if (descripcionSanitizada === null) {
            return showWarning(configValidaciones.descripcion_mantenimiento.mensaje);
        }
        
        if (descripcionValor.length < configValidaciones.descripcion_mantenimiento.min) {
            return showWarning(`La descripción debe tener al menos ${configValidaciones.descripcion_mantenimiento.min} caracteres`);
        }
        
        if (descripcionValor.length > configValidaciones.descripcion_mantenimiento.max) {
            return showWarning(`La descripción no puede exceder los ${configValidaciones.descripcion_mantenimiento.max} caracteres`);
        }

        // Validar código de serie
        if (codigoSanitizado === null) {
            return showWarning(configValidaciones.codigo_serie.mensaje);
        }
        
        if (codigoValor.length < configValidaciones.codigo_serie.min) {
            return showWarning(`El código de serie debe tener al menos ${configValidaciones.codigo_serie.min} caracteres`);
        }
        
        if (codigoValor.length > configValidaciones.codigo_serie.max) {
            return showWarning(`El código de serie no puede exceder los ${configValidaciones.codigo_serie.max} caracteres`);
        }

        // Validar costo
        if (!validarNumeroDecimal(costo, 'costo_mantenimiento')) {
            return showWarning(`El costo debe estar entre Q${configValidaciones.costo_mantenimiento.min} y Q${configValidaciones.costo_mantenimiento.max} con máximo ${configValidaciones.costo_mantenimiento.decimales} decimales`);
        }

        // Validar que la fecha no sea en el futuro
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        ahora.setHours(0, 0, 0, 0); // Solo comparar fechas, no horas
        
        if (fechaSeleccionada > ahora) {
            return showWarning('La fecha de mantenimiento no puede ser en el futuro');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert (formato unificado)
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            
            // Obtener información del mantenimiento desde la fila de la tabla
            const fila = this.closest('tr');
            const idMantenimiento = fila ? fila.querySelector('td:first-child').textContent.trim() : '';
            const mobiliario = fila ? fila.querySelector('td:nth-child(2)').textContent.trim() : '';
            const fecha = fila ? fila.querySelector('td:nth-child(6)').textContent.trim() : '';
            
            const nombreMantenimiento = `Mantenimiento #${idMantenimiento} - ${mobiliario} (${fecha})`;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar mantenimiento?',
                    html: `¿Estás seguro de que deseas eliminar ${nombreMantenimiento} del sistema?<br><br>
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
                if (confirm(`¿Eliminar ${nombreMantenimiento}? Esta acción no se puede deshacer.`)) {
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