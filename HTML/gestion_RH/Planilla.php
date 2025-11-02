<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

function mes_nombre($m) {
    $nombres = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $m = intval($m);
    return $nombres[$m] ?? $m;
}

// Formato de fecha para mostrar: d/m/Y
function fecha_esp($f) {
  if (!$f) return '';
  // Tomar solo la parte de fecha si viniera con hora
  $solo = substr($f, 0, 10);
  $d = DateTime::createFromFormat('Y-m-d', $solo);
  if ($d) return $d->format('d/m/Y');
  // Intentar Y-m-d H:i:s
  $d2 = DateTime::createFromFormat('Y-m-d H:i:s', $f);
  if ($d2) return $d2->format('d/m/Y');
  return htmlspecialchars($f);
}


// 1) Cargar histórico de planillas
$conn = conectar();
$hist = $conn->query("SELECT id_planilla, mes, anio, fecha_generacion, total_empleados, total_general
                      FROM planilla ORDER BY anio ASC, mes ASC");
$planillas = [];
while ($h = $hist->fetch_assoc()) $planillas[] = $h;
desconectar($conn);

// 2) Acciones: Guardar planilla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
  $mes = intval($_POST['mes'] ?? 0);
  $anio = intval($_POST['anio'] ?? 0);

  if ($mes < 1 || $mes > 12 || $anio < 2000) {
    $_SESSION['mensaje'] = 'Selecciona un mes y año válidos.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: Planilla.php?mes=' . $mes . '&anio=' . $anio);
    exit();
  } else {
        $conn = conectar();

        // ¿Ya existe?
        $stmt = $conn->prepare("SELECT id_planilla FROM planilla WHERE mes=? AND anio=? LIMIT 1");
        $stmt->bind_param('ii', $mes, $anio);
        $stmt->execute();
        $stmt->bind_result($id_existente);
        $ya = $stmt->fetch();
        $stmt->close();

    if ($ya) {
      $_SESSION['mensaje'] = 'Ya existe una planilla guardada para ' . mes_nombre($mes) . ' ' . $anio . '.';
      $_SESSION['tipo_mensaje'] = 'warning';
      desconectar($conn);
      header('Location: Planilla.php?mes=' . $mes . '&anio=' . $anio);
      exit();
    } else {
      $sql = "SELECT 
            e.id_empleado, p.sueldo_base,
            250 AS bono_fijo,
            IFNULL(SUM(b.monto_bonificacion),0) AS bonificacion,
            IFNULL(pen.penalizacion,0) AS penalizacion,
            ROUND(p.sueldo_base * 0.0483, 2) AS igss
          FROM empleados e
          INNER JOIN puesto p ON e.id_puesto = p.id_puesto
          LEFT JOIN bonificaciones b
            ON b.id_empleado = e.id_empleado
            AND MONTH(b.fecha_bonificacion)=?
            AND YEAR(b.fecha_bonificacion)=?
          LEFT JOIN (
            SELECT id_empleado, SUM(descuento_penalizacion) AS penalizacion
            FROM penalizaciones
            WHERE MONTH(fecha_penalizacion)=? AND YEAR(fecha_penalizacion)=?
            GROUP BY id_empleado
          ) pen ON pen.id_empleado = e.id_empleado
          GROUP BY e.id_empleado";

      $fechaGen = date('Y-m-d');
      $fechaGenManual = trim($_POST['fecha_generacion_manual'] ?? '');
      if ($fechaGenManual !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $fechaGenManual);
        if ($d && $d->format('Y-m-d') === $fechaGenManual) {
          $fechaGen = $fechaGenManual;
        }
      }

      $insH = $conn->prepare("INSERT INTO planilla (mes, anio, fecha_generacion, total_empleados, total_general)
                  VALUES (?,?,?,0,0.00)");
      $insH->bind_param('iis', $mes, $anio, $fechaGen);
            $insH->execute();
            $id_planilla = $insH->insert_id;
            $insH->close();

            // Calcular detalle
      $st = $conn->prepare($sql);
      $st->bind_param('iiii', $mes, $anio, $mes, $anio);
            $st->execute();
            $rs = $st->get_result();

            $total_general = 0; $total_empleados = 0;
            $insD = $conn->prepare("INSERT INTO detalle_planilla
                (id_planilla, id_empleado, sueldo_base, bono_fijo, bonificacion, penalizacion, igss, sueldo_total)
                VALUES (?,?,?,?,?,?,?,?)");

            while ($r = $rs->fetch_assoc()) {
                $id_emp = intval($r['id_empleado']);
                $sueldo_base = floatval($r['sueldo_base']);
                $bono_fijo = floatval($r['bono_fijo']);
        $bonificacion = floatval($r['bonificacion']);
        $penalizacion = floatval($r['penalizacion'] ?? 0);
                $igss = floatval($r['igss']);
                $sueldo_total = round(($sueldo_base + $bono_fijo + $bonificacion) - ($penalizacion + $igss), 2);

                $insD->bind_param('iidddddd', $id_planilla, $id_emp, $sueldo_base, $bono_fijo, $bonificacion, $penalizacion, $igss, $sueldo_total);
                $insD->execute();
                $total_empleados++;
                $total_general += $sueldo_total;
            }
            $insD->close();
            $st->close();

            // Update totals
            $up = $conn->prepare("UPDATE planilla SET total_empleados=?, total_general=? WHERE id_planilla=?");
            $up->bind_param('idi', $total_empleados, $total_general, $id_planilla);
            $up->execute();
            $up->close();

      $_SESSION['mensaje'] = 'Planilla general ' . mes_nombre($mes) . ' ' . $anio . ' guardada correctamente.';
      $_SESSION['tipo_mensaje'] = 'success';
      desconectar($conn);
      header('Location: Planilla.php?mes=' . $mes . '&anio=' . $anio);
      exit();
        }
    }
}

