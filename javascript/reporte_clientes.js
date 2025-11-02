document.addEventListener("DOMContentLoaded", () => {
  const inputBusqueda = document.getElementById("busqueda");
  const btnBuscar = document.getElementById("btnBuscar");
  const btnLimpiar = document.getElementById("btnLimpiar");

  // Buscar
  btnBuscar.addEventListener("click", (e) => {
    e.preventDefault();
    const texto = inputBusqueda.value.trim();

    Swal.fire({
      icon: "info",
      title: "Buscando clientes...",
      text: texto ? `Filtro aplicado: "${texto}"` : "Mostrando todos los clientes",
      showConfirmButton: false,
      timer: 1200,
    });

    setTimeout(() => {
      const url = texto ? `reporte_clientes.php?busqueda=${encodeURIComponent(texto)}` : "reporte_clientes.php";
      window.location.replace(url);
    }, 500);
  });

  // Limpiar
  btnLimpiar.addEventListener("click", (e) => {
    e.preventDefault();
    Swal.fire({
      title: "¿Restablecer búsqueda?",
      text: "Se mostrarán todos los clientes nuevamente.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, limpiar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#1E3A8A",
    }).then((r) => {
      if (r.isConfirmed) {
        inputBusqueda.value = "";
        Swal.fire({
          icon: "success",
          title: "Filtros limpiados",
          showConfirmButton: false,
          timer: 1000,
        });
        setTimeout(() => window.location.replace("reporte_clientes.php"), 600);
      }
    });
  });

  // Exportar
  const btnExport = document.querySelector(".btn-excel");
  if (btnExport) {
    btnExport.addEventListener("click", () => {
      Swal.fire({
        icon: "success",
        title: "Generando archivo Excel...",
        text: "Tu descarga comenzará en un momento.",
        timer: 1200,
        showConfirmButton: false,
      });
    });
  }
});
