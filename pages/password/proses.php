<?php
ob_start();
session_start();
require_once "../../config/database.php";

if (empty($_SESSION['username'])) {
    header("location: ../../index.php?alert=1");
    exit;
}

if (isset($_POST['Guardar'])) {

    $new_pass    = md5(mysqli_real_escape_string($mysqli, trim($_POST['new_pass'])));
    $retype_pass = md5(mysqli_real_escape_string($mysqli, trim($_POST['retype_pass'])));
    $rol         = $_SESSION['permisos_acceso'] ?? '';
    $es_admin    = in_array($rol, ['Super Admin', 'Supervisor']);

    // Validar que las nuevas contraseñas coincidan
    if ($new_pass !== $retype_pass) {
        header("Location: ../../main.php?module=contrasena&alert=2");
        exit;
    }

    if ($es_admin && !empty($_POST['target_user_id'])) {
        // ── Admin: resetear contraseña de otro usuario directamente ──
        $target_id = (int)$_POST['target_user_id'];
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
        $old_pass = md5(mysqli_real_escape_string($mysqli, trim($_POST['old_pass'] ?? '')));
        $id_user  = (int)$_SESSION['id_user'];

        $sql  = mysqli_query($mysqli, "SELECT password FROM usuario WHERE id_user = $id_user");
        $data = mysqli_fetch_assoc($sql);

        if ($old_pass !== $data['password']) {
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
