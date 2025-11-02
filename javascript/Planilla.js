// Planilla.js — validaciones y confirmaciones coherentes con otros módulos
document.addEventListener('DOMContentLoaded', function () {
  const formPlanilla = document.getElementById('form-planilla');
  const btnGenerar = document.getElementById('btn-generar');

  function showWarning(msg) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
    } else {
      alert(msg);
    }
  }

  if (formPlanilla) {
    formPlanilla.addEventListener('submit', function (e) {
      e.preventDefault();
      const mesEl = document.getElementById('mes_planilla');
      const anioEl = document.getElementById('anio_planilla');
      const mes = mesEl ? parseInt(mesEl.value || '0', 10) : 0;
      const anio = anioEl ? parseInt(anioEl.value || '0', 10) : 0;

      if (!mes || anio < 2000) {
        showWarning('Selecciona un mes y un año válidos.');
        if (!mes) mesEl && mesEl.focus(); else anioEl && anioEl.focus();
        return;
      }

      const doSubmit = () => formPlanilla.submit();

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Generar planilla',
          text: `Se generará la planilla para ${mes}/${anio} y se guardará en el histórico. ¿Continuar?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, generar',
          cancelButtonText: 'Cancelar'
        }).then(r => { if (r.isConfirmed) doSubmit(); });
      } else {
        if (confirm('Se generará la planilla y se guardará en el histórico. ¿Continuar?')) doSubmit();
      }
    });
  }

  // Mejorar UX: prevenir doble envío del botón
  if (btnGenerar) {
    btnGenerar.addEventListener('click', function () {
      // El submit ya muestra confirmación; aquí solo podemos deshabilitar el botón mientras se procesa
      try { btnGenerar.disabled = true; setTimeout(()=>{ btnGenerar.disabled = false; }, 1500); } catch(e){}
    });
  }

  // Sincronizar selects visibles (mes/anio) con los hidden inputs del formulario de guardado
  // y comportamiento del botón 'Nuevo' (resetear valores)
  const mesSelect = document.getElementById('mes_select');
  const anioInput = document.getElementById('anio_input');
  const hiddenMes = document.getElementById('hidden_mes');
  const hiddenAnio = document.getElementById('hidden_anio');
  const formGuardar = document.getElementById('form-guardar');
  const btnNuevo = document.getElementById('btn-nuevo');
  const fechaGenEl = document.getElementById('fecha_generacion_manual');

  function syncHidden(){
    try{
      if (mesSelect && hiddenMes) hiddenMes.value = mesSelect.value;
      if (anioInput && hiddenAnio) hiddenAnio.value = anioInput.value;
    }catch(e){}
  }

  if (mesSelect) mesSelect.addEventListener('change', syncHidden);
  if (anioInput) anioInput.addEventListener('input', syncHidden);
  if (formGuardar) {
    formGuardar.addEventListener('submit', function(e){
      syncHidden(); // asegurar valores actualizados antes de enviar
    });
  }

  // inicializar
  syncHidden();

  // Botón Nuevo: resetear campos a valores por defecto (mes actual, año actual, fecha generación hoy)
  if (btnNuevo) {
    btnNuevo.addEventListener('click', function(){
      try {
        const now = new Date();
        const month = now.getMonth() + 1; // 1-12
        const year = now.getFullYear();
        if (mesSelect) mesSelect.value = String(month);
        if (anioInput) anioInput.value = String(year);
        if (fechaGenEl) {
          const y = year.toString().padStart(4,'0');
          const m = String(month).padStart(2,'0');
          const d = String(now.getDate()).padStart(2,'0');
          fechaGenEl.value = `${y}-${m}-${d}`;
        }
        syncHidden();
      } catch(e){}
    });
  }
});
