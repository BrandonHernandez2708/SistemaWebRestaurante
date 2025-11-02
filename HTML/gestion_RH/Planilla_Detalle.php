<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

function mes_nombre($m) {
    $nombres = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    return $nombres[intval($m)] ?? $m;
}

$id_planilla = intval($_GET['id_planilla'] ?? 0);
if ($id_planilla <= 0) { die('Parámetro inválido.'); }

$conn = conectar();
$stmt = $conn->prepare("SELECT id_planilla, mes, anio, fecha_generacion, total_empleados, total_general
                        FROM planilla WHERE id_planilla=?");
$stmt->bind_param('i', $id_planilla);
$stmt->execute();
$head = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$head) { desconectar($conn); die('Planilla no encontrada.'); }

$sql = "SELECT d.id_empleado, CONCAT(e.nombre_empleado,' ',e.apellido_empleado) AS empleado,
               p.puesto, d.sueldo_base, d.bono_fijo, d.bonificacion, d.penalizacion, d.igss, d.sueldo_total
        FROM detalle_planilla d
        INNER JOIN empleados e ON d.id_empleado = e.id_empleado
        INNER JOIN puesto p ON e.id_puesto = p.id_puesto
        WHERE d.id_planilla=?
        ORDER BY empleado";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_planilla);
$stmt->execute();
$res = $stmt->get_result();
$detalles = [];
while ($r = $res->fetch_assoc()) $detalles[] = $r;
$stmt->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle Planilla - <?= mes_nombre($head['mes']).' '.$head['anio'] ?></title>
<link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0">Detalle de Planilla</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item">
                <a href="../menu_empleados.php" class="btn-back" aria-label="Regresar al menú principal">
                    <span class="arrow">←</span><span>Regresar al Menú</span>
                </a>
            </li>
        </ul>
    </div>
</header>

<main class="container my-4">
<div id="export-area">
<section class="card shadow p-4 mb-4">
  <h2 class="text-primary mb-3">
    <?= mes_nombre($head['mes']).' '.$head['anio'] ?> &middot;
    Generada: <?= htmlspecialchars($head['fecha_generacion']) ?>
  </h2>
  <p class="mb-0"><strong>Empleados:</strong> <?= intval($head['total_empleados']) ?> &nbsp; | &nbsp;
     <strong>Total General:</strong> Q <?= number_format($head['total_general'],2) ?></p>
</section>

<section class="card shadow p-4">
  <h3 class="mb-3">Detalle por empleado</h3>
  <div class="table-responsive" id="tabla-detalle">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Empleado</th>
          <th>Puesto</th>
          <th>Sueldo Base</th>
          <th>Bono Fijo</th>
          <th>Bonificación</th>
          <th>Penalizaciones</th>
          <th>IGSS</th>
          <th>Sueldo Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($detalles)): ?>
          <?php foreach ($detalles as $d): ?>
            <tr>
              <td><?= $d['id_empleado'] ?></td>
              <td><?= htmlspecialchars($d['empleado']) ?></td>
              <td><?= htmlspecialchars($d['puesto']) ?></td>
              <td>Q <?= number_format($d['sueldo_base'],2) ?></td>
              <td>Q <?= number_format($d['bono_fijo'],2) ?></td>
              <td>Q <?= number_format($d['bonificacion'],2) ?></td>
              <td>Q <?= number_format($d['penalizacion'],2) ?></td>
              <td>Q <?= number_format($d['igss'],2) ?></td>
              <td class="fw-bold text-success">Q <?= number_format($d['sueldo_total'],2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center">Sin detalle</td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="8" class="text-end">Total General:</th>
          <th class="fw-bold text-success">Q <?= number_format($head['total_general'],2) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="text-end mt-3">
    <button class="btn btn-outline-primary" id="btn-pdf">🖨 Generar PDF</button>
    <button class="btn btn-outline-success ms-2" id="btn-excel">🗂 Exportar Excel</button>
  </div>
</section>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jsPDF para exportar a PDF en el cliente -->
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js"></script>
<!-- html2canvas para capturar la vista como imagen -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<!-- ExcelJS y FileSaver para generar XLSX con imagen -->
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<script>
document.getElementById('btn-pdf').addEventListener('click', function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('l', 'pt', 'a4');

  doc.setFontSize(16);
  doc.text('Detalle de Planilla - <?= mes_nombre($head['mes']).' '.$head['anio'] ?>', 40, 40);
  doc.setFontSize(11);
  doc.text('Generada: <?= htmlspecialchars($head['fecha_generacion']) ?>', 40, 60);
  doc.text('Empleados: <?= intval($head['total_empleados']) ?>', 40, 78);
  doc.text('Total General: Q <?= number_format($head['total_general'],2) ?>', 220, 78);

  // Construir tabla a partir del HTML
  doc.autoTable({ html: '#tabla-detalle table', startY: 100, styles: { fontSize: 9 } });

  doc.save('Planilla_<?= $head['anio'] ?>_<?= $head['mes'] ?>.pdf');
});

document.getElementById('btn-excel').addEventListener('click', async function(){
  const area = document.getElementById('export-area');
  if (!area) return;

  // Capturar como imagen con buena resolución
  const canvas = await html2canvas(area, {
    backgroundColor: '#ffffff',
    scale: window.devicePixelRatio < 2 ? 2 : window.devicePixelRatio
  });
  const dataUrl = canvas.toDataURL('image/png');

  // Crear libro y hoja
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Detalle');

  const imageId = wb.addImage({ base64: dataUrl, extension: 'png' });
  ws.addImage(imageId, {
    tl: { col: 0, row: 0 },
    ext: { width: canvas.width, height: canvas.height }
  });


  const approxColWidth = Math.ceil(canvas.width / 7); 
  ws.getColumn(1).width = Math.min(255, approxColWidth); 
  ws.getRow(1).height = Math.ceil(canvas.height * 0.75); 

  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const nombre = 'Planilla_<?= $head['anio'] ?>_<?= $head['mes'] ?>.xlsx';
  saveAs(blob, nombre);
});
</script>
</body>
</html>
