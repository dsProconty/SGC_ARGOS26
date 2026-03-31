<!-- START LOGO WRAPPER -->
<nav class="top-toolbar navbar navbar-mobile navbar-tablet">
    <ul class="navbar-nav nav-left">
        <li class="nav-item">
            <a href="javascript:void(0)" data-toggle-state="aside-left-open">
                <i class="icon dripicons-align-left"></i>
            </a>
        </li>
    </ul>
    <ul class="navbar-nav nav-center site-logo">
        <li>
            <a href="?module=dashboard">
                <div class="logo_mobile">
                    <img src="images/icon.png" alt="" width="25" height="25">
                </div>
                <span class="brand-text">SGC ARGOS</span>
            </a>
        </li>
    </ul>
    <ul class="navbar-nav nav-right">
        <li class="nav-item">
            <a href="javascript:void(0)" data-toggle-state="mobile-topbar-toggle">
                <i class="icon dripicons-dots-3 rotate-90"></i>
            </a>
        </li>
    </ul>
</nav>
<!-- END LOGO WRAPPER -->
<!-- START TOP TOOLBAR WRAPPER -->
<nav class="top-toolbar navbar navbar-desktop flex-nowrap">
    <div class="container">
        SISTEMA DE GESTIÓN DE COBRANZAS ARGOS
    </div>
    <!-- START RIGHT TOOLBAR ICON MENUS -->
    <ul class="navbar-nav nav-right">
        <li class="nav-item dropdown">
            <a class="nav-link nav-pill user-avatar" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <img src="./assets/img/avatars/1.jpg" class="w-35 rounded-circle" alt="Usuario">
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-accout">
                <div class="dropdown-header pb-3">
                    <div class="media d-user">
                        <img class="align-self-center mr-3 w-40 rounded-circle" src="./assets/img/avatars/1.jpg" alt="Usuario">
                        <div class="media-body">
                            <h5 class="mt-0 mb-0"><?php echo $_SESSION['name_user'];?></h5>
                            <span><?php echo $_SESSION['permisos_acceso'];?></span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php"><i class="icon dripicons-lock-open"></i> Cerrar Sesión</a>
            </div>
        </li>
    </ul>
    <!-- END RIGHT TOOLBAR ICON MENUS -->
    <!--START TOP TOOLBAR SEARCH -->
    <form role="search" action="pages.search.html" class="navbar-form">
        <div class="form-group">
            <input type="text" placeholder="Search and press enter..." class="form-control navbar-search" autocomplete="off">
            <i data-q-action="close-site-search" class="icon dripicons-cross close-search"></i>
        </div>
        <button type="submit" class="d-none">Submit</button>
    </form>
    <!--END TOP TOOLBAR SEARCH -->
</nav>
<!-- END TOP TOOLBAR WRAPPER -->
