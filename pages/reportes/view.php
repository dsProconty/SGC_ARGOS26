<?php
$tipo = $_GET['tipo'];
?>
<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator"><?php echo strtoupper($tipo); ?></h1>
                    <input type="hidden" name="cartera" id="cartera" value="<?php echo $tipo; ?>">
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Reportes</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <section class="container m-t-30">
        <!-- ROW -->
        <div class="row">
            <!-- COLUMN -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php
                        switch ($tipo) {
                            case 'ventas por locales':
                        ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control">
                                                        <option value="0">Seleccione una marca</option>
                                                        <option value="PIZZA HUT">PIZZA HUT</option>
                                                        <option value="FRIDAYS">FRIDAYS</option>
                                                        <option value="OTROS">OTROS</option>
                                                        <option value="TODOS">TODOS</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'cobranzas anteriores':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'total cobranza':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'detalle cobranza':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'dinero por edades de cartera':
                            ?>

                            <?php
                                break;
                            case 'cartera recuperada':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'cliente consumos':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'cliente - consumos':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'cobranza por gestor':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                break;
                            case 'consumos del mes':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <div class="row">
                                                <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control">
                                                        <option value="0">Seleccione una marca</option>
                                                        <option value="PIZZA HUT">PIZZA HUT</option>
                                                        <option value="FRIDAYS">FRIDAYS</option>
                                                        <option value="OTROS">OTROS</option>
                                                        <option value="TODOS">TODOS</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info"><i class="icon dripicons-cloud-download" style="color:white"></i>Descargar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                        <?php
                                break;
                            default:
                                # code...
                                break;
                        }
                        ?>
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