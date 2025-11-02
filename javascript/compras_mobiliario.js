// ComprasMobiliario.js — gestión de formulario de compras de mobiliario con SweetAlert2
// CON VALIDACIONES MEJORADAS IMPLEMENTADAS

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-compras');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCompraInput = document.getElementById('id_compra_mobiliario');

    // Función para sanitizar inputs y prevenir XSS
    function sanitizarInput(input) {
        if (!input) return '';
        return input.toString().trim().replace(/[<>&"']/g, '');
    }

    // Función para validar formato de fecha
    function validarFecha(fecha) {
        const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!fechaRegex.test(fecha)) {
            return false;
        }
        
        const fechaObj = new Date(fecha);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        return fechaObj <= hoy;
    }

    // Validación de monto con decimales controlados
    const montoInput = document.getElementById('monto_total_compra_q');
    if (montoInput) {
        montoInput.addEventListener('input', function () {
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
            
            // Asegurar que el valor sea positivo
            if (parseFloat(value) < 0) {
                this.value = '0.01';
            }
        });

        montoInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseFloat(this.value))) {
                const valor = parseFloat(this.value);
                if (valor <= 0) {
                    this.value = '0.01';
                } else {
                    this.value = valor.toFixed(2);
                }
            } else if (this.value === '') {
                this.value = '0.01';
            }
        });
    }

    // Validación de fecha - no permitir fechas futuras
    const fechaInput = document.getElementById('fecha_de_compra');
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const fechaSeleccionada = this.value;
            
            if (!validarFecha(fechaSeleccionada)) {
                showWarning('La fecha de compra no puede ser en el futuro y debe tener el formato YYYY-MM-DD');
                const hoy = new Date().toISOString().split('T')[0];
                this.value = hoy;
            }
        });
    }

    // Validación de selección de proveedor
    const proveedorSelect = document.getElementById('id_proveedor');
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function() {
            if (this.value && !/^\d+$/.test(this.value)) {
                showWarning('Seleccione un proveedor válido');
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
                if (operacionInput) operacionInput.value = 'crear_compra';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar compra',
                    text: '¿Deseas registrar esta compra de mobiliario?',
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
                if (confirm('¿Deseas registrar esta compra de mobiliario?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormularioCompleto()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_compra';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar compra',
                    text: '¿Deseas guardar los cambios en esta compra?',
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
            const proveedor = sanitizarInput(this.getAttribute('data-proveedor'));
            const fecha = sanitizarInput(this.getAttribute('data-fecha'));
            const monto = sanitizarInput(this.getAttribute('data-monto'));

            const doFill = () => {
                if (idCompraInput) idCompraInput.value = id || '';
                
                // Sanitizar y establecer valores
                document.getElementById('id_proveedor').value = proveedor || '';
                document.getElementById('fecha_de_compra').value = fecha || '';
                
                // Formatear monto a 2 decimales
                if (monto && !isNaN(parseFloat(monto))) {
                    document.getElementById('monto_total_compra_q').value = parseFloat(monto).toFixed(2);
                } else {
                    document.getElementById('monto_total_compra_q').value = '0.01';
                }

                mostrarBotonesActualizar();
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar compra',
                    text: `¿Deseas editar la compra #${id || ''}?`,
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
        if (idCompraInput) idCompraInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_compra';
        // Establecer fecha actual por defecto
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_de_compra').value = hoy;
        // Establecer monto mínimo por defecto
        document.getElementById('monto_total_compra_q').value = '0.01';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_proveedor').focus();
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
        const proveedor = document.getElementById('id_proveedor').value;
        const fecha = document.getElementById('fecha_de_compra').value;
        const monto = document.getElementById('monto_total_compra_q').value;

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

        // Validar proveedor
        if (!proveedor) { 
            return showWarning('El proveedor es requerido'); 
        }
        if (!/^\d+$/.test(proveedor)) {
            return showWarning('El ID del proveedor debe ser un número válido');
        }

        // Validar fecha
        if (!fecha) { 
            return showWarning('La fecha de compra es requerida'); 
        }
        
        // Validar formato de fecha (YYYY-MM-DD) y que no sea futura
        if (!validarFecha(fecha)) {
            return showWarning('La fecha de compra no puede ser en el futuro y debe tener el formato YYYY-MM-DD');
        }

        // Validar monto
        if (!monto) { 
            return showWarning('El monto total es requerido'); 
        }
        
        const montoNum = parseFloat(monto);
        if (isNaN(montoNum)) {
            return showWarning('El monto total debe ser un número válido');
        }
        
        if (montoNum <= 0) {
            return showWarning('El monto total debe ser mayor a cero');
        }
        
        // Validar formato decimal del monto
        const partesMonto = monto.toString().split('.');
        if (partesMonto.length > 1 && partesMonto[1].length > 2) {
            return showWarning('El monto total no puede tener más de 2 decimales');
        }

        // Validar que el monto no sea excesivamente grande (límite de 10 millones)
        if (montoNum > 10000000) {
            return showWarning('El monto total no puede ser mayor a Q 10,000,000.00');
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            const idCompra = this.querySelector('input[name="id_compra_mobiliario"]').value;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar compra?',
                    html: `¿Estás seguro de que deseas eliminar la compra #<strong>${idCompra}</strong>?<br><br>
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
                if (confirm(`¿Eliminar compra #${idCompra}? Esta acción no se puede deshacer.`)) {
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
    mostrarBotonesGuardar();
    // Establecer fecha actual por defecto al cargar la página
    if (document.getElementById('fecha_de_compra')) {
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_de_compra').value = hoy;
    }
    // Establecer monto mínimo por defecto
    if (document.getElementById('monto_total_compra_q')) {
        document.getElementById('monto_total_compra_q').value = '0.01';
    }

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