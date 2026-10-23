<?php

ob_start();
require_once "config/database.php";
require_once "helpers/db_compat.php";
require_once "helpers/auth_helpers.php";

$username     = mysqli_real_escape_string($mysqli, stripslashes(strip_tags(htmlspecialchars(trim(isset($_POST['username']) ? $_POST['username'] : '')))));
// Formas válidas de la clave escrita (ver helpers/auth_helpers.php): la clave se compara tal cual se escribió y,
// por compatibilidad, también con la transformación antigua; ya no se altera antes de comparar.
$clavesCandidatas = clavesCandidatas($mysqli, isset($_POST['password']) ? $_POST['password'] : '');

// Se aceptan letras (con ñ y tildes), números, guion, guion bajo y punto. Antes solo ctype_alnum(): 20 cuentas
// migradas del sistema viejo (ej. "Mcedeño", "j___sod") quedaron imposibilitadas de entrar. El valor ya viaja
// escapado con mysqli_real_escape_string, así que esto no abre la puerta a inyección.
if ($username === '' || !preg_match('/^[\p{L}\p{N}_.\-]+$/u', $username)) {
	header("Location: index.php?alert=1");

	}
else {

	// Cuentas migradas del sistema viejo (Joomla) guardan su hash bcrypt
	// original ($2y$/$2a$/$2b$...) para que la persona pueda seguir
	// entrando con la misma contraseña de siempre — se valida con
	// password_verify() en vez de comparar MD5 en la consulta.
	$query = mysqli_query($mysqli, "SELECT * FROM usuario WHERE TRIM(username)='$username' AND status='activo'")
									or die('error'.mysqli_error($mysqli));
	$rows  = mysqli_num_rows($query);
	$data  = $rows > 0 ? mysqli_fetch_assoc($query) : null;

	$passwordValida = false;
	if ($data) {
		$passwordValida = claveCoincide($data['password'], $clavesCandidatas);
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
		
		// Destino según el rol. (Antes pasaba por services/load_data.php?action=carga_data, que consultaba el
		// webservice del sistema viejo ibrisystemas.com — ya no existe — y recién después redirigía.)
		$rol = strtolower($data['permisos_acceso'] ?? '');
		if ($rol === 'operador') {
			header("Location: main.php?module=gestiones&cartera=30");
		} elseif ($rol === 'cajero') {
			header("Location: main.php?module=pos");
		} elseif ($rol === 'empresa_cliente') {
			header("Location: main.php?module=portal_empresa");
		} else {
			header("Location: main.php?module=dashboard");
		}
	}
	else {
		echo "<script language=Javascript> location.href=\"index.php?alert=1\"; </script>"; 
		die(); 
	}
}
ob_end_flush();
