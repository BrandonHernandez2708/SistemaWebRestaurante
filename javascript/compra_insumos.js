document.addEventListener('DOMContentLoaded', function () {
    const insumosData = (window.__DATA_COMPRA && window.__DATA_COMPRA.insumos) ? window.__DATA_COMPRA.insumos : [];
    const tablaBody = document.querySelector('#tabla-lineas tbody');
    const btnAgregar = document.getElementById('btn-agregar-linea');
    const totalEl = document.getElementById('total');
    const form = document.getElementById('form-compra');

    function crearSelectInsumo(selectedId) {
        const sel = document.createElement('select');
        sel.name = 'insumo_id[]';
        sel.className = 'form-select form-select-sm';
        sel.required = true;
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '-- seleccionar --';
        sel.appendChild(empty);
        insumosData.forEach(i => {
            const opt = document.createElement('option');
            opt.value = i.id_insumo;
            opt.textContent = `${i.insumo} (${i.descripcion || 'sin desc'}) — stock: ${i.stock}`;
            if (selectedId && String(selectedId) === String(i.id_insumo)) opt.selected = true;
            sel.appendChild(opt);
        });
        return sel;
    }

    function crearFila(defaults = {}) {
        const tr = document.createElement('tr');

        const tdInsumo = document.createElement('td');
        tdInsumo.appendChild(crearSelectInsumo(defaults.id_insumo));
        tr.appendChild(tdInsumo);

        const tdCant = document.createElement('td');
        const inpCant = document.createElement('input');
        inpCant.type = 'number';
        // solo enteros
        inpCant.step = '1';
        inpCant.min = '1';
        inpCant.name = 'cantidad[]';
        inpCant.className = 'form-control form-control-sm';
        inpCant.value = defaults.cantidad ? parseInt(defaults.cantidad, 10) : 1;
        inpCant.required = true;
        tdCant.appendChild(inpCant);
        tr.appendChild(tdCant);

        const tdPrecio = document.createElement('td');
        const inpPrecio = document.createElement('input');
        inpPrecio.type = 'number';
        inpPrecio.step = '0.01';
        inpPrecio.min = '0';
        inpPrecio.name = 'precio_unitario[]';
        inpPrecio.className = 'form-control form-control-sm';
        inpPrecio.value = defaults.precio_unitario ?? '0.00';
        inpPrecio.required = true;
        tdPrecio.appendChild(inpPrecio);
        tr.appendChild(tdPrecio);

        const tdSub = document.createElement('td');
        tdSub.className = 'align-middle';
        tdSub.textContent = '0.00';
        tr.appendChild(tdSub);

        const tdAcc = document.createElement('td');
        const btnDel = document.createElement('button');
        btnDel.type = 'button';
        btnDel.className = 'btn btn-sm btn-danger';
        btnDel.textContent = 'Eliminar';
        btnDel.addEventListener('click', () => { tr.remove(); recalcularTotal(); });
        tdAcc.appendChild(btnDel);
        tr.appendChild(tdAcc);

        // listeners para calcular subtotal
        function calc() {
            const q = parseInt(inpCant.value || 0, 10);
            const p = parseFloat(inpPrecio.value || 0);
            const sub = (!isNaN(q) && !isNaN(p)) ? (q * p) : 0;
            tdSub.textContent = sub.toFixed(2);
            recalcularTotal();
        }
        inpCant.addEventListener('input', calc);
        inpPrecio.addEventListener('input', calc);

        // calcular inicial
        calc();

        tablaBody.appendChild(tr);
        return tr;
    }

    function recalcularTotal() {
        let t = 0;
        tablaBody.querySelectorAll('tr').forEach(tr => {
            const subText = tr.cells[3].textContent.replace(',', '.');
            const val = parseFloat(subText) || 0;
            t += val;
        });
        totalEl.textContent = t.toFixed(2);
    }

    if (btnAgregar) {
        btnAgregar.addEventListener('click', function () {
            crearFila();
        });
    }

    // Si no hay filas iniciales, añadir una
    if (tablaBody.children.length === 0) crearFila();

    // validar antes de enviar: proveedor, fecha, al menos una línea válida
    if (form) {
        form.addEventListener('submit', function (evt) {
            const proveedor = document.getElementById('id_proveedor').value;
            if (!proveedor) {
                evt.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Proveedor requerido', text: 'Selecciona un proveedor antes de guardar.'});
                } else alert('Selecciona un proveedor antes de guardar.');
                return;
            }
            // comprobar líneas
            const filas = Array.from(tablaBody.querySelectorAll('tr'));
            if (filas.length === 0) {
                evt.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Líneas vacías', text: 'Agrega al menos una línea a la compra.'});
                } else alert('Agrega al menos una línea a la compra.');
                return;
            }
            for (let i = 0; i < filas.length; i++) {
                const tr = filas[i];
                const sel = tr.querySelector('select[name="insumo_id[]"]');
                const cant = parseInt((tr.querySelector('input[name="cantidad[]"]').value || '0'), 10);
                const precio = parseFloat((tr.querySelector('input[name="precio_unitario[]"]').value || '0'));
                if (!sel || !sel.value) {
                    evt.preventDefault();
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Insumo requerido', text: `Selecciona insumo en la línea ${i+1}`});
                    else alert(`Selecciona insumo en la línea ${i+1}`);
                    return;
                }
                if (isNaN(cant) || cant <= 0) {
                    evt.preventDefault();
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Cantidad inválida', text: `Cantidad inválida en la línea ${i+1}`});
                    else alert(`Cantidad inválida en la línea ${i+1}`);
                    return;
                }
                if (isNaN(precio) || precio < 0) {
                    evt.preventDefault();
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Precio inválido', text: `Precio inválido en la línea ${i+1}`});
                    else alert(`Precio inválido en la línea ${i+1}`);
                    return;
                }
            }

            // confirmación final
            evt.preventDefault();
            const doSubmit = () => form.submit();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirmar compra',
                    text: `Total a registrar: ${totalEl.textContent}. ¿Deseas continuar?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                }).then(res => { if (res.isConfirmed) doSubmit(); });
            } else {
                if (confirm(`Total a registrar: ${totalEl.textContent}. ¿Deseas continuar?`)) doSubmit();
            }
        });
    }

    // Nuevo: controles rápidos
    const selectQuick = document.getElementById('select_insumo_quick');
    const addCantidad = document.getElementById('add_cantidad');
    const addPrecio = document.getElementById('add_precio');
    const btnAddQuick = document.getElementById('btn-add-quick');

    if (btnAddQuick) {
        btnAddQuick.addEventListener('click', function () {
            const idIns = selectQuick ? selectQuick.value : '';
            const cant = addCantidad ? addCantidad.value : '';
            const precio = addPrecio ? addPrecio.value : '';

            if (!idIns) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Insumo requerido', text: 'Selecciona un insumo para agregar.'});
                } else alert('Selecciona un insumo para agregar.');
                return;
            }
            const cantidadNum = parseFloat(cant || 0);
            const precioNum = parseFloat(precio || 0);
            if (isNaN(cantidadNum) || cantidadNum <= 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Cantidad inválida', text: 'Ingresa una cantidad mayor a 0.'});
                } else alert('Ingresa una cantidad mayor a 0.');
                return;
            }
            if (isNaN(precioNum) || precioNum < 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Precio inválido', text: 'Ingresa un precio válido (>= 0).' });
                } else alert('Ingresa un precio válido (>= 0).');
                return;
            }

            // crear fila usando la función existente (pasa defaults)
            crearFila({ id_insumo: idIns, cantidad: cantidadNum.toString(), precio_unitario: precioNum.toString() });

            // limpiar controles rápidos
            if (selectQuick) selectQuick.value = '';
            if (addCantidad) addCantidad.value = '1';
            if (addPrecio) addPrecio.value = '0.00';
        });
    }

    // Mostrar mensajes desde server (window.__mensaje)

// Mostrar mensajes (si los hay) con SweetAlert
if (window.__mensaje && window.__mensaje.text) {
    const tipo = window.__mensaje.tipo || 'info';
    Swal.fire({
        icon: (tipo === 'success' ? 'success' : (tipo === 'error' ? 'error' : 'info')),
        title: tipo === 'success' ? 'Éxito' : (tipo === 'error' ? 'Error' : 'Aviso'),
        text: window.__mensaje.text
    }).then(() => {
        // ✅ Si la compra fue registrada correctamente, limpiar formulario
        if (tipo === 'success') {
            const form = document.getElementById('form-compra');
            form.reset(); // limpia todos los inputs
            // limpiar líneas de la tabla
            const tbody = document.querySelector('#tabla-lineas tbody');
            if (tbody) tbody.innerHTML = '';
            document.getElementById('total').textContent = '0.00';
        }
    });
}
});
