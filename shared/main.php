<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="shortcut icon" href="images/icon.png" type="image/x-icon">
	<title>SGC ARGOS</title>
	<!-- ================== GOOGLE FONTS ==================-->
	<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500" rel="stylesheet">
	<!-- ======================= GLOBAL VENDOR STYLES ========================-->
	<link rel="stylesheet" href="./assets/css/vendor/bootstrap.css">
	<link rel="stylesheet" href="./assets/vendor/metismenu/dist/metisMenu.css">
	<link rel="stylesheet" href="./assets/vendor/switchery-npm/index.css">
	<link rel="stylesheet" href="./assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css">
	<!-- ======================= LINE AWESOME ICONS ===========================-->
	<link rel="stylesheet" href="./assets/css/icons/line-awesome.min.css">
	<link rel="stylesheet" href="./assets/css/icons/simple-line-icons.css">
	<!-- ======================= DRIP ICONS ===================================-->
	<link rel="stylesheet" href="./assets/css/icons/dripicons.min.css">
	<!-- ======================= MATERIAL DESIGN ICONIC FONTS =================-->
	<link rel="stylesheet" href="./assets/css/icons/material-design-iconic-font.min.css">
	<!-- ======================= PAGE VENDOR STYLES ===========================-->
	<link rel="stylesheet" href="./assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.css">
	<!-- ======================= GLOBAL COMMON STYLES ============================-->
	<link rel="stylesheet" href="./assets/css/common/main.bundle.css">
	<!-- ======================= LAYOUT TYPE ===========================-->
	<link rel="stylesheet" href="./assets/css/layouts/vertical/core/main.css">
	<!-- ======================= MENU TYPE ===========================-->
	<link rel="stylesheet" href="./assets/css/layouts/vertical/menu-type/default.css">
	<!-- ======================= THEME COLOR STYLES ===========================-->
	<link rel="stylesheet" href="./assets/css/layouts/vertical/themes/theme-a.css">

	<script src="./assets/vendor/jquery/dist/jquery.min.js"></script>

</head>

<body>
	<!-- START APP WRAPPER -->
	<div id="app">
		<?php include "./shared/sidebar.php" ?>
		<div class="content-wrapper">
			<?php include "./shared/top-menu.php" ?>
			<div class="content">
				<?php include "./content.php"; ?>
			</div>
		</div>
	</div>
	<!-- END CONTENT WRAPPER -->
	<!-- ================== GLOBAL VENDOR SCRIPTS ==================-->
	<script src="./assets/vendor/modernizr/modernizr.custom.js"></script>
	
	<script src="./assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<script src="./assets/vendor/js-storage/js.storage.js"></script>
	<script src="./assets/vendor/js-cookie/src/js.cookie.js"></script>
	<script src="./assets/vendor/pace/pace.js"></script>
	<script src="./assets/vendor/metismenu/dist/metisMenu.js"></script>
	<script src="./assets/vendor/switchery-npm/index.js"></script>
	<script src="./assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>

	<!-- ================== GLOBAL APP SCRIPTS ==================-->
	<script src="./assets/js/global/app.js"></script>

	<script src="./assets/vendor/jvectormap-next/jquery-jvectormap.min.js"></script>
	<script src="./assets/vendor/jvectormap-next/jquery-jvectormap-world-mill.js"></script>
	<!-- ================== PAGE LEVEL VENDOR SCRIPTS ==================-->
	<script src="./assets/vendor/datatables.net/js/jquery.dataTables.js"></script>
	<script src="./assets/vendor/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>
	<script src="./assets/vendor/select2/select2.min.js"></script>
	
	<script src="./assets/vendor/chartist/dist/chartist.js"></script>

</body>

</html>