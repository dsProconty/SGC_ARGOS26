<?php
ob_start();
session_start();
require_once "../../config/database.php";
require_once "../../helpers/db_compat.php";
require_once "../../helpers/auth_helpers.php";
require_once "../../helpers/session_helpers.php";

if (empty($_SESSION['username'])) {
    header("location: ../../index.php?alert=1");
    exit;
}

if (isset($_POST['Guardar'])) {

    // La clave nueva se guarda como MD5 de lo que se escribió (trim), sin escapes: así el login la reconoce siempre
    // (ver helpers/auth_helpers.php). Antes se guardaba el MD5 de la versión escapada, con barras agregadas.
    // La clave nueva se guarda con bcrypt (ver helpers/auth_helpers.php). La comparación de "repetir clave" se hace sobre el texto.
    $new_plano    = trim(isset($_POST['new_pass']) ? $_POST['new_pass'] : '');
    $retype_plano = trim(isset($_POST['retype_pass']) ? $_POST['retype_pass'] : '');
    $new_pass     = mysqli_real_escape_string($mysqli, hashClave($new_plano));
    $rol         = $_SESSION['permisos_acceso'] ?? '';
    $es_admin    = in_array($rol, ['Super Admin', 'Supervisor']);

    // Validar que las nuevas contraseñas coincidan
    if ($new_plano === '' || $new_plano !== $retype_plano) {
        header("Location: ../../main.php?module=contrasena&alert=2");
        exit;
    }

    if ($es_admin && !empty($_POST['target_user_id'])) {
        // ── Admin: resetear contraseña de otro usuario directamente ──
        $target_id = (int)$_POST['target_user_id'];
        // Un Supervisor puede resetear claves del personal, pero no la de un Super Admin (sería tomar su cuenta).
        if (!esSuperAdmin($mysqli)) {
            $rObj = mysqli_query($mysqli, "SELECT p.per_nombre FROM usuario u LEFT JOIN perfil p ON p.per_id = u.per_id WHERE u.id_user = $target_id");
            $fObj = $rObj ? mysqli_fetch_assoc($rObj) : null;
            if (!$fObj || strtolower((string)$fObj['per_nombre']) === 'super admin') {
                header("location: ../../main.php?module=contrasena&alert=1");
                exit;
            }
        }
        $query = mysqli_query($mysqli, "UPDATE usuario SET password = '$new_pass' WHERE id_user = '$target_id'");
        if ($query) {
            header("location: ../../main.php?module=contrasena&alert=3");
            exit;
        } else {
            header("location: ../../main.php?module=contrasena&alert=1");
            exit;
        }
    } else {
        // ── Usuario normal: verificar contraseña antigua ──
        $id_user  = (int)$_SESSION['id_user'];

        $sql  = mysqli_query($mysqli, "SELECT password FROM usuario WHERE id_user = $id_user");
        $data = mysqli_fetch_assoc($sql);

        // Mismas formas de clave y mismo criterio que el login (bcrypt de las cuentas migradas o MD5).
        $oldValida = $data ? claveCoincide($data['password'], clavesCandidatas($mysqli, isset($_POST['old_pass']) ? $_POST['old_pass'] : '')) : false;

        if (!$oldValida) {
            header("Location: ../../main.php?module=contrasena&alert=1");
            exit;
        }

        $query = mysqli_query($mysqli, "UPDATE usuario SET password = '$new_pass' WHERE id_user = '$id_user'");
        if ($query) {
            header("location: ../../main.php?module=contrasena&alert=3");
            exit;
        } else {
            header("location: ../../main.php?module=contrasena&alert=1");
            exit;
        }
    }
}
