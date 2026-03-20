<?php
require_once "config/database.php";

if (empty($_SESSION['username']) && empty($_SESSION['password'])){
	echo "<meta http-equiv='refresh' content='0; url=index.php?alert=1'>";
}
else {
	if ($_GET['module'] == 'dashboard') {
		include "pages/dashboard/view.php";
	}

	elseif ($_GET['module'] == 'clientes') {
		include "pages/clientes/view.php";
	}

	elseif ($_GET['module'] == 'usuarios') {
		include "pages/user/users.php";
	}

	elseif ($_GET['module'] == 'contrasena') {
		include "pages/password/password.php";
	}

	elseif ($_GET['module'] == 'formulario') {
		include "pages/user/form_user.php";
	}

	elseif ($_GET['module'] == 'gestiones') {
		include "pages/gestiones/view.php";
	}

	elseif ($_GET['module'] == 'nueva_gestion') {
		include "pages/gestiones/form.php";
	}

	elseif ($_GET['module'] == 'reportes') {
		include "pages/reportes/view.php";
	}

	elseif ($_GET['module'] == 'excel') {
		include "pages/reportes/excel.php";
	}

	elseif ($_GET['module'] == 'pos') {
		include "pages/pos/view.php";
	}

	elseif ($_GET['module'] == 'pos_historial') {
		include "pages/pos/historial.php";
	}

	elseif ($_GET['module'] == 'convenios') {
		include "pages/convenio/view.php";
	}

}
?>