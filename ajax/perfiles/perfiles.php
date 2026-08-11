<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/session_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || !esSuperAdmin($mysqli)) {
    echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Módulos del sistema disponibles para asignar
define('MODULOS_SISTEMA', [
    'dashboard'      => 'Dashboard',
    'gestiones'      => 'Gestiones',
    'reportes'       => 'Reportes',
    'pos'            => 'Punto de Venta',
    'convenios'      => 'Convenios',
    'giftcard'       => 'Gift Cards',
    'venta_diferida' => 'Ventas Diferidas',
    'estado_cuenta'  => 'Estados de Cuenta',
    'portal_empresa' => 'Portal Empresa / Nómina',
    'usuarios'       => 'Gestión de Usuarios',
    'configuracion'  => 'Configuración',
    'locales'        => 'Locales Comerciales',
    'clientes'       => 'Clientes',
    'perfiles'       => 'Perfiles y Permisos',
]);

// US-B: permisos granulares (acciones específicas dentro de un módulo)
define('PERMISOS_GRANULARES', [
    'pos.anular' => 'Anular ventas del mismo día (Punto de Venta)',
]);

switch ($action) {

    // ══ LIST — tabla de perfiles ══════════════════════════════
    case 'list':
        $q = "SELECT p.per_id, p.per_nombre, p.per_descripcion, p.per_es_sistema, p.per_activo,
                     COUNT(DISTINCT pm.pm_id) AS total_modulos,
                     COUNT(DISTINCT u.id_user) AS total_usuarios
              FROM perfil p
              LEFT JOIN perfil_modulo pm ON p.per_id = pm.per_id
              LEFT JOIN usuario u ON u.per_id = p.per_id
              GROUP BY p.per_id ORDER BY p.per_id ASC";
        $r = mysqli_query($mysqli, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ══ GET — datos de un perfil para editar ════════════════
    case 'get':
        $per_id = (int)($_GET['per_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM perfil WHERE per_id = ?");
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $perfil = $stmt->get_result()->fetch_assoc();
        if (!$perfil) { echo json_encode(['success' => false, 'mensaje' => 'Perfil no encontrado']); break; }

        $stmt2 = $mysqli->prepare("SELECT pm_modulo FROM perfil_modulo WHERE per_id = ?");
        $stmt2->bind_param('i', $per_id);
        $stmt2->execute();
        $modulos = [];
        $res = $stmt2->get_result();
        while ($m = $res->fetch_assoc()) $modulos[] = $m['pm_modulo'];

        $permisos = [];
        $stmt3 = $mysqli->prepare("SELECT pp_permiso FROM perfil_permiso WHERE per_id = ?");
        if ($stmt3) {
            $stmt3->bind_param('i', $per_id);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            while ($pp = $res3->fetch_assoc()) $permisos[] = $pp['pp_permiso'];
        }

        echo json_encode(['success' => true, 'perfil' => $perfil, 'modulos' => $modulos, 'permisos' => $permisos]);
        break;

    // ══ CREAR ════════════════════════════════════════════════
    case 'crear':
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $modulos     = $_POST['modulos'] ?? [];
        $permisos    = $_POST['permisos'] ?? [];

        if (!$nombre) { echo json_encode(['success' => false, 'mensaje' => 'El nombre es requerido']); break; }

        $stmt = $mysqli->prepare("INSERT INTO perfil (per_nombre, per_descripcion) VALUES (?, ?)");
        $stmt->bind_param('ss', $nombre, $descripcion);
        if (!$stmt->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al crear perfil']); break; }
        $per_id = $mysqli->insert_id;

        $ins = $mysqli->prepare("INSERT INTO perfil_modulo (per_id, pm_modulo) VALUES (?, ?)");
        foreach ($modulos as $mod) {
            $mod = trim($mod);
            if (array_key_exists($mod, MODULOS_SISTEMA)) {
                $ins->bind_param('is', $per_id, $mod);
                $ins->execute();
            }
        }

        $insPP = $mysqli->prepare("INSERT INTO perfil_permiso (per_id, pp_permiso) VALUES (?, ?)");
        if ($insPP) {
            foreach ($permisos as $pp) {
                $pp = trim($pp);
                if (array_key_exists($pp, PERMISOS_GRANULARES)) {
                    $insPP->bind_param('is', $per_id, $pp);
                    $insPP->execute();
                }
            }
        }
        // contrasena siempre incluida (no está en checkboxes pero se agrega)
        echo json_encode(['success' => true, 'mensaje' => 'Perfil creado correctamente', 'per_id' => $per_id]);
        break;

    // ══ EDITAR ═══════════════════════════════════════════════
    case 'editar':
        $per_id      = (int)($_POST['per_id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $modulos     = $_POST['modulos'] ?? [];
        $permisos    = $_POST['permisos'] ?? [];

        if (!$nombre || !$per_id) { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }

        $stmt = $mysqli->prepare("UPDATE perfil SET per_nombre=?, per_descripcion=? WHERE per_id=?");
        $stmt->bind_param('ssi', $nombre, $descripcion, $per_id);
        if (!$stmt->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar']); break; }

        // Reemplazar módulos
        $del = $mysqli->prepare("DELETE FROM perfil_modulo WHERE per_id=?");
        $del->bind_param('i', $per_id);
        $del->execute();

        $ins = $mysqli->prepare("INSERT INTO perfil_modulo (per_id, pm_modulo) VALUES (?, ?)");
        foreach ($modulos as $mod) {
            $mod = trim($mod);
            if (array_key_exists($mod, MODULOS_SISTEMA)) {
                $ins->bind_param('is', $per_id, $mod);
                $ins->execute();
            }
        }

        // Reemplazar permisos granulares
        $delPP = $mysqli->prepare("DELETE FROM perfil_permiso WHERE per_id=?");
        if ($delPP) {
            $delPP->bind_param('i', $per_id);
            $delPP->execute();

            $insPP = $mysqli->prepare("INSERT INTO perfil_permiso (per_id, pp_permiso) VALUES (?, ?)");
            foreach ($permisos as $pp) {
                $pp = trim($pp);
                if (array_key_exists($pp, PERMISOS_GRANULARES)) {
                    $insPP->bind_param('is', $per_id, $pp);
                    $insPP->execute();
                }
            }
        }
        echo json_encode(['success' => true, 'mensaje' => 'Perfil actualizado correctamente']);
        break;

    // ══ TOGGLE ACTIVO ════════════════════════════════════════
    case 'toggle_activo':
        $per_id = (int)($_POST['per_id'] ?? 0);
        $stmt   = $mysqli->prepare("SELECT per_activo, per_es_sistema FROM perfil WHERE per_id=?");
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        if (!$p) { echo json_encode(['success' => false, 'mensaje' => 'Perfil no encontrado']); break; }

        $nuevo = $p['per_activo'] ? 0 : 1;
        $upd   = $mysqli->prepare("UPDATE perfil SET per_activo=? WHERE per_id=?");
        $upd->bind_param('ii', $nuevo, $per_id);
        $upd->execute();
        echo json_encode(['success' => true, 'activo' => $nuevo, 'mensaje' => $nuevo ? 'Perfil activado' : 'Perfil desactivado']);
        break;

    // ══ ELIMINAR ═════════════════════════════════════════════
    case 'eliminar':
        $per_id = (int)($_POST['per_id'] ?? 0);

        // No eliminar perfiles de sistema
        $chk = $mysqli->prepare("SELECT per_es_sistema FROM perfil WHERE per_id=?");
        $chk->bind_param('i', $per_id);
        $chk->execute();
        $p = $chk->get_result()->fetch_assoc();
        if (!$p) { echo json_encode(['success' => false, 'mensaje' => 'Perfil no encontrado']); break; }
        if ($p['per_es_sistema']) { echo json_encode(['success' => false, 'mensaje' => 'Los perfiles de sistema no se pueden eliminar']); break; }

        // No eliminar si tiene usuarios
        $cu = $mysqli->prepare("SELECT COUNT(*) as c FROM usuario WHERE per_id=?");
        $cu->bind_param('i', $per_id);
        $cu->execute();
        $cnt = $cu->get_result()->fetch_assoc()['c'];
        if ($cnt > 0) { echo json_encode(['success' => false, 'mensaje' => "No se puede eliminar: tiene $cnt usuario(s) asignado(s)"]); break; }

        $d1 = $mysqli->prepare("DELETE FROM perfil_modulo WHERE per_id=?");
        $d1->bind_param('i', $per_id); $d1->execute();
        $d3 = $mysqli->prepare("DELETE FROM perfil_permiso WHERE per_id=?");
        if ($d3) { $d3->bind_param('i', $per_id); $d3->execute(); }
        $d2 = $mysqli->prepare("DELETE FROM perfil WHERE per_id=?");
        $d2->bind_param('i', $per_id); $d2->execute();

        echo json_encode(['success' => true, 'mensaje' => 'Perfil eliminado']);
        break;

    // ══ LIST SELECT — para selector en form de usuario ═══════
    case 'list_select':
        $q = "SELECT per_id, per_nombre FROM perfil WHERE per_activo=1 ORDER BY per_nombre ASC";
        $r = mysqli_query($mysqli, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ══ GET MODULOS — módulos disponibles ════════════════════
    case 'get_modulos_sistema':
        echo json_encode(['success' => true, 'data' => MODULOS_SISTEMA]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
