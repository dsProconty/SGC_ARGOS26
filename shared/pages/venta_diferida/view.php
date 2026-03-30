<?php if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; } ?>
<div class="content" data-layout="tabbed">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">VENTAS DIFERIDAS</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Ventas Diferidas</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-info" onclick="abrirModalNueva()" style="color:#fff;">
                        <i class="icon dripicons-plus"></i> Nueva Venta Diferida
                    </button>
                </div>
            </div>
        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Listado de Ventas Diferidas</h5>
                    <div class="card-body">
                        <div id="loader_vd"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Nueva Venta Diferida -->
<div class="modal fade" id="modal_nueva_vd" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Venta Diferida</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_vd"></div>

                <!-- Búsqueda de empleado -->
                <div class="form-group">
                    <label>Cédula del Empleado</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="vd_cedula" placeholder="Ingrese cédula">
                        <div class="input-group-append">
                            <button class="btn btn-secondary" id="btn_buscar_vd">
                                <i class="icon dripicons-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <div id="card_empleado_vd" style="display:none;">
                    <div class="alert alert-info">
                        <strong id="vd_emp_nombre"></strong> — <span id="vd_emp_empresa"></span>
                        <input type="hidden" id="vd_per_id">
                    </div>

                    <div class="form-group">
                        <label>Descripción del producto / servicio</label>
                        <textarea class="form-control" id="vd_descripcion" rows="2" placeholder="Ej: Laptop Dell Inspiron 15"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Monto total ($)</label>
                                <input type="number" class="form-control" id="vd_monto_total" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Número de cuotas</label>
                                <input type="number" class="form-control" id="vd_num_cuotas" min="1" max="36" placeholder="Ej: 12">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha inicio de cobro</label>
                                <input type="date" class="form-control" id="vd_fecha_inicio">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-secondary" id="resumen_vd" style="display:none;">
                        Cuota mensual: <strong id="vd_cuota_calculada">$0.00</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_vd" style="display:none; color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Liquidar Deuda -->
<div class="modal fade" id="modal_liquidar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Liquidar Deuda</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_liquidar"></div>
                <p id="detalle_liquidar"></p>
                <input type="hidden" id="vd_id_liquidar">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn_confirmar_liquidar">
                    <i class="icon dripicons-checkmark"></i> Confirmar Liquidación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar Cuota -->
<div class="modal fade" id="modal_pagar_cuota" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pago de Cuota</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_cuota"></div>
                <p id="detalle_cuota"></p>
                <input type="hidden" id="vd_id_pagar">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn_confirmar_cuota" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<script src="js/venta_diferida.js"></script>
