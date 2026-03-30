<?php if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; } ?>
<div class="content" data-layout="tabbed">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">GIFT CARDS</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Gift Cards</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-info" onclick="$('#modal_nuevo_lote').modal('show')" style="color:#fff;">
                        <i class="icon dripicons-plus"></i> Nuevo Lote
                    </button>
                </div>
            </div>
        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Lotes de Gift Cards</h5>
                    <div class="card-body">
                        <div id="loader_lotes"></div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Modal Ver Códigos -->
<div class="modal fade" id="modal_codigos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-list"></i> <span id="titulo_codigos">Códigos del Lote</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="loader_codigos"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn_exportar_excel" style="color:#fff;">
                    <i class="icon dripicons-download"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Lote -->
<div class="modal fade" id="modal_nuevo_lote" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Lote de Gift Cards</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_lote"></div>
                <div class="form-group">
                    <label>Cantidad de códigos</label>
                    <input type="number" class="form-control" id="lgc_cantidad" min="1" max="1000" placeholder="Ej: 50">
                </div>
                <div class="form-group">
                    <label>Cupo por código ($)</label>
                    <input type="number" class="form-control" id="lgc_cupo_codigo" step="0.01" min="0.01" placeholder="Ej: 25.00">
                </div>
                <div class="form-group">
                    <label>Período de facturación</label>
                    <input type="date" class="form-control" id="lgc_periodo_facturacion">
                </div>
                <div class="form-group">
                    <label>Fecha de caducidad de los códigos</label>
                    <input type="date" class="form-control" id="lgc_fecha_caducidad">
                    <small class="text-muted">Después de esta fecha los códigos no podrán usarse.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_crear_lote" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Crear Lote
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/sheetjs/xlsx.full.min.js"></script>
<script src="js/giftcard.js"></script>
