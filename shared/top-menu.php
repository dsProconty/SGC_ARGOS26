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
        <li class="nav-item dropdown dropdown-notifications dropdown-menu-lg">
            <a href="javascript:void(0)" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="icon dripicons-bell"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="card card-notification">
                    <div class="card-header">
                        <h5 class="card-title">Notificaciones</h5>
                        <ul class="actions top-right">
                            <li>
                                <a href="javascript:void(0);" data-q-action="open-notifi-config">
                                    <i class="icon dripicons-gear"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="card-container-wrapper">
                            <div class="card-container">
                                <div class="timeline timeline-border">
                                    <div class="timeline-list timeline-border timeline-info">
                                        <div class="timeline-info">
                                            <div>User 1 ha realizado una gestión al usuario Perez José con el estado<br><a href="javascript:void(0)"><strong>Pendiente</strong></a> </div>
                                            <small class="text-muted">07/05/18, 2:00 PM</small>
                                        </div>
                                        <div class="timeline-info">
                                            <div>User 2 ha realizado una gestión al usuario Marquez Juán con el estado<br><a href="javascript:void(0)"><strong>Seguimiento</strong></a> </div>
                                            <small class="text-muted">07/05/18, 1:58 PM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-container">
                                <h6 class="p-0 m-0">
                                    Show notifications from:
                                </h6>
                                <div class="row m-b-20 m-t-30">
                                    <div class="col-10"><span class="title"><i class="icon dripicons-calendar"></i>Calendar</span></div>
                                    <div class="col-2"><input type="checkbox" class="js-switch" checked /></div>
                                </div>
                                <div class="row m-b-20">
                                    <div class="col-10"><span class="title"><i class="icon dripicons-mail"></i>Email</span></div>
                                    <div class="col-2"><input type="checkbox" class="js-switch" checked /></div>
                                </div>
                                <div class="row m-b-20">
                                    <div class="col-10"><span class="title"><i class="icon dripicons-message"></i>Messages</span></div>
                                    <div class="col-2"><input type="checkbox" class="js-switch" /></div>
                                </div>
                                <div class="row m-b-20">
                                    <div class="col-10"><span class="title"><i class="icon dripicons-stack"></i>Projects</span></div>
                                    <div class="col-2"><input type="checkbox" class="js-switch" checked /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link nav-pill user-avatar" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <img src="./assets/img/avatars/1.jpg" class="w-35 rounded-circle" alt="Albert Einstein">
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-accout">
                <div class="dropdown-header pb-3">
                    <div class="media d-user">
                        <img class="align-self-center mr-3 w-40 rounded-circle" src="./assets/img/avatars/1.jpg" alt="Albert Einstein">
                        <div class="media-body">
                            <h5 class="mt-0 mb-0"><?php echo $_SESSION['name_user'];?></h5>
                            <span><?php echo $_SESSION['permisos_acceso'];?></span>
                        </div>
                    </div>
                </div>
                <a class="dropdown-item" href="pages.profile.html"><i class="icon dripicons-user"></i> Perfil</a>
                <a class="dropdown-item" href="pages.my-account.html"><i class="icon dripicons-gear"></i> Ajustes de cuenta </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php"><i class="icon dripicons-lock-open"></i> Salir</a>
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