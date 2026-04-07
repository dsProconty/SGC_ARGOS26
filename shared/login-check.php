<?php

ob_start();
require_once "config/database.php";

$username = mysqli_real_escape_string($mysqli, stripslashes(strip_tags(htmlspecialchars(trim($_POST['username'])))));
$password = md5(mysqli_real_escape_string($mysqli, stripslashes(strip_tags(htmlspecialchars(trim($_POST['password']))))));

if (!ctype_alnum($username) OR !ctype_alnum($password)) {
	header("Location: index.php?alert=1");

	}
else {

	$query = mysqli_query($mysqli, "SELECT * FROM usuario WHERE username='$username' AND password='$password' AND status='activo'")
									or die('error'.mysqli_error($mysqli));
	$rows  = mysqli_num_rows($query);

	if ($rows > 0) {
		$data  = mysqli_fetch_assoc($query);

		session_start();
		$_SESSION['id_user']   = $data['id_user'];
		$_SESSION['username']  = $data['username'];
		$_SESSION['password']  = $data['password'];
		$_SESSION['name_user'] = $data['name_user'];
		$_SESSION['permisos_acceso'] = $data['permisos_acceso'];
		$_SESSION['loc_id']          = $data['loc_id'];
		$_SESSION['cli_id']          = $data['cli_id'];
		
		header("Location: services/load_data.php?action=carga_data");
	}
	else {
		echo "<script language=Javascript> location.href=\"index.php?alert=1\"; </script>"; 
		die(); 
	}
}
ob_end_flush();
