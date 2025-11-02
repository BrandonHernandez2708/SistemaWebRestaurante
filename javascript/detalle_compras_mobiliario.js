// DetalleComprasMobiliario.js — gestión de formulario de detalle de compras de mobiliario con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-detalle');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCompraOriginalInput = document.getElementById('id_compra_mobiliario_original');
    const idMobiliarioOriginalInput = document.getElementById('id_mobiliario_original');
    const cantidadInput = document.getElementById('cantidad_de_compra');
    const costoInput = document.getElementById('costo_unitario');
    const montoDisplay = document.getElementById('monto_total_display');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Calcular monto total automáticamente
    function calcularMontoTotal() {
        const cantidad = parseFloat(cantidadInput.value) || 0;
        const costo = parseFloat(costoInput.value) || 0;
        const montoTotal = cantidad * costo;
        montoDisplay.value = 'Q ' + montoTotal.toFixed(2);
    }

    cantidadInput.addEventListener('input', calcularMontoTotal);
    costoInput.addEventListener('input', calcularMontoTotal);

    // Validación de cantidad
    if (cantidadInput) {
        cantidadInput.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Asegurar que el valor sea positivo y dentro de límites
            if (value < 1) {
                value = 1;
            } else if (value > 10000) {
                value = 10000;
                showWarning('La cantidad no puede ser mayor a 10,000 unidades');
            }
            
            this.value = value;
            calcularMontoTotal();
        });

        cantidadInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseInt(this.value))) {
                const valor = parseInt(this.value);
                if (valor < 1) {
                    this.value = 1;
                } else if (valor > 10000) {
                    this.value = 10000;
                }
                calcularMontoTotal();
            }
        });
    }

    // Validación de costo unitario
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
            } else if (valorNum > 100000) {
                this.value = '100000';
                showWarning('El costo unitario no puede ser mayor a Q 100,000.00');
            }
            
            calcularMontoTotal();
        });

        costoInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseFloat(this.value))) {
                const valor = parseFloat(this.value);
                if (valor <= 0) {
                    this.value = '0.01';
                } else if (valor > 100000) {
                    this.value = '100000';
                } else {
                    this.value = valor.toFixed(2);
                }
                calcularMontoTotal();
            } else if (this.value === '') {
                this.value = '0.01';
                calcularMontoTotal();
            }
        });
    }

    // Validación de selecciones
    const compraSelect = document.getElementById('id_compra_mobiliario');
    const mobiliarioSelect = document.getElementById('id_mobiliario');

    if (compraSelect) {
        compraSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione una compra válida');
                this.value = '';
            }
        });
    }

    if (mobiliarioSelect) {
        mobiliarioSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
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
                if (operacionInput) operacionInput.value = 'crear_detalle';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar detalle de compra',
                    text: '¿Deseas registrar este detalle de compra de mobiliario?',
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
                if (confirm('¿Deseas registrar este detalle de compra de mobiliario?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_detalle';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar detalle de compra',
                    text: '¿Deseas guardar los cambios en este detalle de compra?',
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
            const compra = sanitizarInput(this.getAttribute('data-compra'));
            const mobiliario = sanitizarInput(this.getAttribute('data-mobiliario'));
            const cantidad = sanitizarInput(this.getAttribute('data-cantidad'));
            const costo = sanitizarInput(this.getAttribute('data-costo'));

            const doFill = () => {
                if (idCompraOriginalInput) idCompraOriginalInput.value = compra || '';
                if (idMobiliarioOriginalInput) idMobiliarioOriginalInput.value = mobiliario || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_compra_mobiliario').value = compra || '';
                document.getElementById('id_mobiliario').value = mobiliario || '';
                document.getElementById('cantidad_de_compra').value = cantidad || '1';
                
                // Formatear costo a 2 decimales
                if (costo && !isNaN(parseFloat(costo))) {
                    document.getElementById('costo_unitario').value = parseFloat(costo).toFixed(2);
                } else {
                    document.getElementById('costo_unitario').value = '0.01';
                }
                
                calcularMontoTotal();
                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar detalle de compra',
                    text: `¿Deseas editar el detalle de compra #${compra || ''}?`,
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
        if (idCompraOriginalInput) idCompraOriginalInput.value = '';
        if (idMobiliarioOriginalInput) idMobiliarioOriginalInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_detalle';
        // Establecer valores por defecto
        document.getElementById('cantidad_de_compra').value = '1';
        document.getElementById('costo_unitario').value = '0.01';
        calcularMontoTotal();
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_compra_mobiliario').focus();
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
        const compra = document.getElementById('id_compra_mobiliario').value;
        const mobiliario = document.getElementById('id_mobiliario').value;
        const cantidad = cantidadInput.value;
        const costo = costoInput.value;

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

        // Validar compra
        if (!compra) { 
            return showWarning('La compra es requerida'); 
        }
        if (!/^\d+$/.test(compra)) {
            return showWarning('El ID de la compra debe ser un número válido');
        }

        // Validar mobiliario
        if (!mobiliario) { 
            return showWarning('El mobiliario es requerido'); 
        }
        if (!/^\d+$/.test(mobiliario)) {
            return showWarning('El ID del mobiliario debe ser un número válido');
        }

        // Validar cantidad
        if (!cantidad) { 
            return showWarning('La cantidad es requerida'); 
        }
        
        const cantidadNum = parseInt(cantidad);
        if (isNaN(cantidadNum)) {
            return showWarning('La cantidad debe ser un número válido');
        }
        
        if (cantidadNum <= 0) {
            return showWarning('La cantidad debe ser mayor a cero');
        }
        
        if (cantidadNum > 10000) {
            return showWarning('La cantidad no puede ser mayor a 10,000 unidades');
        }

        // Validar costo unitario
        if (!costo) { 
            return showWarning('El costo unitario es requerido'); 
        }
        
        const costoNum = parseFloat(costo);
        if (isNaN(costoNum)) {
            return showWarning('El costo unitario debe ser un número válido');
        }
        
        if (costoNum <= 0) {
            return showWarning('El costo unitario debe ser mayor a cero');
        }
        
        // Validar formato decimal del costo
        const partesCosto = costo.toString().split('.');
        if (partesCosto.length > 1 && partesCosto[1].length > 2) {
            return showWarning('El costo unitario no puede tener más de 2 decimales');
        }

        // Validar que el costo no sea excesivamente alto
        if (costoNum > 100000) {
            return showWarning('El costo unitario no puede ser mayor a Q 100,000.00');
        }

        // Validar monto total (cantidad * costo)
        const montoTotal = cantidadNum * costoNum;
        if (montoTotal > 100000000) { // 100 millones
            return showWarning('El monto total no puede ser mayor a Q 100,000,000.00');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idCompra = this.querySelector('input[name="id_compra_mobiliario"]').value;
            const idMobiliario = this.querySelector('input[name="id_mobiliario"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar detalle de compra?',
                    html: `¿Estás seguro de que deseas eliminar el detalle de compra #<strong>${idCompra}</strong>?<br><br>
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
                if (confirm(`¿Eliminar detalle de compra #${idCompra}? Esta acción no se puede deshacer.`)) {
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
                if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});