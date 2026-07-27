<?php if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; } ?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator" id="page_title">CLIENTES</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" id="breadcrumb_current">Clientes</li>
                        </ol>
                    </nav>
                </div>
                <div id="header_actions">
                    <button class="btn btn-primary" onclick="abrirModalNuevo()">
                        <i class="icon dripicons-plus"></i> Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <!-- ══════════════════════════════════════════════
             VISTA: LISTA DE CLIENTES
        ══════════════════════════════════════════════ -->
        <div id="vista_lista">
            <!-- KPIs -->
            <div class="row" id="kpis_clientes">
                <div class="col-6 col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total Clientes</div>
                            <div class="h3 mb-0" id="kpi_total">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="text-muted small">Empresariales</div>
                            <div class="h3 mb-0 text-primary" id="kpi_empresarial">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="text-muted small">Gift Card</div>
                            <div class="h3 mb-0 text-info" id="kpi_giftcard">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="form-inline">
                        <label class="mr-2 text-muted">Filtrar:</label>
                        <select class="form-control form-control-sm mr-2" id="filtro_tipo" onchange="cargarClientes()">
                            <option value="">Todos los tipos</option>
                            <option value="Empresarial">Empresarial</option>
                            <option value="Gift Card">Gift Card</option>
                            <option value="Sin definir">Sin definir</option>
                        </select>
                        <select class="form-control form-control-sm mr-2" id="filtro_beneficio" onchange="cargarClientes()">
                            <option value="">Todos los beneficios</option>
                            <option value="Cupo">Cupo</option>
                            <option value="Porcentaje">Porcentaje</option>
                        </select>
                        <select class="form-control form-control-sm mr-2" id="filtro_cartera" onchange="cargarClientes()">
                            <option value="">Toda la cartera</option>
                            <option value="30">30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                            <option value="90+">90+ días</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="icon dripicons-cross"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Listado de Clientes / Empresas</h5></div>
                <div class="card-body">
                    <div id="loader_lista" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="tabla_wrapper" style="display:none;">
                        <table id="table_clientes" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Empresa / Cliente</th>
                                    <th>Tipo</th>
                                    <th>Ciudad</th>
                                    <th>Contacto</th>
                                    <th>Email</th>
                                    <th>Beneficio</th>
                                    <th>Cartera</th>
                                    <th>Personal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_clientes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /vista_lista -->

        <!-- ══════════════════════════════════════════════
             VISTA: PERFIL 360° DEL CLIENTE
        ══════════════════════════════════════════════ -->
        <div id="vista_detalle" style="display:none;">
            <!-- Header del cliente -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary mr-3" onclick="volverLista()">
                            <i class="icon dripicons-arrow-thin-left"></i> Volver
                        </button>
                        <div>
                            <h5 class="mb-0" id="detalle_nombre"></h5>
                            <small class="text-muted" id="detalle_subtitulo"></small>
                        </div>
                        <div class="ml-auto">
                            <button class="btn btn-sm btn-primary" onclick="editarClienteActual()">
                                <i class="icon dripicons-document-edit"></i> Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-0" id="tabs_detalle">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-tab="info" onclick="cambiarTab(this,'info'); return false;">
                        <i class="icon dripicons-briefcase"></i> Información
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="personal" onclick="cambiarTab(this,'personal'); return false;">
                        <i class="icon dripicons-user-group"></i> Personal <span class="badge badge-secondary ml-1" id="badge_personal">...</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="consumos" onclick="cambiarTab(this,'consumos'); return false;">
                        <i class="icon dripicons-shopping-bag"></i> Consumos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="ventas_diferidas" onclick="cambiarTab(this,'ventas_diferidas'); return false;">
                        <i class="icon dripicons-basket"></i> Ventas Diferidas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="estado_cuenta" onclick="cambiarTab(this,'estado_cuenta'); return false;">
                        <i class="icon dripicons-graph-bar"></i> Estados de Cuenta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="giftcards" onclick="cambiarTab(this,'giftcards'); return false;">
                        <i class="icon dripicons-card"></i> Gift Cards
                    </a>
                </li>
            </ul>

            <!-- TAB: INFORMACIÓN -->
            <div class="card tab-panel" id="tab_info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Datos de contacto</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th class="text-muted font-weight-normal" style="width:40%">N° Convenio</th><td id="inf_convenio"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Ciudad</th><td id="inf_ciudad"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Contacto</th><td id="inf_contacto"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Email principal</th><td id="inf_email"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Email secundario</th><td id="inf_email2"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Teléfono</th><td id="inf_telefono"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Teléfono alt.</th><td id="inf_telefono2"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Día de corte</th><td id="inf_dia_corte"></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Configuración comercial</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 border-right">
                                            <div class="text-muted small">Tipo de beneficio</div>
                                            <div id="inf_tipo_beneficio" class="mt-1"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Valor</div>
                                            <div id="inf_valor_beneficio" class="mt-1 font-weight-bold"></div>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row text-center">
                                        <div class="col-6 border-right">
                                            <div class="text-muted small">Tipo de cartera</div>
                                            <div id="inf_tipo_cartera" class="mt-1"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Comisión</div>
                                            <div id="inf_comision" class="mt-1 font-weight-bold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PERSONAL -->
            <div class="card tab-panel" id="tab_personal" style="display:none;">
                <div class="card-body">
                    <div class="text-right mb-2">
                        <button class="btn btn-sm btn-outline-info" onclick="abrirModalCargaMasiva()">
                            <i class="icon dripicons-upload"></i> Carga Masiva
                        </button>
                    </div>
                    <div id="alerta_personal"></div>
                    <div id="loader_personal" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_personal_wrapper" style="display:none;">
                        <table id="table_personal" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Nombre</th><th>Documento</th>
                                <th>N° Tarjeta</th><th>Email</th><th>Estado</th>
                                <th>Cupo Asignado</th><th>Cupo Disponible</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tbody_personal"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: CONSUMOS -->
            <div class="card tab-panel" id="tab_consumos" style="display:none;">
                <div class="card-body">
                    <div id="loader_consumos" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_consumos_wrapper" style="display:none;">
                        <table id="table_consumos" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>Fecha</th><th>Empleado</th><th>Tarjeta</th>
                                <th>Local</th><th>Total</th><th>Convenio</th>
                                <th>Externo</th><th>Estado</th>
                            </tr></thead>
                            <tbody id="tbody_consumos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: VENTAS DIFERIDAS -->
            <div class="card tab-panel" id="tab_ventas_diferidas" style="display:none;">
                <div class="card-body">
                    <div id="loader_vd_cliente" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_vd_cliente_wrapper" style="display:none;">
                        <table id="table_vd_cliente" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>Empleado</th><th>Descripción</th><th>Monto Total</th>
                                <th>Cuota</th><th>Avance</th><th>Inicio</th><th>Estado</th>
                            </tr></thead>
                            <tbody id="tbody_vd_cliente"></tbody>
                        </table>
                    </div>
                    <p id="vd_cliente_sin_datos" class="text-muted text-center py-3" style="display:none;">
                        Este cliente no tiene ventas diferidas registradas.
                    </p>
                </div>
            </div>

            <!-- TAB: ESTADOS DE CUENTA -->
            <div class="card tab-panel" id="tab_estado_cuenta" style="display:none;">
                <div class="card-body">
                    <div id="loader_ec" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_ec_wrapper" style="display:none;">
                        <table id="table_ec" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Período</th><th>Monto Total</th>
                                <th>Generado</th><th>Estado Envío</th><th>PDF</th>
                            </tr></thead>
                            <tbody id="tbody_ec"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: GIFT CARDS -->
            <div class="card tab-panel" id="tab_giftcards" style="display:none;">
                <div class="card-body">
                    <div id="loader_gc" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_gc_wrapper" style="display:none;">
                        <table id="table_gc" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Fecha</th><th>Solicitante</th>
                                <th>Cantidad</th><th>Cupo c/código</th>
                                <th>Activos</th><th>Consumidos</th><th>Vencidos</th>
                            </tr></thead>
                            <tbody id="tbody_gc"></tbody>
                        </table>
                    </div>
                    <p id="gc_sin_datos" class="text-muted text-center py-3" style="display:none;">
                        Este cliente no tiene lotes de Gift Cards registrados.
                    </p>
                </div>
            </div>

        </div><!-- /vista_detalle -->

    </section>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — CREAR / EDITAR CLIENTE
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteLabel">Nuevo Cliente</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formCliente">
                <div class="modal-body">
                    <input type="hidden" id="cli_id" name="cli_id">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Empresa / Cliente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cli_descripcion" name="cli_descripcion" required autocomplete="off" placeholder="Ej: EMPRESA XYZ S.A.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>N° Convenio</label>
                                <input type="text" class="form-control" id="cli_numero_convenio" name="cli_numero_convenio" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" class="form-control" id="cli_ciudad" name="cli_ciudad" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Persona de contacto</label>
                                <input type="text" class="form-control" id="cli_contacto" name="cli_contacto" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email principal</label>
                                <input type="email" class="form-control" id="cli_email" name="cli_email" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email secundario</label>
                                <input type="email" class="form-control" id="cli_email2" name="cli_email2" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" id="cli_telefono" name="cli_telefono" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono alternativo</label>
                                <input type="text" class="form-control" id="cli_telefono2" name="cli_telefono2" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Día de corte</label>
                                <select class="form-control" id="cli_dia_corte" name="cli_dia_corte">
                                    <option value="0">— Sin corte —</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3"><i class="icon dripicons-graph-bar"></i> Configuración Comercial</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de beneficio</label>
                                <select class="form-control" id="cli_tipo_beneficio" name="cli_tipo_beneficio">
                                    <option value="">— Seleccionar —</option>
                                    <option value="Cupo">Cupo (monto fijo)</option>
                                    <option value="Porcentaje">Porcentaje (%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label id="label_valor">Valor del beneficio</label>
                                <div class="input-group">
                                    <div class="input-group-prepend" id="prefix_ben"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control" id="cli_valor_beneficio" name="cli_valor_beneficio" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de cartera</label>
                                <select class="form-control" id="cli_tipo_cartera" name="cli_tipo_cartera">
                                    <option value="">— Seleccionar —</option>
                                    <option value="30">30 días</option>
                                    <option value="60">60 días</option>
                                    <option value="90">90 días</option>
                                    <option value="90+">90+ días</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de Cliente</label>
                                <select class="form-control" id="cli_tipo_cliente" name="cli_tipo_cliente">
                                    <option value="">— Sin definir —</option>
                                    <option value="Empresarial">Empresarial</option>
                                    <option value="Gift Card">Gift Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Comisión (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cli_comision" name="cli_comision" min="0" max="100" step="0.01" placeholder="0.00" value="0">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_guardar">
                        <i class="icon dripicons-checkmark"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — EDITAR EMPLEADO (CL-E)
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEmpleado" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Empleado</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_empleado"></div>
                <input type="hidden" id="emp_per_id">
                <input type="hidden" id="emp_cli_id">
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input type="text" class="form-control" id="emp_nombre" autocomplete="off">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cédula</label>
                            <input type="text" class="form-control" id="emp_documento" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" id="emp_correo" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Cupo asignado ($)</label>
                    <input type="number" class="form-control" id="emp_cupo" min="0.01" step="0.01">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_guardar_empleado">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — AUDITORÍA DE EMPLEADO (CL-F)
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAuditoria" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-clock"></i> Auditoría — <span id="aud_nombre_empleado"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="auditoria_body" style="max-height:65vh; overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — CONFIRMAR BLOQUEAR/ACTIVAR EMPLEADO (CL-E)
     Reemplaza confirm() nativo: Chrome lo puede silenciar sin avisar
     después de varios diálogos ("Prevent this page from creating
     additional dialogs"), y el clic parecía no hacer nada.
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalConfirmarEstado" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ce_titulo">Confirmar</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="ce_mensaje" class="mb-0"></p>
                <input type="hidden" id="ce_per_id">
                <input type="hidden" id="ce_nuevo_estado">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn_confirmar_estado">
                    <i class="icon dripicons-checkmark"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — CARGA MASIVA DE PERSONAL (CL-I)
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-upload"></i> Carga Masiva de Personal</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_carga_masiva"></div>
                <div class="alert alert-info py-2 px-3" style="font-size:.85rem;">
                    <i class="icon dripicons-information"></i>
                    Excel o CSV sin encabezados: columna <strong>A</strong> cédula, <strong>B</strong> nombre completo,
                    <strong>C</strong> cupo (solo requerido para Añadir / Actualizar cupo).
                </div>
                <div class="form-group">
                    <label>Acción</label>
                    <select class="form-control" id="cm_accion">
                        <option value="anadir">Añadir empleados nuevos</option>
                        <option value="actualizar_cupo">Actualizar cupo</option>
                        <option value="bloquear">Bloquear empleados</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Archivo (.xlsx, .xls, .csv)</label>
                    <input type="file" class="form-control-file" id="cm_archivo" accept=".xlsx,.xls,.csv">
                </div>
                <div id="cm_resultado" style="display:none; max-height:35vh; overflow-y:auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-info" id="btn_procesar_carga_masiva" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Procesar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ -->
<script src="assets/vendor/sheetjs/xlsx.full.min.js"></script>
<script>
var _cliId   = null;   // ID del cliente en vista detalle
var _cliData = null;   // Datos del cliente actual
var _tabsLoaded = {};  // tabs ya cargados para evitar re-fetch
var _personalData = {}; // cache de empleados de la ficha actual, por per_id (CL-E/CL-F)

var cartBadge = {'30':'success','60':'warning','90':'danger','90+':'dark'};
var tipoBadge = {'Empresarial':'primary','Gift Card':'info','Sin definir':'secondary'};

// ══════════════════════════════════════════════
// LISTA
// ══════════════════════════════════════════════
function cargarClientes() {
    var t = $('#filtro_tipo').val();
    var b = $('#filtro_beneficio').val();
    var c = $('#filtro_cartera').val();
    $('#loader_lista').show();
    $('#tabla_wrapper').hide();

    // Destruir DataTable si existe
    if ($.fn.DataTable.isDataTable('#table_clientes')) {
        $('#table_clientes').DataTable().destroy();
        $('#tbody_clientes').empty();
    }

    $.getJSON('ajax/clientes/clientes.php?action=list&tipo='+encodeURIComponent(t)+'&beneficio='+encodeURIComponent(b)+'&cartera='+encodeURIComponent(c), function(res) {
        $('#loader_lista').hide();
        if (!res.success) { alert('Error al cargar clientes'); return; }

        if (res.kpis) {
            $('#kpi_total').text(res.kpis.total);
            $('#kpi_empresarial').text(res.kpis.empresarial);
            $('#kpi_giftcard').text(res.kpis.giftcard);
        }

        var html = '';
        $.each(res.data, function(i, d) {
            var tipoBen = d.cli_tipo_beneficio
                ? '<span class="badge badge-' + (d.cli_tipo_beneficio==='Cupo'?'info':'primary') + '">'
                  + d.cli_tipo_beneficio
                  + (d.cli_valor_beneficio ? ' — ' + (d.cli_tipo_beneficio==='Cupo'?'$':'') + parseFloat(d.cli_valor_beneficio).toFixed(2) + (d.cli_tipo_beneficio==='Porcentaje'?'%':'') : '')
                  + '</span>'
                : '<span class="text-muted">—</span>';

            var tipoCart = d.cli_tipo_cartera
                ? '<span class="badge badge-' + (cartBadge[d.cli_tipo_cartera]||'secondary') + '">' + d.cli_tipo_cartera + ' días</span>'
                : '<span class="text-muted">—</span>';

            var tipoCli = '<span class="badge badge-' + (tipoBadge[d.cli_tipo_cliente]||'secondary') + '">' + d.cli_tipo_cliente + '</span>';

            html += '<tr>'
                + '<td>' + (i+1) + '</td>'
                + '<td><strong>' + d.cli_descripcion + '</strong></td>'
                + '<td>' + tipoCli + '</td>'
                + '<td>' + (d.cli_ciudad||'—') + '</td>'
                + '<td>' + (d.cli_contacto||'—') + '</td>'
                + '<td>' + (d.cli_email ? '<a href="mailto:'+d.cli_email+'">'+d.cli_email+'</a>' : '—') + '</td>'
                + '<td>' + tipoBen + '</td>'
                + '<td>' + tipoCart + '</td>'
                + '<td class="text-center">'
                  + (d.total_personal > 0 ? '<span class="badge badge-secondary">'+d.total_personal+' emp.</span>' : '<span class="text-muted">0</span>')
                + '</td>'
                + '<td class="text-nowrap">'
                  + '<button class="btn btn-info btn-sm mr-1" onclick="verDetalle('+d.cli_id+')" title="Ver perfil">'
                  + '<i class="icon dripicons-user"></i></button>'
                  + '<button class="btn btn-primary btn-sm" onclick="editarCliente('+d.cli_id+')" title="Editar">'
                  + '<i class="icon dripicons-document-edit"></i></button>'
                + '</td>'
              + '</tr>';
        });

        $('#tbody_clientes').html(html);
        $('#tabla_wrapper').show();

        var dt = $('#table_clientes').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            columnDefs: [{ orderable: false, targets: [9] }],
            pageLength: 15,
            order: [[1,'asc']]
        });
    });
}

function limpiarFiltros() {
    $('#filtro_tipo, #filtro_beneficio, #filtro_cartera').val('');
    cargarClientes();
}

// ══════════════════════════════════════════════
// VISTA DETALLE 360°
// ══════════════════════════════════════════════
function verDetalle(id) {
    _cliId = id;
    _tabsLoaded = {};
    $('#alerta_personal').html('');

    $.getJSON('ajax/clientes/clientes.php?action=get&id='+id, function(res) {
        if (!res.success) { alert('Error al cargar el cliente'); return; }
        _cliData = res.data;
        var d = res.data;

        // Header
        $('#detalle_nombre').text(d.cli_descripcion);
        var subtitulo = [];
        if (d.cli_ciudad)           subtitulo.push(d.cli_ciudad);
        if (d.cli_numero_convenio)  subtitulo.push('Conv. ' + d.cli_numero_convenio);
        if (d.cli_tipo_cartera)     subtitulo.push('Cartera ' + d.cli_tipo_cartera + ' días');
        $('#detalle_subtitulo').text(subtitulo.join(' · '));

        // Tab Info
        $('#inf_convenio').text(d.cli_numero_convenio || '—');
        $('#inf_ciudad').text(d.cli_ciudad || '—');
        $('#inf_contacto').text(d.cli_contacto || '—');
        $('#inf_email').html(d.cli_email ? '<a href="mailto:'+d.cli_email+'">'+d.cli_email+'</a>' : '—');
        $('#inf_email2').html(d.cli_email2 ? '<a href="mailto:'+d.cli_email2+'">'+d.cli_email2+'</a>' : '—');
        $('#inf_telefono').text(d.cli_telefono || '—');
        $('#inf_telefono2').text(d.cli_telefono2 || '—');
        $('#inf_dia_corte').text(d.cli_dia_corte && d.cli_dia_corte != '0' ? 'Día ' + d.cli_dia_corte : '—');

        if (d.cli_tipo_beneficio) {
            var bc = d.cli_tipo_beneficio === 'Cupo' ? 'info' : 'primary';
            $('#inf_tipo_beneficio').html('<span class="badge badge-'+bc+'">'+d.cli_tipo_beneficio+'</span>');
            var val = d.cli_tipo_beneficio === 'Cupo'
                ? '$ ' + parseFloat(d.cli_valor_beneficio||0).toFixed(2)
                : parseFloat(d.cli_valor_beneficio||0).toFixed(2) + '%';
            $('#inf_valor_beneficio').text(val);
        } else {
            $('#inf_tipo_beneficio').html('<span class="text-muted">—</span>');
            $('#inf_valor_beneficio').text('—');
        }
        if (d.cli_tipo_cartera) {
            $('#inf_tipo_cartera').html('<span class="badge badge-'+(cartBadge[d.cli_tipo_cartera]||'secondary')+'">'+d.cli_tipo_cartera+' días</span>');
        } else {
            $('#inf_tipo_cartera').html('<span class="text-muted">—</span>');
        }
        $('#inf_comision').text(parseFloat(d.cli_comision||0).toFixed(2) + '%');

        // Resetear tabs
        $('#tabs_detalle .nav-link').removeClass('active');
        $('#tabs_detalle .nav-link[data-tab="info"]').addClass('active');
        $('.tab-panel').hide();
        $('#tab_info').show();

        // Cambiar vista
        $('#vista_lista').hide();
        $('#vista_detalle').show();
        $('#page_title').text(d.cli_descripcion);
        $('#breadcrumb_current').text(d.cli_descripcion);
        $('#header_actions').hide();

        // Cargar personal inmediatamente (badge)
        cargarTabPersonal();
    });
}

function volverLista() {
    $('#vista_detalle').hide();
    $('#vista_lista').show();
    $('#page_title').text('CLIENTES');
    $('#breadcrumb_current').text('Clientes');
    $('#header_actions').show();
    _cliId = null;
    _cliData = null;
}

function editarClienteActual() {
    if (_cliId) editarCliente(_cliId);
}

// ══════════════════════════════════════════════
// TABS
// ══════════════════════════════════════════════
function cambiarTab(el, tab) {
    $('#tabs_detalle .nav-link').removeClass('active');
    $(el).addClass('active');
    $('.tab-panel').hide();
    $('#tab_' + tab).show();

    if (!_tabsLoaded[tab]) {
        if      (tab === 'personal')          cargarTabPersonal();
        else if (tab === 'consumos')          cargarTabConsumos();
        else if (tab === 'ventas_diferidas')  cargarTabVD();
        else if (tab === 'estado_cuenta')     cargarTabEC();
        else if (tab === 'giftcards')         cargarTabGiftCards();
    }
}

// — Personal ──────────────────────────────────────────────────────────────────
function cargarTabPersonal() {
    if (_tabsLoaded['personal']) return;
    $('#loader_personal').show();
    $('#tabla_personal_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=personal_list&cli_id='+_cliId, function(res) {
        $('#loader_personal').hide();
        if (!res.success) return;

        $('#badge_personal').text(res.data.length);
        _personalData = {};
        var html = '';
        $.each(res.data, function(i, p) {
            _personalData[p.per_id] = p;
            var estadoBadge = {activo:'success', bloqueado:'danger', inactivo:'secondary'}[p.per_estado] || 'secondary';
            var esBloqueado = p.per_estado === 'bloqueado';
            html += '<tr' + (esBloqueado ? ' class="table-danger"' : '') + '>'
                + '<td>' + (i+1) + '</td>'
                + '<td>' + p.per_nombre + '</td>'
                + '<td>' + (p.per_documento || '—') + '</td>'
                + '<td><code>' + (p.per_numero_tarjeta || '—') + '</code></td>'
                + '<td>' + (p.per_correo || '—') + '</td>'
                + '<td><span class="badge badge-'+estadoBadge+'">'+p.per_estado+'</span></td>'
                + '<td class="text-right">$ ' + parseFloat(p.per_cupo_asignado||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(p.per_cupo_disponible||0).toFixed(2) + '</td>'
                + '<td class="text-nowrap">'
                  + '<button class="btn btn-primary btn-sm mr-1" onclick="editarEmpleado('+p.per_id+')" title="Editar">'
                  + '<i class="icon dripicons-document-edit"></i></button>'
                  + '<button class="btn btn-sm ' + (esBloqueado ? 'btn-success' : 'btn-danger') + ' mr-1" onclick="bloquearEmpleado('+p.per_id+')" title="'+(esBloqueado ? 'Activar' : 'Bloquear')+'">'
                  + '<i class="icon ' + (esBloqueado ? 'dripicons-lock-open' : 'dripicons-lock') + '"></i></button>'
                  + '<button class="btn btn-outline-secondary btn-sm" onclick="verAuditoria('+p.per_id+')" title="Auditoría">'
                  + '<i class="icon dripicons-clock"></i></button>'
                + '</td>'
              + '</tr>';
        });
        $('#tbody_personal').html(html);

        if ($.fn.DataTable.isDataTable('#table_personal')) $('#table_personal').DataTable().destroy();
        $('#table_personal').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            columnDefs: [{ orderable: false, targets: [8] }],
            pageLength: 10, order: [[1,'asc']]
        });

        $('#tabla_personal_wrapper').show();
        _tabsLoaded['personal'] = true;
    });
}

