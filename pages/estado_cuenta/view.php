<?php if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; } ?>
<div class="content" data-layout="tabbed">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">ESTADOS DE CUENTA</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Estados de Cuenta</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-info" onclick="$('#modal_generar_ec').modal('show')" style="color:#fff;">
                        <i class="icon dripicons-document"></i> Generar Estado de Cuenta
                    </button>
                </div>
            </div>
        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Estados de Cuenta Generados</h5>
                    <div class="card-body">
                        <div id="loader_ec"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Generar Estado de Cuenta -->
<div class="modal fade" id="modal_generar_ec" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generar Estado de Cuenta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_ec"></div>
                <div class="form-group">
                    <label>Cliente</label>
                    <select class="form-control" id="ec_cli_id">
                        <option value="">-- Seleccione un cliente --</option>
                        <?php
                        $q = mysqli_query($mysqli, "SELECT cli_id, cli_descripcion FROM cliente WHERE cli_descripcion != '' ORDER BY cli_descripcion ASC");
                        while ($r = mysqli_fetch_assoc($q)) {
                            echo "<option value='{$r['cli_id']}'>" . htmlspecialchars($r['cli_descripcion']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Período inicio</label>
                            <input type="date" class="form-control" id="ec_periodo_inicio">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Período fin</label>
                            <input type="date" class="form-control" id="ec_periodo_fin">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_generar_ec" style="color:#fff;">
                    <i class="icon dripicons-document"></i> Generar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Estado de Cuenta (imprimible) -->
<div class="modal fade" id="modal_ver_ec" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Estado de Cuenta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="ec_content"></div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btn_imprimir_ec" style="color:#fff;">
                    <i class="icon dripicons-file-pdf"></i> Generar PDF
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="js/estado_cuenta.js"></script>
