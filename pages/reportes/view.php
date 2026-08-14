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
                                                        <option value="TODOS">— Todas las marcas —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo htmlspecialchars($m['mar_descripcion']); ?>">
                                                                <?php echo htmlspecialchars($m['mar_descripcion']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
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
                                <div class="row">
                                    <div class="col-sm-10 offset-sm-1">
                                        <div class="alert alert-info">
                                            <span class="icon"><i class="dripicons-information"></i></span>
                                            <span class="text">Este reporte muestra la deuda agrupada por cliente según la edad de la cartera (30, 60, 90 y más de 90 días).
                                            Solo incluye carteras con estado <strong>pendiente</strong>, <strong>notificación</strong> o <strong>compromiso</strong>.</span>
                                        </div>
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <br>
                                                    <button type="submit" class="btn btn-info">
                                                        <i class="icon dripicons-cloud-download" style="color:white"></i> Descargar
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
                            case 'transacciones por local':
                            ?>
                                <div class="row">
                                    <div class="col-sm-10 offset-sm-1">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-3">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="">Provincia</label>
                                                    <select name="provincia" id="provincia" class="form-control">
                                                        <option value="">— Todas —</option>
                                                        <?php
                                                        $resProv = mysqli_query($mysqli, "SELECT DISTINCT loc_provincia FROM local WHERE loc_provincia IS NOT NULL AND loc_provincia != '' ORDER BY loc_provincia ASC");
                                                        while ($p = mysqli_fetch_assoc($resProv)):
                                                        ?>
                                                            <option value="<?php echo htmlspecialchars($p['loc_provincia']); ?>"><?php echo htmlspecialchars(ucfirst($p['loc_provincia'])); ?></option>
                                                        <?php endwhile; ?>
                                                        <option value="SIN_ESPECIFICAR">Sin especificar</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="">Local</label>
                                                    <select name="local" id="local" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resLoc = mysqli_query($mysqli, "SELECT loc_id, COALESCE(loc_nombre, loc_direccion) AS loc_label FROM local WHERE loc_activo = 1 ORDER BY loc_label ASC");
                                                        while ($l = mysqli_fetch_assoc($resLoc)):
                                                        ?>
                                                            <option value="<?php echo (int)$l['loc_id']; ?>"><?php echo htmlspecialchars($l['loc_label']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
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
                            case 'total ventas':
                            ?>
                                <div class="row">
                                    <div class="col-sm-6 offset-sm-3">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
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
                            case 'registro cobranza':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
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
                            case 'ventas locales':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
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
                            case 'cobranza pendiente por empresa':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="">Fecha Corte</label>
                                                    <input type="date" name="fecha_corte" id="fecha_corte" class="form-control" required>
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
                            case 'cobranza pendiente por mes':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label for="">Fecha Corte</label>
                                                    <input type="date" name="fecha_corte" id="fecha_corte" class="form-control" required>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
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
                            case 'pendiente empresas':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todos —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="">Año</label>
                                                    <select name="anio" id="anio" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php for ($y = (int)date('Y'); $y >= 2018; $y--): ?>
                                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                        <?php endfor; ?>
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
                            case 'ranking de locales':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
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
                            case 'detalle de tarjetas':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <p>Listado de todos los clientes con sus datos de contacto y condiciones de convenio.</p>
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
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
                            case 'detalle de tarjetas por cliente':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
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
                            case 'giftpoint':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <p>Listado completo de códigos de gift card con su cupo y estado.</p>
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
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
                            case 'giftpoint transacciones':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <label for="">Código de Gift Card</label>
                                                    <input type="text" name="codigo" id="codigo" class="form-control" required>
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
                            case 'reporte gifcards':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Inicio</label>
                                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Fecha Fin</label>
                                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Empresa</label>
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">— Todas —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
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
                            case 'registro pagos gift':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Año</label>
                                                    <select name="anio" id="anio" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php for ($y = (int)date('Y'); $y >= 2018; $y--): ?>
                                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Mes</label>
                                                    <select name="mes" id="mes" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $mesesNom = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                                                        foreach ($mesesNom as $i => $nombreMes):
                                                        ?>
                                                            <option value="<?php echo $i + 1; ?>"><?php echo $nombreMes; ?></option>
                                                        <?php endforeach; ?>
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
                            case 'comision mensual empresas':
                            case 'detalle cobranza ventas':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Año</label>
                                                    <select name="anio" id="anio" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php for ($y = (int)date('Y'); $y >= 2018; $y--): ?>
                                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Mes</label>
                                                    <select name="mes" id="mes" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $mesesNom = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                                                        foreach ($mesesNom as $i => $nombreMes):
                                                        ?>
                                                            <option value="<?php echo $i + 1; ?>"><?php echo $nombreMes; ?></option>
                                                        <?php endforeach; ?>
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
                            case 'ventas por locales liquidacion':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <label for="">Marca</label>
                                                    <select name="marca" id="marca" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resMarcas = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca WHERE mar_id != 9999 ORDER BY mar_descripcion ASC");
                                                        while ($m = mysqli_fetch_assoc($resMarcas)):
                                                        ?>
                                                            <option value="<?php echo (int)$m['mar_id']; ?>"><?php echo htmlspecialchars($m['mar_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Año</label>
                                                    <select name="anio" id="anio" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php for ($y = (int)date('Y'); $y >= 2018; $y--): ?>
                                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="">Mes</label>
                                                    <select name="mes" id="mes" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $mesesNom = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                                                        foreach ($mesesNom as $i => $nombreMes):
                                                        ?>
                                                            <option value="<?php echo $i + 1; ?>"><?php echo $nombreMes; ?></option>
                                                        <?php endforeach; ?>
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
                            case 'reporte recargas':
                            ?>
                                <div class="row">
                                    <div class="col-sm-8 offset-sm-2">
                                        <p>Historial de aumentos/disminuciones de cupo asignado por empleado.</p>
                                        <form action="./pages/reportes/excel.php">
                                            <input type="hidden" name="tipo" id="tipo" value="<?php echo $tipo; ?>">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label for="">Cliente</label>
                                                    <select name="cliente" id="cliente" class="form-control" required>
                                                        <option value="">— Seleccione —</option>
                                                        <?php
                                                        $resCli = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente ORDER BY cli_descripcion ASC");
                                                        while ($c = mysqli_fetch_assoc($resCli)):
                                                        ?>
                                                            <option value="<?php echo (int)$c['cli_id']; ?>"><?php echo htmlspecialchars($c['cli_descripcion']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="">Empleado</label>
                                                    <select name="empleado" id="empleado" class="form-control">
                                                        <option value="">— Todos los de la empresa —</option>
                                                        <?php
                                                        $resEmp = mysqli_query($mysqli, "SELECT per_id, per_nombre FROM personal ORDER BY per_nombre ASC");
                                                        while ($e = mysqli_fetch_assoc($resEmp)):
                                                        ?>
                                                            <option value="<?php echo (int)$e['per_id']; ?>"><?php echo htmlspecialchars($e['per_nombre']); ?></option>
                                                        <?php endwhile; ?>
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