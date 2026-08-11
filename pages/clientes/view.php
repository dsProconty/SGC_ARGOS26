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
                        <button class="btn btn-sm btn-outline-secondary" onclick="abrirModalCargaMasiva()">
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
                        <div class="col-md-4" id="col_valor_beneficio">
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
                    <div class="row" id="row_modo_cupo" style="display:none;">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modo de cupo</label>
                                <select class="form-control" id="cli_modo_cupo" name="cli_modo_cupo">
                                    <option value="global">Global (compartido entre marcas)</option>
                                    <option value="marca">Por marca (independiente por marca)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="row_cupo_por_marca" style="display:none;">
                        <div class="col-12">
                            <label class="mb-1">Monto máximo por marca</label>
                            <div id="cupo_marca_inputs" class="row"></div>
                            <input type="hidden" id="cupo_por_marca_input" name="cupo_por_marca" value="{}">
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
     3 pantallas dentro del mismo modal: Configurar → Revisar → Resultado.
══════════════════════════════════════════════════ -->
<style>
    #modalCargaMasiva .cm-steps{ display:flex; align-items:center; gap:8px; padding:2px 0 16px; }
    #modalCargaMasiva .cm-step{ display:flex; align-items:center; gap:7px; font-size:.78rem; color:#97a3b1; font-weight:600; white-space:nowrap; }
    #modalCargaMasiva .cm-step .dot{ width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.68rem; border:1.5px solid #d9dee4; color:#97a3b1; flex:0 0 auto; }
    #modalCargaMasiva .cm-step.now{ color:#495057; }
    #modalCargaMasiva .cm-step.now .dot{ background:#950d1a; border-color:#950d1a; color:#fff; }
    #modalCargaMasiva .cm-step.done{ color:#495057; }
    #modalCargaMasiva .cm-step.done .dot{ background:#fff; border-color:#950d1a; color:#950d1a; }
    #modalCargaMasiva .cm-step-line{ flex:1; height:1.5px; background:#e1e5e9; min-width:14px; }
    #modalCargaMasiva .cm-step-line.done{ background:#c7ccd1; }

    #modalCargaMasiva .cm-screen{ display:none; }
    #modalCargaMasiva .cm-screen.active{ display:block; }

    #modalCargaMasiva .cm-field-label{ font-size:.72rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#8a97a5; margin:0 0 10px; }
    #modalCargaMasiva .cm-section + .cm-section{ margin-top:22px; }

    #modalCargaMasiva .cm-tiles{ display:flex; gap:10px; flex-wrap:wrap; }
    #modalCargaMasiva .cm-tile{ flex:1 1 180px; border:1.5px solid #e1e5e9; border-radius:8px; padding:12px; text-align:left; background:#fff; cursor:pointer; }
    #modalCargaMasiva .cm-tile .ic{ width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:#f4f5f7; color:#8a97a5; margin-bottom:8px; }
    #modalCargaMasiva .cm-tile .t{ font-size:.85rem; font-weight:700; display:block; color:#495057; }
    #modalCargaMasiva .cm-tile .d{ font-size:.73rem; color:#8a97a5; margin-top:3px; line-height:1.4; display:block; }
    #modalCargaMasiva .cm-tile.sel{ border-color:#950d1a; background:#f7ecf0; }
    #modalCargaMasiva .cm-tile.sel .ic{ background:#950d1a; color:#fff; }
    #modalCargaMasiva .cm-tile.sel .t{ color:#950d1a; }
    #modalCargaMasiva .cm-tile:not(.sel):hover{ border-color:#c7ccd1; }

    #modalCargaMasiva .cm-dropzone{ border:1.5px dashed #d9dee4; border-radius:8px; padding:18px; text-align:center; background:#fafbfc; cursor:pointer; }
    #modalCargaMasiva .cm-dropzone.dragover{ border-color:#950d1a; background:#f7ecf0; }
    #modalCargaMasiva .cm-dropzone .ic{ color:#950d1a; margin-bottom:4px; font-size:1.4rem; }
    #modalCargaMasiva .cm-dropzone p{ margin:0; font-size:.82rem; color:#8a97a5; }
    #modalCargaMasiva .cm-dropzone .browse{ color:#950d1a; font-weight:700; text-decoration:underline; }

    #modalCargaMasiva .cm-file-chip{ margin-top:10px; display:none; align-items:center; gap:9px; background:#fff; border:1px solid #e1e5e9; border-radius:7px; padding:8px 10px 8px 12px; font-size:.8rem; }
    #modalCargaMasiva .cm-file-chip.show{ display:flex; }
    #modalCargaMasiva .cm-file-chip .name{ flex:1; font-weight:600; color:#495057; word-break:break-all; }
    #modalCargaMasiva .cm-file-chip .x{ background:none; border:0; color:#8a97a5; cursor:pointer; font-size:1rem; line-height:1; padding:2px; }

    #modalCargaMasiva .cm-help-row{ margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 13px; background:#f4f5f7; border:1px solid #e1e5e9; border-radius:7px; flex-wrap:wrap; }
    #modalCargaMasiva .cm-help-row .txt{ font-size:.76rem; color:#8a97a5; line-height:1.5; }
    #modalCargaMasiva .cm-help-row .txt b{ color:#495057; }
    #modalCargaMasiva .cm-help-row .btn{ flex:0 0 auto; }

    #modalCargaMasiva .cm-stats{ display:flex; gap:10px; }
    #modalCargaMasiva .cm-stat{ flex:1; border:1px solid #e1e5e9; border-radius:8px; padding:11px 13px; background:#fafbfc; }
    #modalCargaMasiva .cm-stat .n{ font-size:1.35rem; font-weight:700; color:#495057; line-height:1; }
    #modalCargaMasiva .cm-stat .l{ font-size:.71rem; color:#8a97a5; margin-top:4px; font-weight:600; }
    #modalCargaMasiva .cm-stat.apply{ background:#f7ecf0; border-color:#e6cfd8; }
    #modalCargaMasiva .cm-stat.apply .n{ color:#950d1a; }

    #modalCargaMasiva .cm-ctx-line{ font-size:.8rem; color:#8a97a5; margin:14px 0 10px; }
    #modalCargaMasiva .cm-ctx-line b{ color:#495057; }

    #modalCargaMasiva .cm-pill{ display:inline-block; padding:3px 9px; border-radius:999px; font-size:.71rem; font-weight:700; white-space:nowrap; }
    #modalCargaMasiva .cm-pill-apply{ background:#f7ecf0; color:#950d1a; }
    #modalCargaMasiva .cm-pill-skip{ background:#eef0f2; color:#8a97a5; }

    #modalCargaMasiva .cm-result-banner{ display:flex; align-items:center; gap:12px; padding:13px 15px; border-radius:8px; background:#f7ecf0; border:1px solid #e6cfd8; margin-bottom:16px; }
    #modalCargaMasiva .cm-result-banner .ic{ width:32px; height:32px; border-radius:50%; background:#950d1a; color:#fff; display:flex; align-items:center; justify-content:center; flex:0 0 auto; font-size:1rem; }
    #modalCargaMasiva .cm-result-banner .t{ font-size:.9rem; font-weight:700; color:#495057; }
    #modalCargaMasiva .cm-result-banner .s{ font-size:.76rem; color:#8a97a5; margin-top:1px; }

    #modalCargaMasiva .cm-stats4{ display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    #modalCargaMasiva .cm-stats4 .cm-stat{ min-width:110px; }
