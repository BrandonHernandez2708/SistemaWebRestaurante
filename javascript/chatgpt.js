document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-chatgpt");
  const txtConsulta = document.getElementById("consulta");
  const sqlBox = document.getElementById("sql-generado");
  const btnLimpiar = document.getElementById("btn-limpiar");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const consulta = txtConsulta.value.trim();

    if (!consulta) {
      Swal.fire("Advertencia", "Por favor, escribe una descripción de la consulta.", "warning");
      return;
    }

    Swal.fire({
      title: "Generando consulta...",
      text: "Por favor espera unos segundos",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      const resp = await fetch("procesar_consulta.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ consulta }),
    });

      const data = await resp.json();
      Swal.close();

      if (data.status === "ok") {
        sqlBox.textContent = data.sql;
      } else {
        Swal.fire("Error", data.message, "error");
      }
    } catch (err) {
      Swal.close();
      Swal.fire("Error", "Ocurrió un problema al comunicarse con el servidor.", "error");
    }
  });

  btnLimpiar.addEventListener("click", () => {
    txtConsulta.value = "";
    sqlBox.textContent = "Esperando consulta...";
  });
});