// — Consumos ──────────────────────────────────────────────────────────────────
function cargarTabConsumos() {
    if (_tabsLoaded['consumos']) return;
    $('#loader_consumos').show();
    $('#tabla_consumos_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=consumos_list&cli_id='+_cliId, function(res) {
        $('#loader_consumos').hide();
        if (!res.success) return;

        var html = '';
        $.each(res.data, function(i, c) {
            var estColor = {pendiente:'warning', exitoso:'success', rechazado:'danger', anulado:'secondary'}[c.con_estado] || 'secondary';
            html += '<tr>'
                + '<td>' + c.con_fecha + ' <small class="text-muted">' + (c.con_hora||'') + '</small></td>'
                + '<td>' + c.per_nombre + '</td>'
                + '<td><code>' + c.con_numero_tarjeta + '</code></td>'
                + '<td>' + c.local_nombre + '</td>'
                + '<td class="text-right font-weight-bold">$ ' + parseFloat(c.con_valor_total||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(c.con_monto_convenio||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(c.con_monto_externo||0).toFixed(2) + '</td>'
                + '<td><span class="badge badge-'+estColor+'">'+c.con_estado+'</span></td>'
              + '</tr>';
        });
        $('#tbody_consumos').html(html);

        if ($.fn.DataTable.isDataTable('#table_consumos')) $('#table_consumos').DataTable().destroy();
        $('#table_consumos').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            pageLength: 15, order: [[0,'desc']]
        });

        $('#tabla_consumos_wrapper').show();
        _tabsLoaded['consumos'] = true;
    });
}

