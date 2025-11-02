// GestionVehiculos.js — gestión de formulario de vehículos con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-vehiculo');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idPlacaInput = document.getElementById('id_placa');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para validar formato de placa
    function validarFormatoPlaca(placa) {
        const placaRegex = /^[A-Z0-9\-]+$/;
        return placa.length >= 3 && placa.length <= 15 && placaRegex.test(placa);
    }

    // Función para validar marca/modelo
    function validarTextoVehiculo(texto, minLength, maxLength) {
        const textoRegex = /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.]+$/;
        return texto.length >= minLength && texto.length <= maxLength && textoRegex.test(texto);
    }

    // Función para validar descripción
    function validarDescripcion(descripcion) {
        if (!descripcion) return true; // Descripción es opcional
        const descripcionRegex = /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)]+$/;
        return descripcion.length <= 500 && descripcionRegex.test(descripcion);
    }

    // Validación de placa en tiempo real
    const placaInput = document.getElementById('no_placas');
    if (placaInput) {
        placaInput.addEventListener('input', function () {
            // Convertir a mayúsculas automáticamente
            let valor = this.value.toUpperCase();
            
            // Permitir solo letras, números y guiones
            valor = valor.replace(/[^A-Z0-9\-]/g, '');
            
            // Limitar longitud
            if (valor.length > 15) {
                valor = valor.substring(0, 15);
                showWarning('La placa no puede exceder los 15 caracteres');
            }
            
            this.value = valor;
        });

        placaInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarFormatoPlaca(valor)) {
                showWarning('La placa debe tener entre 3 y 15 caracteres y solo puede contener letras mayúsculas, números y guiones');
                this.focus();
            }
        });
    }

    // Validación de marca en tiempo real
    const marcaInput = document.getElementById('marca');
    if (marcaInput) {
        marcaInput.addEventListener('input', function () {
            let valor = this.value;
            
            // Limitar longitud
            if (valor.length > 50) {
                valor = valor.substring(0, 50);
                showWarning('La marca no puede exceder los 50 caracteres');
            }
            
            this.value = valor;
        });

        marcaInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarTextoVehiculo(valor, 2, 50)) {
                showWarning('La marca debe tener entre 2 y 50 caracteres y solo puede contener letras, números, espacios y los caracteres: - _ .');
                this.focus();
            }
        });
    }

    // Validación de modelo en tiempo real
    const modeloInput = document.getElementById('modelo');
    if (modeloInput) {
        modeloInput.addEventListener('input', function () {
            let valor = this.value;
            
            // Limitar longitud
            if (valor.length > 50) {
                valor = valor.substring(0, 50);
                showWarning('El modelo no puede exceder los 50 caracteres');
            }
            
            this.value = valor;
        });

        modeloInput.addEventListener('blur', function() {
            const valor = this.value.trim();
            if (valor && !validarTextoVehiculo(valor, 1, 50)) {
                showWarning('El modelo debe tener entre 1 y 50 caracteres y solo puede contener letras, números, espacios y los caracteres: - _ .');
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
                showWarning('La descripción contiene caracteres no permitidos. Solo se permiten letras, números, espacios y los caracteres: - _ . , ; : ! ? ( )');
                this.focus();
            }
        });
    }

    // Validación de año en tiempo real
    const anioInput = document.getElementById('anio_vehiculo');
    if (anioInput) {
        anioInput.addEventListener('input', function () {
            const currentYear = new Date().getFullYear();
            const year = parseInt(this.value) || 0;
            
            if (this.value && (year < 1900 || year > currentYear + 1)) {
                this.setCustomValidity(`El año debe estar entre 1900 y ${currentYear + 1}`);
            } else {
                this.setCustomValidity('');
            }
        });

        anioInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseInt(this.value))) {
                const valor = parseInt(this.value);
                const currentYear = new Date().getFullYear();
                
                if (valor < 1900) {
                    this.value = 1900;
                } else if (valor > currentYear + 1) {
                    this.value = currentYear + 1;
                }
            }
        });
    }

    // Validación de selección de estado
    const estadoSelect = document.getElementById('estado');
    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            const estadosValidos = ['ACTIVO', 'EN_TALLER', 'BAJA'];
            if (this.value && !estadosValidos.includes(this.value)) {
                showWarning('Seleccione un estado válido');
                this.value = 'ACTIVO';
            }
        });
    }

    // Validación de selección de mobiliario
    const mobiliarioSelect = document.getElementById('id_mobiliario');
    if (mobiliarioSelect) {
        mobiliarioSelect.addEventListener('change', function() {
            if (this.value && !/^\d*$/.test(this.value)) {
                showWarning('Seleccione un mobiliario válido');
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
                    title: 'Registrar vehículo',
                    text: '¿Deseas registrar este vehículo en el sistema?',
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
                if (confirm('¿Deseas registrar este vehículo en el sistema?')) doSubmit();
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
                    title: 'Actualizar vehículo',
                    text: '¿Deseas guardar los cambios en este vehículo?',
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
            const placas = sanitizarInput(this.getAttribute('data-placas'));
            const marca = sanitizarInput(this.getAttribute('data-marca'));
            const modelo = sanitizarInput(this.getAttribute('data-modelo'));
            const anio = sanitizarInput(this.getAttribute('data-anio'));
            const descripcion = sanitizarInput(this.getAttribute('data-descripcion'));
            const estado = sanitizarInput(this.getAttribute('data-estado'));
            const mobiliario = sanitizarInput(this.getAttribute('data-mobiliario'));

            const doFill = () => {
                if (idPlacaInput) idPlacaInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('no_placas').value = placas || '';
                document.getElementById('marca').value = marca || '';
                document.getElementById('modelo').value = modelo || '';
                document.getElementById('anio_vehiculo').value = anio || '';
                document.getElementById('descripcion').value = descripcion || '';
                document.getElementById('estado').value = estado || 'ACTIVO';
                document.getElementById('id_mobiliario').value = mobiliario || '';

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar vehículo',
                    text: `¿Deseas editar el vehículo con placa "${placas || id}"?`,
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
        if (idPlacaInput) idPlacaInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Establecer valores por defecto
        document.getElementById('estado').value = 'ACTIVO';
        document.getElementById('id_mobiliario').value = '';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        if (placaInput) placaInput.focus();
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
        const placas = document.getElementById('no_placas').value.trim();
        const marca = document.getElementById('marca').value.trim();
        const modelo = document.getElementById('modelo').value.trim();
        const anio = document.getElementById('anio_vehiculo').value;
        const estado = document.getElementById('estado').value;
        const descripcion = document.getElementById('descripcion').value.trim();
        const mobiliario = document.getElementById('id_mobiliario').value;

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

        // Validar placa
        if (!placas) { 
            return showWarning('La placa es requerida'); 
        }
        
        if (placas.length < 3) {
            return showWarning('La placa debe tener al menos 3 caracteres');
        }
        
        if (placas.length > 15) {
            return showWarning('La placa no puede exceder los 15 caracteres');
        }
        
        if (!validarFormatoPlaca(placas)) {
            return showWarning('La placa solo puede contener letras mayúsculas, números y guiones');
        }

        // Validar marca
        if (!marca) { 
            return showWarning('La marca es requerida'); 
        }
        
        if (marca.length < 2) {
            return showWarning('La marca debe tener al menos 2 caracteres');
        }
        
        if (marca.length > 50) {
            return showWarning('La marca no puede exceder los 50 caracteres');
        }
        
        if (!validarTextoVehiculo(marca, 2, 50)) {
            return showWarning('La marca contiene caracteres no permitidos');
        }

        // Validar modelo
        if (!modelo) { 
            return showWarning('El modelo es requerido'); 
        }
        
        if (modelo.length < 1) {
            return showWarning('El modelo debe tener al menos 1 caracter');
        }
        
        if (modelo.length > 50) {
            return showWarning('El modelo no puede exceder los 50 caracteres');
        }
        
        if (!validarTextoVehiculo(modelo, 1, 50)) {
            return showWarning('El modelo contiene caracteres no permitidos');
        }

        // Validar año
        if (!anio) { 
            return showWarning('El año es requerido'); 
        }
        
        const anioNum = parseInt(anio);
        if (isNaN(anioNum)) {
            return showWarning('El año debe ser un número válido');
        }
        
        const currentYear = new Date().getFullYear();
        if (anioNum < 1900 || anioNum > currentYear + 1) {
            return showWarning(`El año debe estar entre 1900 y ${currentYear + 1}`);
        }

        // Validar estado
        if (!estado) { 
            return showWarning('El estado es requerido'); 
        }
        
        const estadosValidos = ['ACTIVO', 'EN_TALLER', 'BAJA'];
        if (!estadosValidos.includes(estado)) {
            return showWarning('El estado seleccionado no es válido');
        }

        // Validar descripción (opcional)
        if (descripcion && !validarDescripcion(descripcion)) {
            return showWarning('La descripción contiene caracteres no permitidos o excede el límite de 500 caracteres');
        }

        // Validar mobiliario (opcional)
        if (mobiliario && !/^\d*$/.test(mobiliario)) {
            return showWarning('El ID del mobiliario asociado es inválido');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idVehiculo = this.querySelector('input[name="id_placa"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar vehículo?',
                    html: `¿Estás seguro de que deseas eliminar este vehículo del sistema?<br><br>
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
                if (confirm('¿Eliminar vehículo? Esta acción no se puede deshacer.')) {
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