$mes = intval($_GET['mes'] ?? date('n'));
$anio = intval($_GET['anio'] ?? date('Y'));
$fecha_filtro = trim($_GET['fecha'] ?? ''); // YYYY-MM-DD opcional
$filtro_activo = false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Planillas (con filtros)</title>
<link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0">Gestión de Planillas</h1>
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
<?php if (isset($_SESSION['mensaje'])): ?>
<script>
  window.__mensaje = {text: <?= json_encode($_SESSION['mensaje']) ?>, tipo: <?= json_encode($_SESSION['tipo_mensaje'] ?? 'error') ?>};
</script>
<noscript>
  <div class="alert alert-<?= ($_SESSION['tipo_mensaje'] ?? '') === 'success' ? 'success' : (($_SESSION['tipo_mensaje'] ?? '') === 'warning' ? 'warning' : 'danger') ?>">
    <?= htmlspecialchars($_SESSION['mensaje']) ?>
  </div>
</noscript>
<?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); endif; ?>

<section class="card shadow p-4 mb-4">
  <h2 class="card__title text-primary mb-4">Generar / Visualizar planilla</h2>
  <form class="row g-3" id="form-buscar" method="get" action="Planilla_Detalle.php">


    <div class="col-md-2" id="col-mes">
      <label class="form-label">Mes</label>
      <select class="form-select" name="mes" id="mes_select">
        <?php for($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= $m==$mes?'selected':'' ?>><?= mes_nombre($m) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-2" id="col-anio">
      <label class="form-label">Año</label>
      <input type="number" class="form-control" name="anio" id="anio_input" min="2000" max="<?= date('Y')+1 ?>" value="<?= htmlspecialchars($anio) ?>">
    </div>
    <div class="col-md-3" id="col-fecha" style="display:none;">
      <label class="form-label">Fecha</label>
      <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fecha_filtro) ?>">
    </div>

  </form>
  <form class="mt-3" id="form-guardar" method="post">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="mes" id="hidden_mes" value="<?= htmlspecialchars($mes) ?>">
    <input type="hidden" name="anio" id="hidden_anio" value="<?= htmlspecialchars($anio) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Fecha de generación (la introducirás tú)</label>
        <input type="date" class="form-control" name="fecha_generacion_manual" id="fecha_generacion_manual" value="<?= date('Y-m-d') ?>" required>
        <small class="form-text text-muted help-text">Introduce la fecha exacta en que se generó la planilla (ej: 2025-01-31).</small>
      </div>

      <div class="col-12 mt-3">
        <div class="d-flex gap-2">
          <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
          <button id="btn-guardar" type="submit" class="btn btn-success" data-con-filtro="<?= $filtro_activo ? '1':'0' ?>">Guardar planilla (histórico)</button>
        </div>
      </div>
    </div>
  </form>
</section>


<section class="card shadow p-4">
  <h2 class="text-primary mb-3">Histórico de planillas guardadas</h2>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Año</th>
          <th>Mes</th>
          <th>Fecha de Generación</th>
          <th>Total Empleados</th>
          <th>Total a Pagar</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($planillas)): ?>
          <?php foreach ($planillas as $pl): ?>
            <tr>
              <td><?= $pl['anio'] ?></td>
              <td><?= mes_nombre($pl['mes']) ?></td>
              <td><?= fecha_esp($pl['fecha_generacion']) ?></td>
              <td><?= $pl['total_empleados'] ?></td>
              <td>Q <?= number_format($pl['total_general'],2) ?></td>
              <td>
                <a class="btn btn-primary btn-sm"
                   href="Planilla_Detalle.php?id_planilla=<?= $pl['id_planilla'] ?>">Ver Detalle</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">Aún no hay planillas guardadas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Planilla.js"></script>
<script>
  (function(){
    const m = window.__mensaje;
    if (!m) return;
    Swal.fire({
      icon: m.tipo==='success'?'success':(m.tipo==='warning'?'warning':'error'),
      title: m.tipo==='success'?'Éxito':(m.tipo==='warning'?'Atención':'Error'),
      text: m.text
    });
  })();
  </script>
</body>
</html>
