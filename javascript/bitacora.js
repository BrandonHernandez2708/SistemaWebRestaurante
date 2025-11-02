document.addEventListener("DOMContentLoaded", () => {
  const selectUsuario = document.getElementById("filtro-usuario");
  const inputInicio = document.getElementById("filtro-fecha-inicio");
  const inputFin = document.getElementById("filtro-fecha-fin");
  const btnFiltrar = document.getElementById("btn-filtrar");
  const btnLimpiar = document.getElementById("btn-limpiar");
  const tbody = document.querySelector("#tabla-bitacora tbody");

  // Cargar usuarios dinámicamente
  fetch("../bitacora_crud.php", {
    method: "POST",
    body: new URLSearchParams({ accion: "usuarios" }),
  })
    .then((r) => r.json())
    .then((res) => {
      selectUsuario.innerHTML = `<option value="">Todos los usuarios</option>`;
      if (res.status === "ok") {
        res.data.forEach((u) => {
          const opt = document.createElement("option");
          opt.value = u.usuario;
          opt.textContent = u.usuario;
          selectUsuario.appendChild(opt);
        });
      }
    })
    .catch(() => {
      selectUsuario.innerHTML = `<option value="">Error al cargar usuarios</option>`;
    });

  // Cargar bitácora inicial
  cargarBitacora();

  // --- Filtrar ---
  btnFiltrar.addEventListener("click", (e) => {
    e.preventDefault();
    const usuario = selectUsuario.value;
    const fechaInicio = inputInicio.value;
    const fechaFin = inputFin.value;

    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
      Swal.fire({
        icon: "warning",
        title: "Fechas inválidas",
        text: "La fecha de inicio no puede ser mayor que la fecha de fin.",
      });
      return;
    }

    Swal.fire({
      icon: "info",
      title: "Buscando registros...",
      html: `
        <b>Usuario:</b> ${usuario || "Todos"}<br>
        <b>Desde:</b> ${fechaInicio || "Sin filtro"}<br>
        <b>Hasta:</b> ${fechaFin || "Sin filtro"}
      `,
      showConfirmButton: false,
      timer: 1200,
    });

    setTimeout(() => cargarBitacora(usuario, fechaInicio, fechaFin), 600);
  });

  // --- Limpiar ---
  btnLimpiar.addEventListener("click", () => {
    Swal.fire({
      title: "¿Restablecer filtros?",
      text: "Se mostrarán todos los registros nuevamente.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, limpiar",
      cancelButtonText: "Cancelar",
    }).then((r) => {
      if (r.isConfirmed) {
        selectUsuario.value = "";
        inputInicio.value = "";
        inputFin.value = "";
        Swal.fire({
          icon: "success",
          title: "Filtros limpiados",
          timer: 900,
          showConfirmButton: false,
        });
        setTimeout(() => cargarBitacora(), 500);
      }
    });
  });

  // --- Función principal ---
  function cargarBitacora(usuario = "", fechaInicio = "", fechaFin = "") {
    const datos = new URLSearchParams();
    datos.append("accion", "listar");
    datos.append("usuario", usuario);
    datos.append("fecha_inicio", fechaInicio);
    datos.append("fecha_fin", fechaFin);

    fetch("../bitacora_crud.php", {
      method: "POST",
      body: datos,
    })
      .then((r) => r.json())
      .then((res) => {
        tbody.innerHTML = "";
        if (res.status === "ok" && res.data.length > 0) {
          res.data.forEach((row) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${row.id_bitacora}</td>
              <td>${row.usuario}</td>
              <td>${row.operacion_realizada}</td>
              <td>${row.ip}</td>
              <td>${row.pc}</td>
              <td>${row.fecha_hora_accion}</td>
            `;
            tbody.appendChild(tr);
          });
          Swal.fire({
            icon: "success",
            title: "Resultados actualizados",
            text: `Se encontraron ${res.data.length} registros.`,
            timer: 1300,
            showConfirmButton: false,
          });
        } else {
          tbody.innerHTML =
            '<tr><td colspan="6" class="text-muted py-3">No hay registros de bitácora</td></tr>';
          Swal.fire({
            icon: "info",
            title: "Sin resultados",
            text: "No se encontraron registros con esos filtros.",
            timer: 1200,
            showConfirmButton: false,
          });
        }
      })
      .catch((err) => {
        console.error("Error al cargar bitácora:", err);
        Swal.fire({
          icon: "error",
          title: "Error de conexión",
          text: "No se pudo obtener la bitácora del servidor.",
        });
      });
  }
});
