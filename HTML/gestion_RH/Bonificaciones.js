// <!--Ernesto David Samayoa Jocol 0901-22-3415-->
document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('form-bonificacion');
	const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
	const btnNuevo = document.getElementById('btn-nuevo');
	const btnGuardar = document.getElementById('btn-guardar');
	const btnActualizar = document.getElementById('btn-actualizar');
	const btnCancelar = document.getElementById('btn-cancelar');
	const operacionInput = document.getElementById('operacion');
	const idBonificacionInput = document.getElementById('id_bonificacion');

	const idEmpleadoInput = document.getElementById('id_empleado');
	const puestoEmpleadoInput = document.getElementById('puesto_empleado');
	const sueldoBaseInput = document.getElementById('sueldo_base');
	const fechaInput = document.getElementById('fecha_bonificacion');
	const horasInput = document.getElementById('horas_extras');
	const pagoInput = document.getElementById('pago_por_hora');
	const montoInput = document.getElementById('monto_bonificacion');

	function habilitarCampos() {
		inputs.forEach(i => { if (i.type !== 'hidden') i.disabled = false; });
		if (btnGuardar) btnGuardar.disabled = false;
		if (btnCancelar) btnCancelar.style.display = 'inline-block';
		for (let i = 0; i < inputs.length; i++) {
			if (inputs[i].type !== 'hidden') { inputs[i].focus(); break; }
		}
	}

	function limpiarFormulario() {
		if (form) form.reset();
		if (idBonificacionInput) idBonificacionInput.value = '';
		if (operacionInput) operacionInput.value = 'crear';
		if (puestoEmpleadoInput) puestoEmpleadoInput.value = '';
		if (sueldoBaseInput) sueldoBaseInput.value = '';
		if (montoInput) montoInput.value = '';
		if (pagoInput) pagoInput.placeholder = '';
		mostrarBotonesGuardar();
	}

	function mostrarBotonesGuardar() {
		if (btnGuardar) { btnGuardar.style.display = 'inline-block'; btnGuardar.disabled = false; }
		if (btnActualizar) { btnActualizar.style.display = 'none'; btnActualizar.disabled = true; }
		if (btnCancelar) btnCancelar.style.display = 'inline-block';
	}

	function mostrarBotonesActualizar() {
		if (btnGuardar) { btnGuardar.style.display = 'none'; btnGuardar.disabled = true; }
		if (btnActualizar) { btnActualizar.style.display = 'inline-block'; btnActualizar.disabled = false; }
		if (btnCancelar) btnCancelar.style.display = 'inline-block';
	}

	function formatMoney(v) {
		const n = Number(v) || 0;
		return n.toFixed(2);
	}

	function calcularTotal() {
		const horas = parseFloat(horasInput ? horasInput.value : 0) || 0;
		const pago = parseFloat(pagoInput ? pagoInput.value : 0) || 0;
		const total = horas * pago;
		if (montoInput) montoInput.value = formatMoney(total);
	}

	// Al cambiar empleado, solicitar info al backend (puesto + sueldo)
	if (idEmpleadoInput) {
		idEmpleadoInput.addEventListener('change', function () {
			const id = this.value;
			if (!id) {
				if (puestoEmpleadoInput) puestoEmpleadoInput.value = '';
				if (sueldoBaseInput) sueldoBaseInput.value = '';
				return;
			}
			const url = '/SistemaWebRestaurante/HTML/gestion_RH/Bonificaciones.php?action=infoEmpleado&id=' + encodeURIComponent(id);
			fetch(url).then(r => r.json()).then(data => {
				if (data) {
					if (puestoEmpleadoInput) puestoEmpleadoInput.value = data.puesto || '';
					if (sueldoBaseInput) sueldoBaseInput.value = data.sueldo_base ? Number(data.sueldo_base).toFixed(2) : '';
					// No autocompletar el pago por hora: mostrar una sugerencia en el placeholder
					if (pagoInput) {
						const sugerido = data.sueldo_base ? (Number(data.sueldo_base) / 160) : 0;
						pagoInput.placeholder = sugerido ? sugerido.toFixed(2) : '';
						// No calcular total automáticamente — el usuario debe ingresar el pago por hora
						// pero si existe valor en horas y pago, calcularTotal() seguirá funcionando.
					}
				}
			}).catch(err => {
				console.warn('No se pudo obtener info del empleado', err);
			});
		});
	}

	if (horasInput) horasInput.addEventListener('input', calcularTotal);
	if (pagoInput) pagoInput.addEventListener('input', calcularTotal);

	// ---------- Botones ----------
	if (btnGuardar) btnGuardar.addEventListener('click', function (e) {
		if (!form) return console.warn('Formulario no encontrado');
		e.preventDefault();
		if (validarFormulario()) {
			const doSubmit = () => {
				if (operacionInput) operacionInput.value = 'crear';
				form.submit();
			};
			if (typeof Swal !== 'undefined') {
				Swal.fire({ title: 'Guardar horas extra', text: '¿Deseas guardar este registro?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' }).then((r) => { if (r.isConfirmed) doSubmit(); });
			} else {
				if (confirm('¿Deseas guardar este registro?')) doSubmit();
			}
		}
	});

	if (btnActualizar) btnActualizar.addEventListener('click', function () {
		if (!form) return console.warn('Formulario no encontrado');
		if (validarFormulario()) {
			const doSubmit = () => {
				if (operacionInput) operacionInput.value = 'actualizar';
				form.submit();
			};
			if (typeof Swal !== 'undefined') {
				Swal.fire({ title: 'Actualizar registro', text: '¿Deseas guardar los cambios?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' }).then((r)=>{ if (r.isConfirmed) doSubmit(); });
			} else {
				if (confirm('¿Deseas guardar los cambios?')) doSubmit();
			}
		}
	});

	if (btnCancelar) btnCancelar.addEventListener('click', function () { limpiarFormulario(); try { btnCancelar.style.display = 'none'; } catch(e){} });

	// Botones editar en la tabla
	document.querySelectorAll('.editar-btn').forEach(btn => {
		btn.addEventListener('click', function () {
			const id = this.getAttribute('data-id');
			const empleado = this.getAttribute('data-empleado');
			const fecha = this.getAttribute('data-fecha');
			const horas = this.getAttribute('data-horas');
			const pago = this.getAttribute('data-pago');
			const total = this.getAttribute('data-total');

			const doFill = () => {
				if (idBonificacionInput) idBonificacionInput.value = id || '';
				if (idEmpleadoInput) idEmpleadoInput.value = empleado || '';
				if (fechaInput) fechaInput.value = fecha || '';
				if (horasInput) horasInput.value = horas || '';
				if (pagoInput) pagoInput.value = pago || '';
				if (montoInput) montoInput.value = total ? Number(total).toFixed(2) : '';
				// Trigger change to load puesto/sueldo
				if (idEmpleadoInput) idEmpleadoInput.dispatchEvent(new Event('change'));
				habilitarCampos();
				mostrarBotonesActualizar();
			};

			if (typeof Swal !== 'undefined') {
				Swal.fire({ title: 'Editar horas extra', text: '¿Deseas editar este registro?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' }).then((r)=>{ if (r.isConfirmed) doFill(); });
			} else {
				doFill();
			}
		});
	});

	function showWarning(msg) {
		if (typeof Swal !== 'undefined') {
			Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
		} else {
			alert(msg);
		}
	}

	function validarFormulario() {
		const empleado = idEmpleadoInput ? idEmpleadoInput.value : '';
		const fecha = fechaInput ? fechaInput.value.trim() : '';
		const horas = horasInput ? parseFloat(horasInput.value) : 0;
		const pago = pagoInput ? parseFloat(pagoInput.value) : 0;

		if (!empleado) { showWarning('Debe seleccionar un empleado'); if (idEmpleadoInput) idEmpleadoInput.focus(); return false; }
		if (!fecha) { showWarning('La fecha es requerida'); if (fechaInput) fechaInput.focus(); return false; }
		if (!horas || horas <= 0) { showWarning('Las horas extras deben ser mayores a 0'); if (horasInput) horasInput.focus(); return false; }
		if (!pago || pago <= 0) { showWarning('El pago por hora debe ser mayor a 0'); if (pagoInput) pagoInput.focus(); return false; }
		return true;
	}

	// Interceptar formularios de eliminación marcados con data-eliminar="true"
	document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
		f.addEventListener('submit', function (evt) {
			evt.preventDefault();
			const frm = this;
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: '¿Eliminar registro?',
					text: 'Esta acción no se puede deshacer.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'Sí',
					cancelButtonText: 'Cancelar'
				}).then((result) => { if (result.isConfirmed) frm.submit(); });
			} else {
				if (confirm('¿Eliminar registro?')) frm.submit();
			}
		});
	});

	// Mostrar mensaje enviado desde el servidor
	try {
		if (window.__mensaje && typeof window.__mensaje === 'object') {
			const m = window.__mensaje;
			const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : 'error';
			if (typeof Swal !== 'undefined') {
				Swal.fire({ title: icon === 'success' ? 'Éxito' : 'Atención', text: m.text, icon: icon });
			} else {
				alert(m.text);
			}
			try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
		}
	} catch (e) { /* no bloquear la carga si falla */ }

});