// — Ventas Diferidas (CL-D) ──────────────────────────────────────────────────
function cargarTabVD() {
    if (_tabsLoaded['ventas_diferidas']) return;
    $('#loader_vd_cliente').show();
    $('#tabla_vd_cliente_wrapper, #vd_cliente_sin_datos').hide();

    var vdBadge = {activo:'success', completado:'primary', cancelado:'danger'};

    $.getJSON('ajax/clientes/clientes.php?action=venta_diferida_list&cli_id='+_cliId, function(res) {
        $('#loader_vd_cliente').hide();
        if (!res.success) return;

        if (!res.data.length) {
            $('#vd_cliente_sin_datos').show();
        } else {
            var html = '';
            $.each(res.data, function(i, v) {
                var pagadas = parseInt(v.vd_cuotas_pagadas) || 0;
                var total   = parseInt(v.vd_num_cuotas) || 0;
                var pct     = total > 0 ? Math.round(pagadas / total * 100) : 0;
                html += '<tr>'
                    + '<td>' + v.per_nombre + '<br><small class="text-muted">' + (v.per_documento||'') + '</small></td>'
                    + '<td>' + v.vd_descripcion + '</td>'
                    + '<td class="text-right">$ ' + parseFloat(v.vd_monto_total||0).toFixed(2) + '</td>'
                    + '<td class="text-right">$ ' + parseFloat(v.vd_monto_cuota||0).toFixed(2) + '</td>'
                    + '<td>'
                      + '<div class="progress" style="height:16px;" title="'+pagadas+' de '+total+' cuotas">'
                      + '<div class="progress-bar bg-success" style="width:'+pct+'%">'+pagadas+'/'+total+'</div>'
                      + '</div>'
                    + '</td>'
                    + '<td>' + (v.vd_fecha_inicio||'') + '</td>'
                    + '<td><span class="badge badge-'+(vdBadge[v.vd_estado]||'secondary')+'">'+v.vd_estado+'</span></td>'
                  + '</tr>';
            });
            $('#tbody_vd_cliente').html(html);

            if ($.fn.DataTable.isDataTable('#table_vd_cliente')) $('#table_vd_cliente').DataTable().destroy();
            $('#table_vd_cliente').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                pageLength: 10, order: [[5,'desc']]
            });
            $('#tabla_vd_cliente_wrapper').show();
        }
        _tabsLoaded['ventas_diferidas'] = true;
    });
}

