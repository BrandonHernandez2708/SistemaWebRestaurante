// RutasVehiculos.js — gestión de formulario de rutas con SweetAlert2
// CON VALIDACIONES MEJORADAS Y FORMATO UNIFICADO

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-rutas');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idRutaInput = document.getElementById('id_ruta');

    // Configuración de validaciones
    const configValidaciones = {
        descripcion_ruta: {
            min: 3,
            max: 200,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , ; : ! ? ( ) # &"
        },
        inicio_ruta: {
            min: 0,
            max: 100,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]*$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , ; : ! ? ( ) # &",
            opcional: true
        },
        fin_ruta: {
            min: 0,
            max: 100,
            regex: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]*$/,
            mensaje: "Solo letras, números, espacios y los siguientes caracteres especiales: - _ . , ; : ! ? ( ) # &",
            opcional: true
        },
        gasolina_aproximada: {
            min: 0,
            max: 1000,
            decimales: 2,
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

    // Función para validar número decimal
    function validarNumeroDecimal(valor, campo) {
        if (!valor && configValidaciones[campo].opcional) return true;
        
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

    // Validación en tiempo real para campos de texto
    function configurarValidacionEnTiempoReal() {
        const camposTexto = ['descripcion_ruta', 'inicio_ruta', 'fin_ruta'];
        
        camposTexto.forEach(campoId => {
            const input = document.getElementById(campoId);
            if (input) {
                input.addEventListener('input', function() {
                    const valorOriginal = this.value;
                    const valorSanitizado = sanitizarTexto(valorOriginal, campoId);
                    
                    if (valorSanitizado === null) {
                        // Caracteres no permitidos - mostrar advertencia visual
                        this.style.borderColor = '#dc3545';
                        this.title = configValidaciones[campoId].mensaje;
                    } else {
                        this.style.borderColor = '';
                        this.title = '';
                        
                        // Actualizar valor si fue sanitizado
                        if (valorSanitizado !== valorOriginal) {
                            this.value = valorSanitizado;
                        }
                    }
                    
                    // Validar longitud
                    const config = configValidaciones[campoId];
                    if (valorSanitizado && valorSanitizado.length > config.max) {
                        this.style.borderColor = '#ffc107';
                        this.title = `Máximo ${config.max} caracteres`;
                    } else if (valorSanitizado && !config.opcional && valorSanitizado.length < config.min) {
                        this.style.borderColor = '#ffc107';
                        this.title = `Mínimo ${config.min} caracteres`;
                    } else if (!valorSanitizado && !config.opcional) {
                        this.style.borderColor = '#dc3545';
                        this.title = 'Campo requerido';
                    } else {
                        this.style.borderColor = '#28a745';
                        this.title = '';
                    }
                });
                
                // También validar al perder el foco
                input.addEventListener('blur', function() {
                    const evento = new Event('input', { bubbles: true });
                    this.dispatchEvent(evento);
                });
            }
        });
        
        // Validación en tiempo real para gasolina
        const gasolinaInput = document.getElementById('gasolina_aproximada');
        if (gasolinaInput) {
            gasolinaInput.addEventListener('input', function() {
                const valor = this.value;
                
                if (valor && !validarNumeroDecimal(valor, 'gasolina_aproximada')) {
                    this.style.borderColor = '#dc3545';
                    this.title = `Debe ser un número entre ${configValidaciones.gasolina_aproximada.min} y ${configValidaciones.gasolina_aproximada.max} con máximo ${configValidaciones.gasolina_aproximada.decimales} decimales`;
                } else if (valor) {
                    this.style.borderColor = '#28a745';
                    this.title = '';
                    
                    // Formatear a 2 decimales
                    const num = parseFloat(valor);
                    if (!isNaN(num)) {
                        this.value = num.toFixed(2);
                    }
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                }
            });
            
            gasolinaInput.addEventListener('blur', function() {
                const evento = new Event('input', { bubbles: true });
                this.dispatchEvent(evento);
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
                if (operacionInput) operacionInput.value = 'crear_ruta';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar ruta',
                    text: '¿Deseas registrar esta ruta en el sistema?',
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
                if (confirm('¿Deseas registrar esta ruta en el sistema?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_ruta';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar ruta',
                    text: '¿Deseas guardar los cambios en esta ruta?',
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
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));
            const inicio = sanitizarInput(this.getAttribute('data-inicio'));
            const fin = sanitizarInput(this.getAttribute('data-fin'));
            const gasolina = sanitizarInput(this.getAttribute('data-gasolina'));

            const doFill = () => {
                if (idRutaInput) idRutaInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('descripcion_ruta').value = descripcion || '';
                document.getElementById('inicio_ruta').value = inicio || '';
                document.getElementById('fin_ruta').value = fin || '';
                document.getElementById('gasolina_aproximada').value = gasolina || '';

                // Disparar eventos de validación para actualizar estilos
                ['descripcion_ruta', 'inicio_ruta', 'fin_ruta', 'gasolina_aproximada'].forEach(campoId => {
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
                    title: 'Editar ruta',
                    text: `¿Deseas editar la ruta "${descripcion || id}"?`,
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
        if (idRutaInput) idRutaInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_ruta';
        mostrarBotonesGuardar();
        
        // Limpiar estilos de validación
        ['descripcion_ruta', 'inicio_ruta', 'fin_ruta', 'gasolina_aproximada'].forEach(campoId => {
            const input = document.getElementById(campoId);
            if (input) {
                input.style.borderColor = '';
                input.title = '';
            }
        });
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('descripcion_ruta').focus();
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
        const descripcion = document.getElementById('descripcion_ruta');
        const descripcionValor = descripcion.value.trim();
        const descripcionSanitizada = sanitizarTexto(descripcionValor, 'descripcion_ruta');
        
        const inicio = document.getElementById('inicio_ruta');
        const inicioValor = inicio.value.trim();
        const inicioSanitizado = sanitizarTexto(inicioValor, 'inicio_ruta');
        
        const fin = document.getElementById('fin_ruta');
        const finValor = fin.value.trim();
        const finSanitizado = sanitizarTexto(finValor, 'fin_ruta');
        
        const gasolina = document.getElementById('gasolina_aproximada');
        const gasolinaValor = gasolina.value;

        // Validar descripción
        if (!descripcionValor) { 
            return showWarning('La descripción de la ruta es requerida'); 
        }
        
        if (descripcionSanitizada === null) {
            return showWarning(configValidaciones.descripcion_ruta.mensaje);
        }
        
        if (descripcionValor.length < configValidaciones.descripcion_ruta.min) {
            return showWarning(`La descripción debe tener al menos ${configValidaciones.descripcion_ruta.min} caracteres`);
        }
        
        if (descripcionValor.length > configValidaciones.descripcion_ruta.max) {
            return showWarning(`La descripción no puede exceder los ${configValidaciones.descripcion_ruta.max} caracteres`);
        }

        // Validar inicio_ruta (opcional)
        if (inicioValor && inicioSanitizado === null) {
            return showWarning(`Punto de inicio: ${configValidaciones.inicio_ruta.mensaje}`);
        }
        
        if (inicioValor.length > configValidaciones.inicio_ruta.max) {
            return showWarning(`El punto de inicio no puede exceder los ${configValidaciones.inicio_ruta.max} caracteres`);
        }

        // Validar fin_ruta (opcional)
        if (finValor && finSanitizado === null) {
            return showWarning(`Punto final: ${configValidaciones.fin_ruta.mensaje}`);
        }
        
        if (finValor.length > configValidaciones.fin_ruta.max) {
            return showWarning(`El punto final no puede exceder los ${configValidaciones.fin_ruta.max} caracteres`);
        }

        // Validar gasolina (opcional)
        if (gasolinaValor && !validarNumeroDecimal(gasolinaValor, 'gasolina_aproximada')) {
            return showWarning(`La gasolina aproximada debe ser un número entre ${configValidaciones.gasolina_aproximada.min} y ${configValidaciones.gasolina_aproximada.max} con máximo ${configValidaciones.gasolina_aproximada.decimales} decimales`);
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert (formato unificado)
document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
    f.addEventListener('submit', function(evt) {
        evt.preventDefault();
        const frm = this;
        const idRuta = this.querySelector('input[name="id_ruta"]').value;
        
        // Obtener el nombre de la ruta desde la fila de la tabla
        const fila = this.closest('tr');
        const nombreRuta = fila ? fila.querySelector('.descripcion-cell').textContent.trim() : 'esta ruta';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar ruta?',
                html: `¿Estás seguro de que deseas eliminar la ruta "${nombreRuta}" del sistema?<br><br>
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
            if (confirm(`¿Eliminar la ruta "${nombreRuta}"? Esta acción no se puede deshacer.`)) {
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