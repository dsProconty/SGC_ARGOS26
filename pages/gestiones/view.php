<?php
$cartera = $_GET['cartera'];
$title = '';
switch ($cartera) {
    case '30':
        $title = 'CARTERA 30 DÍAS';
        break;
    case '60':
        $title = 'CARTERA 60 DÍAS';
        break;
    case '90':
        $title = 'CARTERA 90 DÍAS';
        break;
    case '91':
        $title = 'CARTERA +90 DÍAS';
        break;
    default:
        # code...
        break;
}
?>
<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator"><?php echo strtoupper($_GET['module']).' '.$title; ?></h1>
                    <input type="hidden" name="cartera" id="cartera" value="<?php echo $cartera;?>">
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Gestiones</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!-- PAGE TABS -->
            <ul class="nav nav-tabs">
                <li class="nav-item" role="presentation">
                    <a href="#tab-1" class="nav-link active show" data-toggle="tab" aria-expanded="true" onclick="load_gestion('sin_gestion')">Sin Gestión</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-2" class="nav-link" data-toggle="tab" aria-expanded="true" onclick="load_gestion('pendiente')">Pendientes</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-5" class="nav-link" data-toggle="tab" aria-expanded="true" onclick="load_gestion('notificacion')">Notificación</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-3" class="nav-link" data-toggle="tab" aria-expanded="true" onclick="load_gestion('cobrada')">Cobrada</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-4" class="nav-link" data-toggle="tab" aria-expanded="true" onclick="load_gestion('compromiso')">Compromiso de Pago</a>
                </li>
            </ul>
        </div>
    </header>
    <section class="container m-t-30">
        <!-- ROW -->
        <div class="row">
            <!-- COLUMN -->
            <div class="col">
                <!-- TAB CONTENT -->
                <div class="tab-content">
                    <div class="tab-pane fadeIn active" id="tab-1">
                        <div class="card">
                            <h5 class="card-header">Sin Gestión</h5>
                            <div class="table-responsive">
                                <div class="card-body" id="loader_sin_gestion">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fadeIn" id="tab-2">
                        <div class="card">
                            <h5 class="card-header">Pendiente</h5>
                            <div class="table-responsive">
                                <div class="card-body" id="loader_pendiente">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fadeIn" id="tab-3">
                        <div class="card">
                            <h5 class="card-header">Cobrada</h5>
                            <div class="table-responsive">
                                <div class="card-body" id="loader_cobrada">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fadeIn" id="tab-4">
                        <div class="card">
                            <h5 class="card-header">Compromiso de Pago</h5>
                            <div class="table-responsive">
                                <div class="card-body" id="loader_compromiso">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fadeIn" id="tab-5">
                        <div class="card">
                            <h5 class="card-header">Notificación</h5>
                            <div class="table-responsive">
                                <div class="card-body" id="loader_notificacion">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
    include('modal/observacion.php')
?>
<script src="js/gestiones.js"></script>