// — Estados de Cuenta ─────────────────────────────────────────────────────────
function cargarTabEC() {
    if (_tabsLoaded['estado_cuenta']) return;
    $('#loader_ec').show();
    $('#tabla_ec_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=estado_cuenta_list&cli_id='+_cliId, function(res) {
        $('#loader_ec').hide();
        if (!res.success) return;

        var html = '';
        $.each(res.data, function(i, e) {
            var envBadge = {pendiente:'warning', enviado:'success', error:'danger'}[e.ec_estado_envio] || 'secondary';
            var pdf = e.ec_archivo_pdf
                ? '<a href="' + e.ec_archivo_pdf + '" target="_blank" class="btn btn-xs btn-outline-danger"><i class="icon dripicons-document"></i> PDF</a>'
                : '<span class="text-muted">—</span>';
            html += '<tr>'
                + '<td>' + e.ec_id + '</td>'
                + '<td>' + e.ec_periodo_inicio + ' → ' + e.ec_periodo_fin + '</td>'
                + '<td class="text-right font-weight-bold">$ ' + parseFloat(e.ec_monto_total||0).toFixed(2) + '</td>'
                + '<td>' + e.ec_fecha_generacion + '</td>'
                + '<td><span class="badge badge-'+envBadge+'">'+e.ec_estado_envio+'</span></td>'
                + '<td>' + pdf + '</td>'
              + '</tr>';
        });
        $('#tbody_ec').html(html);

        if ($.fn.DataTable.isDataTable('#table_ec')) $('#table_ec').DataTable().destroy();
        $('#table_ec').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            pageLength: 10, order: [[0,'desc']]
        });

        $('#tabla_ec_wrapper').show();
        _tabsLoaded['estado_cuenta'] = true;
    });
}

