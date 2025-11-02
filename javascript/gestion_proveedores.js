// GestionProveedores.js — gestión de formulario de proveedores con SweetAlert2
// CON VALIDACIONES MEJORADAS Y FORMATO UNIFICADO

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-proveedores');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idProveedorInput = document.getElementById('id_proveedor');

    // Configuración de validaciones
    const configValidaciones = {
        nombre_proveedor: {
            min: 3,
            max: 100,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\&\#\(\)]+$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , & # ( )"
        },
        telefono_proveedor: {
            min: 8,
            max: 20,
            regex: /^[\d\s\-\+\(\)]+$/,
            mensaje: "Solo números, espacios, guiones, paréntesis y signo +",
            opcional: true
        },
        correo_proveedor: {
            min: 5,
            max: 100,
            regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            mensaje: "Formato de correo electrónico inválido",
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
        // Validación de nombre del proveedor
        const nombreInput = document.getElementById('nombre_proveedor');
        if (nombreInput) {
            nombreInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'nombre_proveedor');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.nombre_proveedor.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud
                if (valorSanitizado && valorSanitizado.length < configValidaciones.nombre_proveedor.min) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Mínimo ${configValidaciones.nombre_proveedor.min} caracteres`;
                } else if (valorSanitizado && valorSanitizado.length > configValidaciones.nombre_proveedor.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.nombre_proveedor.max} caracteres`;
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '#dc3545';
                    this.title = 'Campo requerido';
                }
            });
        }

        // Validación de teléfono
        const telefonoInput = document.getElementById('telefono_proveedor');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'telefono_proveedor');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.telefono_proveedor.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud solo si hay valor
                if (valorSanitizado && valorSanitizado.length > configValidaciones.telefono_proveedor.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.telefono_proveedor.max} caracteres`;
                } else if (valorSanitizado && valorSanitizado.replace(/\D/g, '').length < configValidaciones.telefono_proveedor.min) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Mínimo ${configValidaciones.telefono_proveedor.min} dígitos`;
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                }
            });
        }

        // Validación de correo
        const correoInput = document.getElementById('correo_proveedor');
        if (correoInput) {
            correoInput.addEventListener('input', function() {
                const valorOriginal = this.value;
                const valorSanitizado = sanitizarTexto(valorOriginal, 'correo_proveedor');
                
                if (valorSanitizado === null) {
                    this.style.borderColor = '#dc3545';
                    this.title = configValidaciones.correo_proveedor.mensaje;
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                    
                    // Actualizar valor si fue sanitizado
                    if (valorSanitizado !== valorOriginal) {
                        this.value = valorSanitizado;
                    }
                }
                
                // Validar longitud solo si hay valor
                if (valorSanitizado && valorSanitizado.length > configValidaciones.correo_proveedor.max) {
                    this.style.borderColor = '#ffc107';
                    this.title = `Máximo ${configValidaciones.correo_proveedor.max} caracteres`;
                } else if (valorSanitizado && !configValidaciones.correo_proveedor.regex.test(valorSanitizado)) {
                    this.style.borderColor = '#dc3545';
                    this.title = 'Formato de correo electrónico inválido';
                } else if (valorSanitizado) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                } else {
                    this.style.borderColor = '';
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
                if (operacionInput) operacionInput.value = 'crear_proveedor';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar proveedor',
                    text: '¿Deseas registrar este proveedor en el sistema?',
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
                if (confirm('¿Deseas registrar este proveedor en el sistema?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_proveedor';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar proveedor',
                    text: '¿Deseas guardar los cambios en este proveedor?',
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
            const nombre = sanitizarInput(this.getAttribute('data-nombre'));
            const correo = sanitizarInput(this.getAttribute('data-correo'));
            const telefono = sanitizarInput(this.getAttribute('data-telefono'));

            const doFill = () => {
                if (idProveedorInput) idProveedorInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('nombre_proveedor').value = nombre || '';
                document.getElementById('correo_proveedor').value = correo || '';
                document.getElementById('telefono_proveedor').value = telefono || '';

                // Disparar eventos de validación para actualizar estilos
                ['nombre_proveedor', 'telefono_proveedor', 'correo_proveedor'].forEach(campoId => {
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
                    title: 'Editar proveedor',
                    text: `¿Deseas editar el proveedor "${nombre || ''}"?`,
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
        if (idProveedorInput) idProveedorInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_proveedor';
        
        // Limpiar estilos de validación
        ['nombre_proveedor', 'telefono_proveedor', 'correo_proveedor'].forEach(campoId => {
            const input = document.getElementById(campoId);
            if (input) {
                input.style.borderColor = '';
                input.title = '';
            }
        });
        
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('nombre_proveedor').focus();
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
        const nombre = document.getElementById('nombre_proveedor');
        const nombreValor = nombre.value.trim();
        const nombreSanitizado = sanitizarTexto(nombreValor, 'nombre_proveedor');
        const telefono = document.getElementById('telefono_proveedor');
        const telefonoValor = telefono.value.trim();
        const telefonoSanitizado = sanitizarTexto(telefonoValor, 'telefono_proveedor');
        const correo = document.getElementById('correo_proveedor');
        const correoValor = correo.value.trim();
        const correoSanitizado = sanitizarTexto(correoValor, 'correo_proveedor');

        // Validar nombre (requerido)
        if (!nombreValor) { 
            return showWarning('El nombre del proveedor es requerido'); 
        }
        
        if (nombreSanitizado === null) {
            return showWarning(configValidaciones.nombre_proveedor.mensaje);
        }
        
        if (nombreValor.length < configValidaciones.nombre_proveedor.min) {
            return showWarning(`El nombre del proveedor debe tener al menos ${configValidaciones.nombre_proveedor.min} caracteres`);
        }
        
        if (nombreValor.length > configValidaciones.nombre_proveedor.max) {
            return showWarning(`El nombre del proveedor no puede exceder los ${configValidaciones.nombre_proveedor.max} caracteres`);
        }

        // Validar teléfono (opcional)
        if (telefonoValor && telefonoSanitizado === null) {
            return showWarning(configValidaciones.telefono_proveedor.mensaje);
        }
        
        if (telefonoValor && telefonoValor.replace(/\D/g, '').length < configValidaciones.telefono_proveedor.min) {
            return showWarning(`El teléfono debe tener al menos ${configValidaciones.telefono_proveedor.min} dígitos`);
        }
        
        if (telefonoValor.length > configValidaciones.telefono_proveedor.max) {
            return showWarning(`El teléfono no puede exceder los ${configValidaciones.telefono_proveedor.max} caracteres`);
        }

        // Validar correo (opcional)
        if (correoValor && correoSanitizado === null) {
            return showWarning(configValidaciones.correo_proveedor.mensaje);
        }
        
        if (correoValor && !configValidaciones.correo_proveedor.regex.test(correoValor)) {
            return showWarning('Por favor ingrese un correo electrónico válido');
        }
        
        if (correoValor.length > configValidaciones.correo_proveedor.max) {
            return showWarning(`El correo no puede exceder los ${configValidaciones.correo_proveedor.max} caracteres`);
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert (formato unificado)
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            
            // Obtener información del proveedor desde la fila de la tabla
            const fila = this.closest('tr');
            const idProveedor = fila ? fila.querySelector('td:first-child').textContent.trim() : '';
            const nombreProveedor = fila ? fila.querySelector('td:nth-child(2)').textContent.trim() : '';
            
            const nombreCompleto = `Proveedor "${nombreProveedor}" (ID: ${idProveedor})`;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar proveedor?',
                    html: `¿Estás seguro de que deseas eliminar ${nombreCompleto} del sistema?<br><br>
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
                if (confirm(`¿Eliminar ${nombreCompleto}? Esta acción no se puede deshacer.`)) {
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