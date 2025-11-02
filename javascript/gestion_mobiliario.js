// GestionMobiliario.js — gestión de formulario de mobiliario con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-mobiliario');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idMobiliarioInput = document.getElementById('id_mobiliario');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para validar nombre del mobiliario
    function validarNombreMobiliario(nombre) {
        const nombreRegex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\(\)]+$/;
        return nombre.length >= 2 && nombre.length <= 100 && nombreRegex.test(nombre);
    }

    // Función para validar descripción
    function validarDescripcion(descripcion) {
        if (!descripcion) return true; // Descripción es opcional
        const descripcionRegex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\(\)\,\;\:\!\?]+$/;
        return descripcion.length <= 500 && descripcionRegex.test(descripcion);
    }

    // Validación de nombre del mobiliario en tiempo real
    const nombreInput = document.getElementById('nombre_mobiliario');
    if (nombreInput) {
        nombreInput.addEventListener('input', function () {
            const valor = this.value.trim();
            if (valor.length > 100) {
                this.value = valor.substring(0, 100);
                showWarning('El nombre no puede exceder los 100 caracteres');
            }
        });

        nombreInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarNombreMobiliario(valor)) {
                showWarning('El nombre contiene caracteres no permitidos. Solo se permiten letras, números, espacios y los caracteres: - _ . ( )');
                this.focus();
            }
        });
    }

    // Validación de descripción en tiempo real
    const descripcionInput = document.getElementById('descripcion');
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
                showWarning('La descripción contiene caracteres no permitidos. Solo se permiten letras, números, espacios y los caracteres: - _ . ( ) , ; : ! ?');
                this.focus();
            }
        });
    }

    // Validación de cantidad
    const cantidadInput = document.getElementById('cantidad_en_stock');
    if (cantidadInput) {
        cantidadInput.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Asegurar que el valor sea positivo y dentro de límites
            if (value < 0) {
                value = 0;
            } else if (value > 100000) {
                value = 100000;
                showWarning('La cantidad no puede ser mayor a 100,000 unidades');
            }
            
            this.value = value;
        });

        cantidadInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseInt(this.value))) {
                const valor = parseInt(this.value);
                if (valor < 0) {
                    this.value = 0;
                } else if (valor > 100000) {
                    this.value = 100000;
                }
            }
        });
    }

    // Validación de selección de tipo
    const tipoSelect = document.getElementById('id_tipo_mobiliario');
    if (tipoSelect) {
        tipoSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un tipo de mobiliario válido');
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
                    title: 'Registrar mobiliario',
                    text: '¿Deseas registrar este mobiliario en el inventario?',
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
                if (confirm('¿Deseas registrar este mobiliario en el inventario?')) doSubmit();
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
                    title: 'Actualizar mobiliario',
                    text: '¿Deseas guardar los cambios en este mobiliario?',
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
            const tipo = sanitizarInput(this.getAttribute('data-tipo'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));
            const cantidad = sanitizarInput(this.getAttribute('data-cantidad'));

            const doFill = () => {
                if (idMobiliarioInput) idMobiliarioInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('nombre_mobiliario').value = nombre || '';
                document.getElementById('id_tipo_mobiliario').value = tipo || '';
                document.getElementById('descripcion').value = descripcion || '';
                document.getElementById('cantidad_en_stock').value = cantidad || '0';

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar mobiliario',
                    text: `¿Deseas editar el mobiliario "${nombre || ''}"?`,
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
        if (idMobiliarioInput) idMobiliarioInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Establecer valores por defecto
        document.getElementById('cantidad_en_stock').value = '0';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('nombre_mobiliario').focus();
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
        const nombre = document.getElementById('nombre_mobiliario').value.trim();
        const tipo = document.getElementById('id_tipo_mobiliario').value;
        const cantidad = document.getElementById('cantidad_en_stock').value;
        const descripcion = document.getElementById('descripcion').value.trim();

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

        // Validar nombre
        if (!nombre) { 
            return showWarning('El nombre del mobiliario es requerido'); 
        }
        
        if (nombre.length < 2) {
            return showWarning('El nombre del mobiliario debe tener al menos 2 caracteres');
        }
        
        if (nombre.length > 100) {
            return showWarning('El nombre del mobiliario no puede exceder los 100 caracteres');
        }
        
        if (!validarNombreMobiliario(nombre)) {
            return showWarning('El nombre contiene caracteres no permitidos. Solo se permiten letras, números, espacios y los caracteres: - _ . ( )');
        }

        // Validar tipo
        if (!tipo) { 
            return showWarning('El tipo de mobiliario es requerido'); 
        }
        if (!/^\d+$/.test(tipo)) {
            return showWarning('El ID del tipo de mobiliario debe ser un número válido');
        }

        // Validar cantidad
        if (!cantidad) { 
            return showWarning('La cantidad en stock es requerida'); 
        }
        
        const cantidadNum = parseInt(cantidad);
        if (isNaN(cantidadNum)) {
            return showWarning('La cantidad en stock debe ser un número válido');
        }
        
        if (cantidadNum < 0) {
            return showWarning('La cantidad en stock no puede ser negativa');
        }
        
        if (cantidadNum > 100000) {
            return showWarning('La cantidad en stock no puede ser mayor a 100,000 unidades');
        }

        // Validar descripción (opcional)
        if (descripcion && !validarDescripcion(descripcion)) {
            return showWarning('La descripción contiene caracteres no permitidos o excede el límite de 500 caracteres');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idMobiliario = this.querySelector('input[name="id_mobiliario"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar mobiliario?',
                    html: `¿Estás seguro de que deseas eliminar este mobiliario del inventario?<br><br>
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
                if (confirm('¿Eliminar mobiliario? Esta acción no se puede deshacer.')) {
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