// — Gift Cards ─────────────────────────────────────────────────────────────────
function cargarTabGiftCards() {
    if (_tabsLoaded['giftcards']) return;
    $('#loader_gc').show();
    $('#tabla_gc_wrapper, #gc_sin_datos').hide();

    $.getJSON('ajax/clientes/clientes.php?action=giftcard_list&cli_id='+_cliId, function(res) {
        $('#loader_gc').hide();
        if (!res.success) return;

        if (!res.data.length) {
            $('#gc_sin_datos').show();
        } else {
            var html = '';
            $.each(res.data, function(i, g) {
                html += '<tr>'
                    + '<td>' + g.lgc_id + '</td>'
                    + '<td>' + g.lgc_fecha + '</td>'
                    + '<td>' + g.solicitante + '</td>'
                    + '<td class="text-center">' + g.lgc_cantidad + '</td>'
                    + '<td class="text-right">$ ' + parseFloat(g.lgc_cupo_codigo||0).toFixed(2) + '</td>'
                    + '<td class="text-center"><span class="badge badge-success">' + (g.activos||0) + '</span></td>'
                    + '<td class="text-center"><span class="badge badge-secondary">' + (g.consumidos||0) + '</span></td>'
                    + '<td class="text-center"><span class="badge badge-warning">' + (g.vencidos||0) + '</span></td>'
                  + '</tr>';
            });
            $('#tbody_gc').html(html);

            if ($.fn.DataTable.isDataTable('#table_gc')) $('#table_gc').DataTable().destroy();
            $('#table_gc').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                pageLength: 10, order: [[1,'desc']]
            });
            $('#tabla_gc_wrapper').show();
        }
        _tabsLoaded['giftcards'] = true;
    });
}

