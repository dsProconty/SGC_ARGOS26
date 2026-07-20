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
