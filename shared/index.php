<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href="images/icon.png" type="image/x-icon">
    <title>SGC ARGOS | Ingreso</title>
    <!-- ================== GOOGLE FONTS ==================-->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500" rel="stylesheet">
    <!-- ======================= GLOBAL VENDOR STYLES ========================-->
    <link rel="stylesheet" href="./assets/css/vendor/bootstrap.css">
    <link rel="stylesheet" href="./assets/vendor/metismenu/dist/metisMenu.css">
    <link rel="stylesheet" href="./assets/vendor/switchery-npm/index.css">
    <link rel="stylesheet" href="./assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css">
    <!-- ======================= LINE AWESOME ICONS ===========================-->
    <link rel="stylesheet" href="./assets/css/icons/line-awesome.min.css">
    <!-- ======================= DRIP ICONS ===================================-->
    <link rel="stylesheet" href="./assets/css/icons/dripicons.min.css">
    <!-- ======================= MATERIAL DESIGN ICONIC FONTS =================-->
    <link rel="stylesheet" href="./assets/css/icons/material-design-iconic-font.min.css">
    <!-- ======================= GLOBAL COMMON STYLES ============================-->
    <link rel="stylesheet" href="./assets/css/common/main.bundle.css">
    <!-- ======================= LAYOUT TYPE ===========================-->
    <link rel="stylesheet" href="./assets/css/layouts/vertical/core/main.css">
    <!-- ======================= MENU TYPE ===========================================-->
    <link rel="stylesheet" href="./assets/css/layouts/vertical/menu-type/default.css">
    <!-- ======================= THEME COLOR STYLES ===========================-->
    <link rel="stylesheet" href="./assets/css/layouts/vertical/themes/theme-a.css">
</head>

<body>
    <div class="container">
        <?php

        if (empty($_GET['alert'])) {
            echo "";
        } elseif ($_GET['alert'] == 1) {
            echo "<div class='alert alert-danger alert-dismissable'>
           <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
           <h4>  <i class='icon fa fa-times-circle'></i> Error al entrar!</h4>
          Usuario/Contraseña incorrecta. Vuelva a ingresar su nombre de usuario y contraseña.
         </div>";
        } elseif ($_GET['alert'] == 2) {
            echo "<div class='alert alert-success alert-dismissable'>
           <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
           <h4>  <i class='icon fa fa-check-circle'></i> Exito!!</h4>
            Has salido con éxito.
            </div>";
        }
        ?>
        <form class="sign-in-form" action="login-check.php" method="POST">
            <div class="card">
                <div class="card-body">
                    <a href="index.html" class="brand text-center d-block m-b-20">
                        <img src="images/icon.png" alt="" width="150" height="150">
                    </a>
                    <h5 class="sign-in-heading text-center m-b-20">SGC ARGOS</h5>
                    <div class="form-group">
                        <label for="inputEmail" class="sr-only">Usuario:</label>
                        <input type="text" id="inputEmail" name= "username"class="form-control" placeholder="Usuario" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="inputPassword" class="sr-only">Contraseña:</label>
                        <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Contraseña" required >
                    </div>
                    <div class="checkbox m-b-10 m-t-20">
                        <div class="custom-control custom-checkbox checkbox-primary form-check">
                            <input type="checkbox" class="custom-control-input" id="stateCheck1" checked="">
                            <label class="custom-control-label" for="stateCheck1"> Recuerdame:</label>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-rounded btn-floating btn-lg btn-block" type="submit">Ingresar</button>
                </div>

            </div>
        </form>
    </div>

    <!-- ================== GLOBAL VENDOR SCRIPTS ==================-->
    <script src="./assets/vendor/modernizr/modernizr.custom.js"></script>
    <script src="./assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="./assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/vendor/js-storage/js.storage.js"></script>
    <script src="./assets/vendor/js-cookie/src/js.cookie.js"></script>
    <script src="./assets/vendor/pace/pace.js"></script>
    <script src="./assets/vendor/metismenu/dist/metisMenu.js"></script>
    <script src="./assets/vendor/switchery-npm/index.js"></script>
    <script src="./assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>
    <!-- ================== GLOBAL APP SCRIPTS ==================-->
    <script src="./assets/js/global/app.js"></script>

</body>

</html>