// ══════════════════════════════════════════════
// MODAL CREAR / EDITAR
// ══════════════════════════════════════════════
function abrirModalNuevo() {
    $('#formCliente')[0].reset();
    $('#cli_id').val('');
    $('#modalClienteLabel').text('Nuevo Cliente');
    actualizarPrefijo('');
    $('#modalCliente').modal('show');
}

function editarCliente(id) {
    $.getJSON('ajax/clientes/clientes.php?action=get&id='+id, function(res) {
        if (!res.success) { alert('Error al cargar cliente'); return; }
        var d = res.data;
        $('#cli_id').val(d.cli_id);
        $('#cli_descripcion').val(d.cli_descripcion);
        $('#cli_numero_convenio').val(d.cli_numero_convenio||'');
        $('#cli_ciudad').val(d.cli_ciudad||'');
        $('#cli_contacto').val(d.cli_contacto||'');
        $('#cli_email').val(d.cli_email||'');
        $('#cli_email2').val(d.cli_email2||'');
        $('#cli_telefono').val(d.cli_telefono||'');
        $('#cli_telefono2').val(d.cli_telefono2||'');
        $('#cli_dia_corte').val(d.cli_dia_corte||'0');
        $('#cli_tipo_beneficio').val(d.cli_tipo_beneficio||'');
        $('#cli_valor_beneficio').val(d.cli_valor_beneficio||'');
        $('#cli_tipo_cartera').val(d.cli_tipo_cartera||'');
        $('#cli_tipo_cliente').val(d.cli_tipo_cliente||'');
        $('#cli_comision').val(d.cli_comision||'0');
        actualizarPrefijo(d.cli_tipo_beneficio||'');
        $('#modalClienteLabel').text('Editar Cliente');
        $('#modalCliente').modal('show');
    });
}

