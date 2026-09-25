<?php

ob_start();
require_once "config/database.php";

$username     = mysqli_real_escape_string($mysqli, stripslashes(strip_tags(htmlspecialchars(trim($_POST['username'])))));
$passwordPlano = stripslashes(strip_tags(htmlspecialchars(trim($_POST['password']))));

if (!ctype_alnum($username)) {
	header("Location: index.php?alert=1");

	}
else {

	// Cuentas migradas del sistema viejo (Joomla) guardan su hash bcrypt
	// original ($2y$/$2a$/$2b$...) para que la persona pueda seguir
	// entrando con la misma contraseña de siempre — se valida con
	// password_verify() en vez de comparar MD5 en la consulta.
	$query = mysqli_query($mysqli, "SELECT * FROM usuario WHERE username='$username' AND status='activo'")
									or die('error'.mysqli_error($mysqli));
	$rows  = mysqli_num_rows($query);
	$data  = $rows > 0 ? mysqli_fetch_assoc($query) : null;

	$passwordValida = false;
	if ($data) {
		$esBcrypt = (strpos($data['password'], '$2y$') === 0 || strpos($data['password'], '$2a$') === 0 || strpos($data['password'], '$2b$') === 0);
		if ($esBcrypt) {
			$passwordValida = password_verify($passwordPlano, $data['password']);
		} else {
			$passwordValida = (md5($passwordPlano) === $data['password']);
		}
	}

	if ($passwordValida) {

		session_start();
		$_SESSION['id_user']         = $data['id_user'];
		$_SESSION['username']        = $data['username'];
		$_SESSION['password']        = $data['password'];
		$_SESSION['name_user']       = $data['name_user'];
		$_SESSION['permisos_acceso'] = $data['permisos_acceso'];
		$_SESSION['loc_id']          = $data['loc_id'];
		$_SESSION['cli_id']          = $data['cli_id'];
		$_SESSION['session_version'] = isset($data['session_version']) ? (int)$data['session_version'] : 0;
		
		header("Location: services/load_data.php?action=carga_data");
	}
	else {
		echo "<script language=Javascript> location.href=\"index.php?alert=1\"; </script>"; 
		die(); 
	}
}
ob_end_flush();