</style>
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="icon dripicons-upload"></i> Carga Masiva de Personal</h5>
                    <p class="mb-0 text-muted" id="cm_cliente_nombre" style="font-size:.8rem;"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                <div class="cm-steps">
                    <div class="cm-step" id="cm_step1"><span class="dot">1</span>Configurar</div>
                    <div class="cm-step-line" id="cm_line1"></div>
                    <div class="cm-step" id="cm_step2"><span class="dot">2</span>Revisar</div>
                    <div class="cm-step-line" id="cm_line2"></div>
                    <div class="cm-step" id="cm_step3"><span class="dot">3</span>Resultado</div>
                </div>

                <div id="alerta_carga_masiva"></div>

                <!-- ═══ PANTALLA 1: CONFIGURAR ═══ -->
                <div class="cm-screen active" id="cm_screen_1">
                    <div class="cm-section">
                        <p class="cm-field-label">¿Qué querés hacer?</p>
                        <div class="cm-tiles">
                            <button type="button" class="cm-tile sel" data-accion="anadir">
                                <span class="ic"><i class="icon dripicons-user-group"></i></span>
                                <span class="t">Añadir empleados</span>
                                <span class="d">Crea empleados nuevos con un cupo inicial.</span>
                            </button>
                            <button type="button" class="cm-tile" data-accion="actualizar_cupo">
                                <span class="ic"><i class="icon dripicons-wallet"></i></span>
                                <span class="t">Actualizar cupo</span>
                                <span class="d">Cambia el cupo de empleados que ya existen.</span>
                            </button>
                            <button type="button" class="cm-tile" data-accion="bloquear">
                                <span class="ic"><i class="icon dripicons-lock"></i></span>
                                <span class="t">Bloquear empleados</span>
                                <span class="d">Bloquea el acceso de empleados existentes.</span>
                            </button>
                        </div>
                        <input type="hidden" id="cm_accion" value="anadir">
                    </div>

                    <div class="cm-section">
                        <p class="cm-field-label">Archivo</p>
                        <div class="cm-dropzone" id="cm_dropzone">
                            <div class="ic"><i class="icon dripicons-cloud-upload"></i></div>
                            <p>Arrastrá tu Excel o CSV aquí, o <span class="browse">buscalo en tu computador</span></p>
                        </div>
                        <input type="file" id="cm_archivo" accept=".xlsx,.xls,.csv" style="display:none;">
                        <div class="cm-file-chip" id="cm_file_chip">
                            <i class="icon dripicons-document" style="color:#950d1a;"></i>
                            <span class="name" id="cm_file_name"></span>
                            <button type="button" class="x" id="cm_file_quitar">&times;</button>
                        </div>

                        <div class="cm-help-row">
                            <p class="txt mb-0"><b>Columna A</b> cédula · <b>B</b> nombre completo · <b>C</b> cupo (solo para Añadir / Actualizar). El encabezado es opcional, si lo incluyes se detecta y se omite solo.</p>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="descargarPlantillaCargaMasiva()">
                                <i class="icon dripicons-download"></i> Plantilla de ejemplo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═══ PANTALLA 2: REVISAR CAMBIOS ═══ -->
                <div class="cm-screen" id="cm_screen_2">
                    <div class="cm-stats">
                        <div class="cm-stat"><div class="n" id="cm_stat_total">0</div><div class="l">Filas leídas</div></div>
                        <div class="cm-stat apply"><div class="n" id="cm_stat_aplican">0</div><div class="l" id="cm_stat_aplican_label">Se van a aplicar</div></div>
                        <div class="cm-stat"><div class="n" id="cm_stat_omiten">0</div><div class="l">Sin cambios</div></div>
                    </div>
                    <p class="cm-ctx-line" id="cm_ctx_line"></p>
                    <div style="max-height:32vh; overflow-y:auto; border:1px solid #e1e5e9; border-radius:8px;">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light" style="position:sticky; top:0;">
                                <tr><th>Cédula</th><th>Nombre (archivo)</th><th>Estado actual</th><th>Resultado</th></tr>
                            </thead>
                            <tbody id="cm_preview_tbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- ═══ PANTALLA 3: RESULTADO ═══ -->
                <div class="cm-screen" id="cm_screen_3">
                    <div class="cm-result-banner">
                        <div class="ic"><i class="icon dripicons-checkmark"></i></div>
                        <div>
                            <div class="t" id="cm_result_titulo"></div>
                            <div class="s" id="cm_result_sub"></div>
                        </div>
                    </div>
                    <div class="cm-stats4">
                        <div class="cm-stat"><div class="n" id="cm_res_agregados">0</div><div class="l">Agregados</div></div>
                        <div class="cm-stat"><div class="n" id="cm_res_actualizados">0</div><div class="l">Actualizados</div></div>
                        <div class="cm-stat"><div class="n" id="cm_res_bloqueados">0</div><div class="l">Bloqueados</div></div>
                        <div class="cm-stat apply"><div class="n" id="cm_res_omitidos">0</div><div class="l">Omitidos</div></div>
                    </div>
                    <div id="cm_omitidos_box" style="display:none; border:1px solid #e1e5e9; border-radius:8px; overflow:hidden;">
                        <div style="padding:9px 12px; background:#f4f5f7; font-size:.78rem; font-weight:700; color:#495057;">Filas omitidas</div>
                        <table class="table table-sm mb-0"><thead><tr><th>Cédula</th><th>Motivo</th></tr></thead><tbody id="cm_omitidos_tbody"></tbody></table>
                    </div>
                </div>

            </div>
            <div class="modal-footer" id="cm_footer_1">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_procesar_carga_masiva">
                    Analizar archivo <i class="icon dripicons-arrow-thin-right"></i>
                </button>
            </div>
            <div class="modal-footer" id="cm_footer_2" style="display:none;">
                <button type="button" class="btn btn-secondary" id="btn_cm_atras">
                    <i class="icon dripicons-arrow-thin-left"></i> Atrás
                </button>
                <button type="button" class="btn btn-danger" id="btn_confirmar_carga_masiva">
                    <i class="icon dripicons-checkmark"></i> <span id="btn_confirmar_carga_masiva_txt">Confirmar y aplicar</span>
                </button>
            </div>
            <div class="modal-footer" id="cm_footer_3" style="display:none;">
                <button type="button" class="btn btn-secondary" id="btn_cm_otra_carga">Hacer otra carga</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
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
    $('#cli_modo_cupo').val('global');
    $('#cupo_marca_inputs').html('');
    $('#cupo_por_marca_input').val('{}');
    toggleModoCupoUI();
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
        $('#cli_modo_cupo').val(d.cli_modo_cupo || 'global');
        $('#cupo_marca_inputs').html('');
        $('#cupo_por_marca_input').val('{}');
        toggleModoCupoUI();
        if (d.cli_modo_cupo === 'marca') {
            renderCupoPorMarcaInputs(d.cupo_por_marca || {});
        }
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