function actualizarPrefijo(tipo) {
    if (tipo === 'Porcentaje') {
        $('#prefix_ben').html('<span class="input-group-text">%</span>');
        $('#label_valor').text('Porcentaje de descuento');
    } else {
        $('#prefix_ben').html('<span class="input-group-text">$</span>');
        $('#label_valor').text('Cupo máximo');
    }
}

$('#cli_tipo_beneficio').on('change', function() { actualizarPrefijo($(this).val()); });

$('#formCliente').on('submit', function(e) {
    e.preventDefault();
    var id     = $('#cli_id').val();
    var action = id ? 'editar' : 'crear';
    var btn    = $('#btn_guardar');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.post('ajax/clientes/clientes.php?action='+action, $(this).serialize(), function(res) {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        if (res.success) {
            $('#modalCliente').modal('hide');
            if (_cliId) {
                // Estamos en vista detalle — refrescar nombre si editamos el actual
                _tabsLoaded = {};
                verDetalle(id || res.id);
            } else {
                cargarClientes();
            }
        } else {
            alert(res.mensaje || 'Error al guardar');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        alert('Error de conexión');
    });
});

// ══════════════════════════════════════════════
// CL-E: EDITAR / BLOQUEAR EMPLEADO
// ══════════════════════════════════════════════
function editarEmpleado(per_id) {
    var p = _personalData[per_id];
    if (!p) return;
    $('#alerta_empleado').html('');
    $('#emp_per_id').val(p.per_id);
    $('#emp_cli_id').val(_cliId);
    $('#emp_nombre').val(p.per_nombre);
    $('#emp_documento').val(p.per_documento || '');
    $('#emp_correo').val(p.per_correo || '');
    $('#emp_cupo').val(p.per_cupo_asignado || '');
    $('#modalEmpleado').modal('show');
}

$('#btn_guardar_empleado').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    $.post('ajax/clientes/clientes.php?action=personal_editar', {
        per_id: $('#emp_per_id').val(),
        cli_id: $('#emp_cli_id').val(),
        per_nombre: $('#emp_nombre').val(),
        per_documento: $('#emp_documento').val(),
        per_correo: $('#emp_correo').val(),
        per_cupo_asignado: $('#emp_cupo').val()
    }, function(res) {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        if (res.success) {
            $('#modalEmpleado').modal('hide');
            _tabsLoaded['personal'] = false;
            cargarTabPersonal();
        } else {
            $('#alerta_empleado').html('<div class="alert alert-danger mb-0">' + res.mensaje + '</div>');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        $('#alerta_empleado').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
    });
});

function bloquearEmpleado(per_id) {
    var p = _personalData[per_id];
    if (!p) return;
    var esBloqueado = p.per_estado === 'bloqueado';
    var nuevoEstado = esBloqueado ? 'activo' : 'bloqueado';
    var accion = esBloqueado ? 'activar' : 'bloquear';

    $('#ce_titulo').text((esBloqueado ? 'Activar' : 'Bloquear') + ' Empleado');
    $('#ce_mensaje').text('¿Seguro que deseas ' + accion + ' a ' + p.per_nombre + '?');
    $('#ce_per_id').val(per_id);
    $('#ce_nuevo_estado').val(nuevoEstado);
    $('#btn_confirmar_estado').removeClass('btn-danger btn-success').addClass(esBloqueado ? 'btn-success' : 'btn-danger');
    $('#modalConfirmarEstado').modal('show');
}

$('#btn_confirmar_estado').on('click', function () {
    var per_id = $('#ce_per_id').val();
    var nuevo_estado = $('#ce_nuevo_estado').val();
    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.post('ajax/clientes/clientes.php?action=personal_cambiar_estado', {
        per_id: per_id, cli_id: _cliId, per_estado: nuevo_estado
    }, function (res) {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar');
        $('#modalConfirmarEstado').modal('hide');
        if (res.success) {
            $('#alerta_personal').html('<div class="alert alert-success py-2 mb-2">Estado actualizado correctamente.</div>');
            _tabsLoaded['personal'] = false;
            cargarTabPersonal();
        } else {
            $('#alerta_personal').html('<div class="alert alert-danger py-2 mb-2">' + (res.mensaje || 'Error al actualizar el estado') + '</div>');
        }
    }, 'json').fail(function () {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar');
        $('#modalConfirmarEstado').modal('hide');
        $('#alerta_personal').html('<div class="alert alert-danger py-2 mb-2">Error de conexión</div>');
    });
});

