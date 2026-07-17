<?php
require_once "config/database.php";

if (empty($_SESSION['username']) && empty($_SESSION['password'])) {
    echo "<meta http-equiv='refresh' content='0; url=index.php?alert=1'>";
} else {

    $module = $_GET['module'] ?? 'dashboard';

    // ── Validación de acceso por perfil ──────────────────────────────────────
    // Sub-páginas mapeadas al módulo padre (null = siempre permitido)
    $modulo_padre = [
        'contrasena'    => null,
        'formulario'    => 'usuarios',
        'nueva_gestion' => 'gestiones',
        'excel'         => 'reportes',
        'pos_historial' => 'pos',
    ];

    $modulo_check = array_key_exists($module, $modulo_padre)
        ? $modulo_padre[$module]
        : $module;

    if ($modulo_check !== null) {
        $uid  = (int)$_SESSION['id_user'];
        $stmt = $mysqli->prepare(
            "SELECT pm_modulo FROM perfil_modulo pm
             JOIN usuario u ON u.per_id = pm.per_id
             WHERE u.id_user = ?"
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res     = $stmt->get_result();
        $allowed = [];
        while ($row = $res->fetch_assoc()) {
            $allowed[] = $row['pm_modulo'];
        }

        if (!in_array($modulo_check, $allowed)) {
            echo "<meta http-equiv='refresh' content='0; url=?module=dashboard'>";
            exit;
        }
    }

    // ── Routing ──────────────────────────────────────────────────────────────
    if ($module === 'dashboard') {
        include "pages/dashboard/view.php";
    } elseif ($module === 'clientes') {
        include "pages/clientes/view.php";
    } elseif ($module === 'usuarios') {
        include "pages/user/users.php";
    } elseif ($module === 'contrasena') {
        include "pages/password/password.php";
    } elseif ($module === 'formulario') {
        include "pages/user/form_user.php";
    } elseif ($module === 'gestiones') {
        include "pages/gestiones/view.php";
    } elseif ($module === 'nueva_gestion') {
        include "pages/gestiones/form.php";
    } elseif ($module === 'reportes') {
        include "pages/reportes/view.php";
    } elseif ($module === 'excel') {
        include "pages/reportes/excel.php";
    } elseif ($module === 'pos') {
        include "pages/pos/view.php";
    } elseif ($module === 'pos_historial') {
        include "pages/pos/historial.php";
    } elseif ($module === 'giftcard') {
        include "pages/giftcard/view.php";
    } elseif ($module === 'venta_diferida') {
        include "pages/venta_diferida/view.php";
    } elseif ($module === 'estado_cuenta') {
        include "pages/estado_cuenta/view.php";
    } elseif ($module === 'portal_empresa') {
        include "pages/portal_empresa/view.php";
    } elseif ($module === 'configuracion') {
        include "pages/configuracion/view.php";
    } elseif ($module === 'locales') {
        include "pages/locales/view.php";
    } elseif ($module === 'perfiles') {
        include "pages/perfiles/view.php";
    }

}
?>