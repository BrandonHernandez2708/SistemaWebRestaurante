document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  const btnBuscar = document.getElementById("btnBuscar");
  const btnLimpiar = document.getElementById("btnLimpiar");

  const selCliente = document.getElementById("cliente");
  const selEstado  = document.getElementById("estado");
  const inpDesde   = document.getElementById("desde");
  const inpHasta   = document.getElementById("hasta");

  // Utilidad: construir querystring solo con filtros llenos
  function buildQuery() {
    const params = new URLSearchParams();
    const c = selCliente?.value?.trim() || "";
    const e = selEstado?.value?.trim()  || "";
    const d = inpDesde?.value?.trim()   || "";
    const h = inpHasta?.value?.trim()   || "";

    if (c) params.set("cliente", c);
    if (e) params.set("estado",  e);
    if (d) params.set("desde",   d);     // <input type="date"> ya envía YYYY-MM-DD
    if (h) params.set("hasta",   h);

    return params;
  }

  // Validación de fechas
  function validarFechas() {
    const d = inpDesde.value.trim();
    const h = inpHasta.value.trim();
    if (d && h && d > h) {
      Swal.fire({
        icon: "warning",
        title: "Rango de fechas inválido",
        text: "La fecha 'Desde' no puede ser mayor que 'Hasta'.",
        confirmButtonColor: "#1E3A8A",
      });
      return false;
    }
    return true;
  }

  // Buscar (construye URL y navega)
  btnBuscar.addEventListener("click", (e) => {
    e.preventDefault();

    if (!validarFechas()) return;

    const params = buildQuery();

    // Feedback visual
    Swal.fire({
      icon: "info",
      title: "Buscando reservaciones...",
      html: `
        <div style="text-align:left">
          <b>Cliente:</b> ${selCliente.value || "Todos"}<br>
          <b>Estado:</b> ${selEstado.value || "Todos"}<br>
          <b>Desde:</b> ${inpDesde.value || "Sin filtro"}<br>
          <b>Hasta:</b> ${inpHasta.value || "Sin filtro"}
        </div>
      `,
      showConfirmButton: false,
      timer: 1000,
    });

    // Navega con filtros (si no hay filtros, va sin query)
    setTimeout(() => {
      const base = "reporte_reservaciones.php";
      const url  = params.toString() ? `${base}?${params.toString()}` : base;
      // usa replace para que el back no vuelva al estado previo del mismo reporte
      window.location.replace(url);
    }, 400);
  });

  // Limpiar (borra TODO, incluidas fechas, y regresa a vista general)
  btnLimpiar.addEventListener("click", (e) => {
    e.preventDefault();

    Swal.fire({
      title: "¿Restablecer filtros?",
      text: "Se mostrarán todas las reservaciones nuevamente.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, limpiar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#1E3A8A",
      cancelButtonColor: "#6b7280",
    }).then((r) => {
      if (!r.isConfirmed) return;

      // Reset de controles (incluye fechas)
      if (selCliente) selCliente.value = "";
      if (selEstado)  selEstado.value  = "";
      if (inpDesde)   inpDesde.value   = "";
      if (inpHasta)   inpHasta.value   = "";

      Swal.fire({
        icon: "success",
        title: "Filtros limpiados",
        text: "Vista general restaurada.",
        timer: 900,
        showConfirmButton: false,
      });

      setTimeout(() => {
        // Vuelve a la página base sin parámetros
        window.location.replace("reporte_reservaciones.php");
      }, 600);
    });
  });

  // Al exportar a Excel (solo feedback)
  const btnExport = document.querySelector(".btn-excel");
  if (btnExport) {
    btnExport.addEventListener("click", () => {
      Swal.fire({
        icon: "success",
        title: "Generando Excel...",
        text: "La descarga iniciará enseguida.",
        timer: 900,
        showConfirmButton: false,
      });
    });
  }
});
