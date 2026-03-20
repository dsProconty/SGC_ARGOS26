<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header" style="background-color: #950d1a; color: white;">INGRESO DE NUEVA GESTIÓN</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <h5>Datos de Cliente</h5>
                                <input type="hidden" name="id_cartera" id="id_cartera" value="<?php echo $_GET['id']; ?>">
                                <input type="hidden" name="id_cliente" id="id_cliente">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <label for="">Cliente</label>
                                        <input type="text" name="nombre_cliente" id="nombre_cliente" readonly class="form-control" placeholder="Cliente">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="">Ciudad</label>
                                        <input type="text" name="ciudad" id="ciudad" readonly class="form-control" placeholder="Ciudad">
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="">Día de Corte</label>
                                        <input type="text" name="dia_corte" id="dia_corte" readonly class="form-control" placeholder="Día de Corte">
                                    </div>

                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5>Datos de Contacto</h5>
                                <div class="container">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <label for="">Contacto</label>
                                            <input type="text" name="contacto" id="contacto" readonly class="form-control" placeholder="Contacto">
                                        </div>

                                        <div class="col-sm-6">
                                            <label for="">Email</label>
                                            <input type="text" name="email" id="email" readonly class="form-control" placeholder="Email">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="">Email 2</label>
                                            <input type="text" name="email2" id="email2" readonly class="form-control" placeholder="Email 2">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="">Teléfono</label>
                                            <input type="text" name="telefono" id="telefono" readonly class="form-control" placeholder="Teléfono">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="">Teléfono 2</label>
                                            <input type="text" name="telefono2" id="telefono2" readonly class="form-control" placeholder="Teléfono 2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <h5>Deuda</h5>
                                <div class="container">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <label for="">Total Cartera:</label>
                                            <input type="text" name="total_deuda" id="total_deuda" readonly class="form-control" placeholder="Total">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5>Cartera</h5>
                                <div class="container">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="">Fecha Inicio:</label>
                                            <input type="text" name="fecha_inicio" id="fecha_inicio" readonly class="form-control">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="">Fecha Fín:</label>
                                            <input type="text" name="fecha_fin" id="fecha_fin" readonly class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row"><br></div>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <form action="" id="form_gestion">
                                        <h5 class="card-header"><strong> Gestión</strong></h5>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <label for="">Tipo de Gestión</label>
                                                            <select class="form-control" id="tipo_gestion" name="tipo_gestion">
                                                                <option value="0">Selecciona un tipo de gestión</option>
                                                                <option value="telefonica">Telefónica</option>
                                                                <option value="correo">Correo</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <label for="">Tipo de Contacto</label>
                                                            <select class="form-control" id="tipo_contacto" name="tipo_contacto">
                                                                <option value="0">Selecciona un tipo de contacto</option>
                                                                <option value="contactado">Contactado</option>
                                                                <option value="no_contactado">No Contactado</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <label for="">Respuesta</label>
                                                            <select class="form-control" id="respuesta" name="respuesta">
                                                                <option value="0">Selecciona una respuesta</option>
                                                                <option value="pago">Registro de Pago</option>
                                                                <option value="compromiso">Compromiso de Pago</option>
                                                                <option value="no_contactado">No Contactado</option>
                                                                <option value="notificacion">Notificación</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-6" id="telefono_contacto">
                                                            <label for="">Número de Contacto</label>
                                                            <input type="number" class="form-control" placeholder="Número de contacto" id="numero_contacto" name="numero_contacto">
                                                        </div>
                                                        <div class="col-lg-6" style="display:none" id="email_form">
                                                            <label for="">Email de Contacto</label>
                                                            <input type="email" class="form-control" placeholder="Email de contacto" id="email_contacto" name="email_contacto">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="col-sm-12">
                                                        <label for="">Observación</label>
                                                        <textarea name="observacion_gestion" id="observacion_gestion" class="form-control" rows="5" required></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <span style="display:none" id="form_pago">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <h5><strong>Registro de Pago</strong></h5>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="">Monto:</label>
                                                        <input type="number" name="monto" id="monto" class="form-control">
                                                    </div>
                                                    <div class="col-sm-9">
                                                        <label for="">Observación Pago:</label>
                                                        <textarea name="observacion" id="observacion" cols="30" rows="5" class="form-control"></textarea>
                                                    </div>
                                                </div>
                                            </span>
                                            <span style="display:none" id="form_compromiso">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <h5><strong>Compromiso de Pago</strong></h5>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="">Monto Compromiso:</label>
                                                        <input type="number" name="monto_compromiso" id="monto_compromiso" class="form-control">
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="">Fecha Compromiso:</label>
                                                        <input type="date" name="fecha_compromiso" id="fecha_compromiso" class="form-control">
                                                    </div>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="card-footer">
                                            <div class="row">
                                                <div class="col-sm-4 offset-lg-5">
                                                    <button type="submit" class="btn btn-primary"><i class="icon dripicons-checkmark" style="color:white;"></i>Guardar</button>
                                                    <a href="?module=gestiones&cartera=30" class="btn btn-warning"><i class="icon dripicons-forward" style="color:white;"></i>Cancelar</a>
                                                </div>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="row"><br></div>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <ul class="nav nav-pills nav-pills-info mb-3" id="pills-demo-2" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="pills-4-tab" data-toggle="pill" href="#pills-consumos" role="tab" aria-controls="pills-4" aria-selected="true"><i class="la la-cart-arrow-down"></i>Consumo</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="pills-5-tab" data-toggle="pill" href="#pills-gestiones" role="tab" aria-controls="pills-5" aria-selected="false"><i class="la la-file"></i>Gestiones</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="pills-6-tab" data-toggle="pill" href="#pills-pagos" role="tab" aria-controls="pills-6" aria-selected="false"><i class="la la-usd"></i>Pagos</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent-2">
                                                <div class="tab-pane fade show active" id="pills-consumos" role="tabpanel" aria-labelledby="pills-4">
                                                    <h5 class="card-header">Detalle de Consumos</h5>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="table-responsive" id="outer_consumos">

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="pills-gestiones" role="tabpanel" aria-labelledby="pills-5">
                                                    <h5 class="card-header">Detalle de Gestiones</h5>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="table-responsive" id="outer_gestiones">

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="pills-pagos" role="tabpanel" aria-labelledby="pills-6">
                                                    <h5 class="card-header">Detalle de Pagos</h5>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="table-responsive" id="outer_pagos">

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
<script src="js/form_gestion.js"></script>