var _marcasCatalogo = null; // cache: [{mar_id, mar_descripcion}, ...]

function cargarMarcasCatalogo(callback) {
    if (_marcasCatalogo) { callback(_marcasCatalogo); return; }
    $.getJSON('ajax/locales/locales.php', { action: 'list_marcas' }, function (res) {
        _marcasCatalogo = (res && res.success) ? res.data : [];
        callback(_marcasCatalogo);
    }).fail(function () {
        _marcasCatalogo = [];
        callback(_marcasCatalogo);
    });
}

function renderCupoPorMarcaInputs(montosExistentes) {
    // montosExistentes may be {} , [] (PHP empty-array quirk), or {"3":50,...}
    if (!montosExistentes || Array.isArray(montosExistentes)) montosExistentes = {};
    cargarMarcasCatalogo(function (marcas) {
        var html = '';
        marcas.forEach(function (m) {
            var valor = montosExistentes[m.mar_id] || '';
            html += '<div class="col-md-4 mb-2">'
                + '<label class="small mb-1">' + esc(m.mar_descripcion) + '</label>'
                + '<div class="input-group input-group-sm">'
                + '<div class="input-group-prepend"><span class="input-group-text">$</span></div>'
                + '<input type="number" class="form-control cupo-marca-input" data-mar-id="' + m.mar_id + '" min="0" step="0.01" value="' + valor + '" placeholder="0.00">'
                + '</div></div>';
        });
        $('#cupo_marca_inputs').html(html);
    });
}

