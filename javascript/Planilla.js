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

  // ==== Exportaciones en Detalle de Planilla PDF/EXCEl
  const btnPdf = document.getElementById('btn-pdf');
  if (btnPdf) {
    btnPdf.addEventListener('click', function () {
      try {
        const jspdfNS = window.jspdf || {};
        const jsPDF = jspdfNS.jsPDF;
        if (!jsPDF) { alert('No se pudo cargar jsPDF'); return; }

        const doc = new jsPDF('l', 'pt', 'a4');

        // Tomar textos desde el DOM
        const titleEl = document.querySelector('#export-area h2');
        const infoEl = document.querySelector('#export-area p');
        const titulo = titleEl ? titleEl.textContent.trim() : 'Detalle de Planilla';
        const info = infoEl ? infoEl.textContent.trim() : '';

        doc.setFontSize(16);
        doc.text(titulo, 40, 40);
        doc.setFontSize(11);
        if (info) doc.text(info, 40, 60);

        if (typeof doc.autoTable !== 'function') {
          alert('No está disponible autoTable para jsPDF');
          return;
        }
        doc.autoTable({ html: '#tabla-detalle table', startY: 100, styles: { fontSize: 9 } });

        // Construir nombre de archivo a partir del título si es posible
        let nombre = 'Planilla.pdf';
        const m = titulo.match(/([A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)\s+(\d{4})/);
        if (m) nombre = `Planilla_${m[2]}_${m[1]}.pdf`;
        doc.save(nombre);
      } catch (e) {
        console.error(e);
        alert('No fue posible generar el PDF.');
      }
    });
  }

  const btnExcel = document.getElementById('btn-excel');
  if (btnExcel) {
    btnExcel.addEventListener('click', async function () {
      try {
        if (typeof html2canvas === 'undefined') { alert('No está disponible html2canvas'); return; }
        if (typeof ExcelJS === 'undefined') { alert('No está disponible ExcelJS'); return; }
        if (typeof saveAs === 'undefined') { alert('No está disponible FileSaver'); return; }

        const area = document.getElementById('export-area');
        if (!area) return;

        const canvas = await html2canvas(area, {
          backgroundColor: '#ffffff',
          scale: window.devicePixelRatio < 2 ? 2 : window.devicePixelRatio
        });
        const dataUrl = canvas.toDataURL('image/png');

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet('Detalle');
        const imageId = wb.addImage({ base64: dataUrl, extension: 'png' });
        ws.addImage(imageId, {
          tl: { col: 0, row: 0 },
          ext: { width: canvas.width, height: canvas.height }
        });

        // Crear espacio para visualizar la imagen completa
        const approxColWidth = Math.ceil(canvas.width / 7);
        ws.getColumn(1).width = Math.min(255, approxColWidth);
        ws.getRow(1).height = Math.ceil(canvas.height * 0.75);

        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

        // Nombre a partir del título si se puede
        const titleEl = document.querySelector('#export-area h2');
        const titulo = titleEl ? titleEl.textContent.trim() : '';
        let nombre = 'Planilla.xlsx';
        const m = titulo.match(/([A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)\s+(\d{4})/);
        if (m) nombre = `Planilla_${m[2]}_${m[1]}.xlsx`;
        saveAs(blob, nombre);
      } catch (e) {
        console.error(e);
        alert('No fue posible exportar a Excel.');
      }
    });
  }
});