// ══════════════════════════════════════════════
// CL-F: AUDITORÍA DE EMPLEADO
// ══════════════════════════════════════════════
function verAuditoria(per_id) {
    var p = _personalData[per_id];
    $('#aud_nombre_empleado').text(p ? p.per_nombre : '');
    $('#auditoria_body').html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#modalAuditoria').modal('show');

    $.getJSON('ajax/clientes/clientes.php?action=personal_trazabilidad_list&per_id=' + per_id, function(res) {
        if (!res.success || !res.data.length) {
            $('#auditoria_body').html('<p class="text-muted text-center mb-0">Sin registros de auditoría para este empleado.</p>');
            return;
        }
        var html = '<div class="list-group">';
        res.data.forEach(function(t) {
            html += '<div class="list-group-item">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<span><strong>' + t.tra_campo_label + '</strong></span>'
                + '<small class="text-muted">' + t.tra_fecha + '</small>'
                + '</div>'
                + '<div class="small text-muted">Por: ' + t.name_user + '</div>'
                + (t.tra_valor_anterior || t.tra_valor_nuevo
                    ? '<div class="small mt-1"><span class="text-muted">' + (t.tra_valor_anterior || '—') + '</span> &rarr; <strong>' + (t.tra_valor_nuevo || '—') + '</strong></div>'
                    : '')
              + '</div>';
        });
        $('#auditoria_body').html(html + '</div>');
    });
}

// ══════════════════════════════════════════════
// CL-I: CARGA MASIVA DE PERSONAL
// ══════════════════════════════════════════════
function abrirModalCargaMasiva() {
    $('#cm_accion').val('anadir');
    $('#cm_archivo').val('');
    $('#alerta_carga_masiva').html('');
    $('#cm_resultado').hide().html('');
    $('#modalCargaMasiva').modal('show');
}

var _cmFilasPendientes = null;
var _cmAccionPendiente = null;

$('#btn_procesar_carga_masiva').on('click', function () {
    var archivo = $('#cm_archivo')[0].files[0];
    var accion  = $('#cm_accion').val();
    $('#alerta_carga_masiva').html('');
    $('#cm_resultado').hide().html('');

    if (!archivo) {
        $('#alerta_carga_masiva').html('<div class="alert alert-warning mb-0">Selecciona un archivo.</div>');
        return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
        var wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
        var ws = wb.Sheets[wb.SheetNames[0]];
        var rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: true, defval: '' });

        var filas = [];
        rows.forEach(function (r) {
            var cedula = (r[0] !== undefined && r[0] !== null) ? String(r[0]).trim() : '';
            var nombre = (r[1] !== undefined && r[1] !== null) ? String(r[1]).trim() : '';
            var cupo   = (r[2] !== undefined && r[2] !== null && r[2] !== '') ? parseFloat(r[2]) : null;
            if (cedula) filas.push({ cedula: cedula, nombre: nombre, cupo: cupo });
        });

        if (!filas.length) {
            $('#alerta_carga_masiva').html('<div class="alert alert-warning mb-0">No se encontraron filas con cédula en el archivo.</div>');
            return;
        }

        // Confirmación dentro de la página (no confirm() nativo: Chrome lo
        // puede silenciar después de varios diálogos, sin avisar).
        var accionTxt = { anadir: 'Añadir empleados nuevos', actualizar_cupo: 'Actualizar cupo', bloquear: 'Bloquear empleados' }[accion];
        _cmFilasPendientes = filas;
        _cmAccionPendiente = accion;
        $('#alerta_carga_masiva').html(
            '<div class="alert alert-warning mb-0">'
            + 'Se van a procesar <strong>' + filas.length + '</strong> fila(s) — acción: "<strong>' + accionTxt + '</strong>" — para <strong>' + esc(_cliData.cli_descripcion) + '</strong>.'
            + '<div class="mt-2"><button type="button" class="btn btn-sm btn-danger" id="btn_confirmar_carga_masiva">Sí, continuar</button> '
            + '<button type="button" class="btn btn-sm btn-secondary" onclick="$(\'#alerta_carga_masiva\').html(\'\');">Cancelar</button></div>'
            + '</div>'
        );
    };
    reader.readAsArrayBuffer(archivo);
});

// Delegado porque el botón se inserta dinámicamente dentro de #alerta_carga_masiva
$(document).on('click', '#btn_confirmar_carga_masiva', function () {
    if (!_cmFilasPendientes || !_cmAccionPendiente) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.post('ajax/clientes/clientes.php?action=personal_carga_masiva', {
        cli_id: _cliId, accion: _cmAccionPendiente, filas: JSON.stringify(_cmFilasPendientes)
    }, function (res) {
        if (!res.success) {
            $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">' + (res.mensaje || 'Error al procesar') + '</div>');
            return;
        }
        var r = res.resultados;
        $('#alerta_carga_masiva').html('');
        var resumen = '<div class="alert alert-success mb-2">'
            + 'Agregados: <strong>' + r.agregados + '</strong> &nbsp;|&nbsp; '
            + 'Actualizados: <strong>' + r.actualizados + '</strong> &nbsp;|&nbsp; '
            + 'Bloqueados: <strong>' + r.bloqueados + '</strong> &nbsp;|&nbsp; '
            + 'Omitidos: <strong>' + r.omitidos.length + '</strong>'
            + '</div>';
        if (r.omitidos.length) {
            resumen += '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Cédula</th><th>Motivo</th></tr></thead><tbody>';
            r.omitidos.forEach(function (o) {
                resumen += '<tr><td>' + esc(o.cedula) + '</td><td>' + esc(o.motivo) + '</td></tr>';
            });
            resumen += '</tbody></table>';
        }
        $('#cm_resultado').html(resumen).show();
        _tabsLoaded['personal'] = false;
        cargarTabPersonal();
    }, 'json').fail(function () {
        $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
    }).always(function () {
        _cmFilasPendientes = null;
        _cmAccionPendiente = null;
    });
});

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

// ══════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════
$(document).ready(function() {
    cargarClientes();
});
</script>