function leerCupoPorMarcaInputs() {
    var out = {};
    $('#cupo_marca_inputs .cupo-marca-input').each(function () {
        var marId = $(this).data('mar-id');
        var val   = parseFloat($(this).val());
        if (val > 0) out[marId] = val;
    });
    return out;
}

function toggleModoCupoUI() {
    var esCupo  = $('#cli_tipo_beneficio').val() === 'Cupo';
    var esMarca = $('#cli_modo_cupo').val() === 'marca';
    $('#row_modo_cupo').toggle(esCupo);
    $('#row_cupo_por_marca').toggle(esCupo && esMarca);
    // El campo único "Valor del beneficio" solo tiene sentido cuando NO es modo marca
    // (para Porcentaje siempre se muestra; para Cupo+marca se oculta a favor de los inputs por marca).
    $('#col_valor_beneficio').toggle(!(esCupo && esMarca));
}

$('#cli_tipo_beneficio, #cli_modo_cupo').on('change', function() {
    toggleModoCupoUI();
    // Si el usuario acaba de activar "Por marca" y aún no hay inputs renderizados
    // (cliente nuevo, o cliente que no tenía modo marca), generarlos vacíos.
    if ($('#cli_tipo_beneficio').val() === 'Cupo' && $('#cli_modo_cupo').val() === 'marca'
        && $('#cupo_marca_inputs').children().length === 0) {
        renderCupoPorMarcaInputs({});
    }
});

