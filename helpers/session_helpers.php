<?php
// Helpers de permisos: aceptan tanto el rol legacy (usuario.permisos_acceso)
// como el perfil nuevo asignado vía el módulo Perfiles y Permisos
// (usuario.per_id -> perfil.per_nombre). Sin esto, un usuario con un
// perfil personalizado (cuyo permisos_acceso legacy no quedó sincronizado
// con el nombre exacto del perfil) queda bloqueado de páginas enteras.
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (!function_exists('tienePerfil')) {
    function tienePerfil($mysqli, $nombre) {
        $nombre = strtolower($nombre);
        if (strtolower($_SESSION['permisos_acceso'] ?? '') === $nombre) {
            return true;
        }
        if (empty($_SESSION['id_user'])) {
            return false;
        }
        $stmt = $mysqli->prepare('SELECT p.per_nombre FROM usuario u JOIN perfil p ON u.per_id = p.per_id WHERE u.id_user = ?');
        $stmt->bind_param('i', $_SESSION['id_user']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && strtolower($row['per_nombre']) === $nombre;
    }
}

if (!function_exists('esSuperAdmin')) {
    function esSuperAdmin($mysqli) {
        return tienePerfil($mysqli, 'Super Admin');
    }
}

// US-B: permiso granular (acción específica dentro de un módulo, ej.
// 'pos.anular'), independiente de perfil_modulo (que solo da acceso al
// módulo completo). Super Admin siempre pasa, igual que con módulos.
if (!function_exists('tienePermiso')) {
    function tienePermiso($mysqli, $clave) {
        if (esSuperAdmin($mysqli)) {
            return true;
        }
        if (empty($_SESSION['id_user'])) {
            return false;
        }
        $stmt = $mysqli->prepare(
            'SELECT pp.pp_id FROM perfil_permiso pp
             JOIN usuario u ON u.per_id = pp.per_id
             WHERE u.id_user = ? AND pp.pp_permiso = ? LIMIT 1'
        );
        if (!$stmt) {
            // Tabla perfil_permiso no existe aún — ejecutar migrations/bloque11_permisos_granulares.sql
            return false;
        }
        $stmt->bind_param('is', $_SESSION['id_user'], $clave);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (bool)$row;
    }
}

// ¿El usuario en sesión tiene habilitado este módulo (perfil_modulo)? Misma regla que content.php:
// Super Admin siempre; si no, por su per_id, y como respaldo por el nombre del rol legacy.
if (!function_exists('tieneModulo')) {
    function tieneModulo($mysqli, $modulo) {
        if (esSuperAdmin($mysqli)) {
            return true;
        }
        if (empty($_SESSION['id_user'])) {
            return false;
        }
        $uid = (int)$_SESSION['id_user'];
        $mod = mysqli_real_escape_string($mysqli, $modulo);
        $r = mysqli_query($mysqli, "SELECT 1 FROM perfil_modulo pm JOIN usuario u ON u.per_id = pm.per_id
                                    WHERE u.id_user = $uid AND pm.pm_modulo = '$mod' LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) {
            return true;
        }
        $rol = mysqli_real_escape_string($mysqli, $_SESSION['permisos_acceso'] ?? '');
        $r2 = mysqli_query($mysqli, "SELECT 1 FROM perfil_modulo pm JOIN perfil p ON p.per_id = pm.per_id
                                     WHERE LOWER(p.per_nombre) = LOWER('$rol') AND pm.pm_modulo = '$mod' LIMIT 1");
        return (bool)($r2 && mysqli_num_rows($r2) > 0);
    }
}

// Puerta de entrada de los endpoints ajax: exige sesión iniciada y al menos uno de los módulos indicados.
// Antes varios archivos (users, dashboard, gestiones, reportes) respondían sin siquiera sesión, y otros solo
// verificaban que hubiera sesión (cualquier cajero o empresa cliente llegaba a Clientes, Usuarios, etc.).
// Responde JSON con 200 como el resto de los ajax y corta la ejecución.
if (!function_exists('exigirAccesoAjax')) {
    function exigirAccesoAjax($mysqli, $modulos) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['id_user'])) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'mensaje' => 'Sesión no válida'));
            exit;
        }
        $lista = is_array($modulos) ? $modulos : array($modulos);
        foreach ($lista as $m) {
            if (tieneModulo($mysqli, $m)) {
                return;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'mensaje' => 'Sin permisos'));
        exit;
    }
}
