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
          text: `Se generará la planilla para ${mes}/${anio} y se guardará en el historial. ¿Continuar?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Si',
          cancelButtonText: 'Cancelar'
        }).then(r => { if (r.isConfirmed) doSubmit(); });
      } else {
        if (confirm('Se generará la planilla y se guardará en el histórico. ¿Continuar?')) doSubmit();
      }
    });
  }

  if (btnGenerar) {
    btnGenerar.addEventListener('click', function () {
      // El submit ya muestra confirmación; aquí solo podemos deshabilitar el botón mientras se procesa
      try { btnGenerar.disabled = true; setTimeout(()=>{ btnGenerar.disabled = false; }, 1500); } catch(e){}
    });
  }

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
      syncHidden(); 
    });
  }

  // inicializar
  syncHidden();

  // Exportaciones en Detalle de Planilla PDF/EXCEl
  const btnPdf = document.getElementById('btn-pdf');
  if (btnPdf) {
    btnPdf.addEventListener('click', function () {
      try {
        const jspdfNS = window.jspdf || {};
        const jsPDF = jspdfNS.jsPDF;
        if (!jsPDF) { alert('No se pudo cargar jsPDF'); return; }

        const doc = new jsPDF('l', 'pt', 'a4');

  
  const titleEl = document.querySelector('#export-area h2');
  const infoEl = document.querySelector('#export-area p');
  // Normalizar espacios internos para evitar saltos de línea heredados del HTML
  const titulo = titleEl ? titleEl.textContent.replace(/\s+/g,' ').trim() : 'Detalle de Planilla';
  const info = infoEl ? infoEl.textContent.replace(/\s+/g,' ').trim() : '';

 
        const marginLeft = 40;
        const marginTop = 40;
        const pageWidth = doc.internal.pageSize.getWidth();
        const maxWidth = pageWidth - marginLeft * 2;


    doc.setFontSize(16);
    const titleLines = doc.splitTextToSize(titulo, maxWidth);
    doc.text(titleLines, marginLeft, marginTop);
    // Calcular altura aproximada del bloque de título según el tamaño de fuente y número de líneas
    const titleLineHeight = doc.getFontSize() * 1.2;
    let currentY = marginTop + (Array.isArray(titleLines) ? titleLines.length : 1) * titleLineHeight;


        if (info) {
          currentY += 8;
          doc.setFontSize(11);
          const infoLines = doc.splitTextToSize(info, maxWidth);
          const infoTop = currentY;
          doc.text(infoLines, marginLeft, infoTop);
          const infoLineHeight = doc.getFontSize() * 1.25;
          currentY = infoTop + (Array.isArray(infoLines) ? infoLines.length : 1) * infoLineHeight;
        }

        if (typeof doc.autoTable !== 'function') {
          alert('No está disponible autoTable para jsPDF');
          return;
        }

  const startY = Math.max(currentY + 16, 100);
        doc.autoTable({ html: '#tabla-detalle table', startY, styles: { fontSize: 9 } });

        // Nombre del archivo
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

        // Nombre excel
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