$('#cli_tipo_beneficio').on('change', function() { actualizarPrefijo($(this).val()); });

$('#formCliente').on('submit', function(e) {
    e.preventDefault();
    var id     = $('#cli_id').val();
    var action = id ? 'editar' : 'crear';
    var btn    = $('#btn_guardar');
    $('#cupo_por_marca_input').val(JSON.stringify(leerCupoPorMarcaInputs()));
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
var ACCION_LABEL = { anadir: 'Añadir empleados nuevos', actualizar_cupo: 'Actualizar cupo', bloquear: 'Bloquear empleados' };
var ACCION_CONFIRMA_TXT = { anadir: 'Confirmar y añadir', actualizar_cupo: 'Confirmar y actualizar', bloquear: 'Confirmar y bloquear' };

function abrirModalCargaMasiva() {
    $('#cm_cliente_nombre').text(_cliData ? _cliData.cli_descripcion : '');
    $('.cm-tile').removeClass('sel');
    $('.cm-tile[data-accion="anadir"]').addClass('sel');
    $('#cm_accion').val('anadir');
    $('#cm_archivo').val('');
    $('#cm_file_chip').removeClass('show');
    $('#cm_file_name').text('');
    $('#alerta_carga_masiva').html('');
    _cmFilasPendientes = null;
    _cmAccionPendiente = null;
    cmGoScreen(1);
    $('#modalCargaMasiva').modal('show');
}

// Navegación entre las 3 pantallas del modal (Configurar → Revisar → Resultado)
function cmGoScreen(n) {
    $('.cm-screen').removeClass('active');
    $('#cm_screen_' + n).addClass('active');
    $('#cm_footer_1, #cm_footer_2, #cm_footer_3').hide();
    $('#cm_footer_' + n).show();

    [1, 2, 3].forEach(function (i) {
        var $step = $('#cm_step' + i);
        $step.removeClass('now done');
        if (i < n) $step.addClass('done');
        else if (i === n) $step.addClass('now');
    });
    $('#cm_line1').toggleClass('done', n > 1);
    $('#cm_line2').toggleClass('done', n > 2);
}

// Selección de acción mediante tarjetas (reemplaza el <select> anterior)
$(document).on('click', '.cm-tile', function () {
    $('.cm-tile').removeClass('sel');
    $(this).addClass('sel');
    $('#cm_accion').val($(this).data('accion'));
});

// Dropzone: clic o arrastrar-y-soltar abren/reciben el archivo
$('#cm_dropzone').on('click', function () { $('#cm_archivo').trigger('click'); });
$('#cm_dropzone').on('dragover', function (e) { e.preventDefault(); $(this).addClass('dragover'); });
$('#cm_dropzone').on('dragleave', function () { $(this).removeClass('dragover'); });
$('#cm_dropzone').on('drop', function (e) {
    e.preventDefault();
    $(this).removeClass('dragover');
    var f = e.originalEvent.dataTransfer.files[0];
    if (f) {
        var dt = new DataTransfer();
        dt.items.add(f);
        $('#cm_archivo')[0].files = dt.files;
        $('#cm_archivo').trigger('change');
    }
});
$('#cm_archivo').on('change', function () {
    var f = this.files[0];
    if (f) { $('#cm_file_name').text(f.name); $('#cm_file_chip').addClass('show'); }
});
$('#cm_file_quitar').on('click', function (e) {
    e.stopPropagation();
    $('#cm_archivo').val('');
    $('#cm_file_chip').removeClass('show');
});

// Plantilla de ejemplo con encabezados (columna A cédula, B nombre, C cupo)
// más filas de muestra. El procesador no requiere encabezados, pero si la
// primera fila trae uno (texto sin dígitos en la columna cédula) se
// detecta y se omite solo automáticamente al leer el archivo — ver más
// abajo en el manejador de #btn_procesar_carga_masiva.
function descargarPlantillaCargaMasiva() {
    var datos = [
        ['Cédula', 'Nombre completo', 'Cupo'],
        ['0102030405', 'Juan Pérez Ejemplo', 50],
        ['0607080910', 'María Gómez Ejemplo', 30],
        ['1112131415', 'Carlos Torres Ejemplo', 100]
    ];
    var ws = XLSX.utils.aoa_to_sheet(datos);
    ws['!cols'] = [{ wch: 14 }, { wch: 28 }, { wch: 10 }];
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Personal');
    XLSX.writeFile(wb, 'plantilla_carga_masiva_personal.xlsx');
}

var _cmFilasPendientes = null;
var _cmAccionPendiente = null;

$('#btn_procesar_carga_masiva').on('click', function () {
    var archivo = $('#cm_archivo')[0].files[0];
    var accion  = $('#cm_accion').val();
    $('#alerta_carga_masiva').html('');

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
        rows.forEach(function (r, idx) {
            var cedula = (r[0] !== undefined && r[0] !== null) ? String(r[0]).trim() : '';
            var nombre = (r[1] !== undefined && r[1] !== null) ? String(r[1]).trim() : '';
            var cupo   = (r[2] !== undefined && r[2] !== null && r[2] !== '') ? parseFloat(r[2]) : null;
            // Si la primera fila trae un encabezado (ej. "Cédula", sin dígitos),
            // se detecta y se omite automáticamente — no hace falta borrarla.
            if (idx === 0 && cedula && !/\d/.test(cedula)) return;
            // Si en Excel la columna cédula quedó con formato numérico (no Texto),
            // al escribir un valor que empieza en 0 (ej. 0602345678) Excel lo
            // guarda como número y pierde el/los cero(s) inicial(es). La cédula
            // ecuatoriana siempre tiene 10 dígitos, así que se recompone
            // rellenando con ceros a la izquierda antes de buscarla.
            if (cedula && /^\d+$/.test(cedula) && cedula.length < 10) {
                cedula = cedula.padStart(10, '0');
            }
            if (cedula) filas.push({ cedula: cedula, nombre: nombre, cupo: cupo });
        });

        if (!filas.length) {
            $('#alerta_carga_masiva').html('<div class="alert alert-warning mb-0">No se encontraron filas con cédula en el archivo.</div>');
            return;
        }

        _cmFilasPendientes = filas;
        _cmAccionPendiente = accion;
        $('#alerta_carga_masiva').html('<div class="text-center p-2"><span class="spinner-border spinner-border-sm"></span> Analizando archivo...</div>');

        // Preview: se le pide al backend que evalúe fila por fila (existe,
        // estado actual, qué haría) SIN guardar nada todavía, para mostrarlo
        // antes de aplicar cualquier cambio real.
        $.post('ajax/clientes/clientes.php?action=personal_carga_masiva', {
            cli_id: _cliId, accion: accion, filas: JSON.stringify(filas), solo_preview: 1
        }, function (res) {
            if (!res.success) {
                $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">' + (res.mensaje || 'Error al analizar el archivo') + '</div>');
                return;
            }
            renderPreviewCargaMasiva(res);
        }, 'json').fail(function () {
            $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
        });
    };
    reader.readAsArrayBuffer(archivo);
});

function renderPreviewCargaMasiva(res) {
    $('#cm_stat_total').text(res.total);
    $('#cm_stat_aplican').text(res.aplicaran);
    $('#cm_stat_omiten').text(res.total - res.aplicaran);
    $('#cm_stat_aplican_label').text(_cmAccionPendiente === 'bloquear' ? 'Se van a bloquear' : 'Se van a aplicar');
    $('#cm_ctx_line').html('Acción: <b>' + ACCION_LABEL[_cmAccionPendiente] + '</b> · Archivo analizado');

    var filas = '';
    res.detalle.forEach(function (d) {
        filas += '<tr' + (d.aplica ? '' : ' class="table-light text-muted"') + '>'
            + '<td>' + esc(d.cedula) + '</td>'
            + '<td>' + esc(d.nombre || '—') + '</td>'
            + '<td>' + esc(d.estado_actual || '—') + '</td>'
            + '<td><span class="cm-pill ' + (d.aplica ? 'cm-pill-apply' : 'cm-pill-skip') + '">' + esc(d.resultado) + '</span></td>'
            + '</tr>';
    });
    $('#cm_preview_tbody').html(filas);

    $('#btn_confirmar_carga_masiva_txt').text(ACCION_CONFIRMA_TXT[_cmAccionPendiente]);
    $('#btn_confirmar_carga_masiva')
        .prop('disabled', res.aplicaran === 0)
        .toggleClass('btn-danger', _cmAccionPendiente === 'bloquear')
        .toggleClass('btn-primary', _cmAccionPendiente !== 'bloquear');

    cmGoScreen(2);
}

$('#btn_cm_atras').on('click', function () { cmGoScreen(1); });

$('#btn_confirmar_carga_masiva').on('click', function () {
    if (!_cmFilasPendientes || !_cmAccionPendiente) return;
    var btn = $(this);
    var txtOriginal = $('#btn_confirmar_carga_masiva_txt').text();
    btn.prop('disabled', true);
    $('#btn_confirmar_carga_masiva_txt').html('<span class="spinner-border spinner-border-sm"></span> Aplicando...');

    $.post('ajax/clientes/clientes.php?action=personal_carga_masiva', {
        cli_id: _cliId, accion: _cmAccionPendiente, filas: JSON.stringify(_cmFilasPendientes)
    }, function (res) {
        if (!res.success) {
            btn.prop('disabled', false);
            $('#btn_confirmar_carga_masiva_txt').text(txtOriginal);
            $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">' + (res.mensaje || 'Error al procesar') + '</div>');
            return;
        }
        var r = res.resultados;
        var totalAplicado = r.agregados + r.actualizados + r.bloqueados;
        $('#cm_result_titulo').text(totalAplicado === 1 ? '1 fila aplicada correctamente' : totalAplicado + ' filas aplicadas correctamente');
        $('#cm_result_sub').text((_cliData ? _cliData.cli_descripcion : '') + ' · ' + ACCION_LABEL[_cmAccionPendiente]);
        $('#cm_res_agregados').text(r.agregados);
        $('#cm_res_actualizados').text(r.actualizados);
        $('#cm_res_bloqueados').text(r.bloqueados);
        $('#cm_res_omitidos').text(r.omitidos.length);

        if (r.omitidos.length) {
            var tbody = '';
            r.omitidos.forEach(function (o) {
                tbody += '<tr><td>' + esc(o.cedula) + '</td><td>' + esc(o.motivo) + '</td></tr>';
            });
            $('#cm_omitidos_tbody').html(tbody);
            $('#cm_omitidos_box').show();
        } else {
            $('#cm_omitidos_box').hide();
        }

        cmGoScreen(3);
        _tabsLoaded['personal'] = false;
        cargarTabPersonal();
    }, 'json').fail(function () {
        btn.prop('disabled', false);
        $('#btn_confirmar_carga_masiva_txt').text(txtOriginal);
        $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
    });
});

$('#btn_cm_otra_carga').on('click', function () {
    $('#cm_archivo').val('');
    $('#cm_file_chip').removeClass('show');
    _cmFilasPendientes = null;
    _cmAccionPendiente = null;
    cmGoScreen(1);
});

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

// ══════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════
$(document).ready(function() {
    cargarClientes();
});
</script>
