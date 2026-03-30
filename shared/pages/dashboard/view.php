<div class="content">
    <!--START PAGE CONTENT -->
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">DASHBOARD</h5>
                    <ul class="actions top-right">
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="btn btn-fab" data-toggle="dropdown" aria-expanded="false">
                                <i class="la la-ellipsis-h"></i>
                            </a>
                            <div class="dropdown-menu dropdown-icon-menu dropdown-menu-right">
                                <div class="dropdown-header">
                                    Acciones Rápidas
                                </div>
                                <a href="?module=dashboard" class="dropdown-item">
                                    <i class="icon dripicons-clockwise"></i> Recargar
                                </a>
                                <a href="#" class="dropdown-item">
                                    <i class="icon dripicons-help"></i> Soporte
                                </a>
                            </div>
                        </li>
                    </ul>
                    <div class="card-body">
                        <ul class="nav nav-pills nav-pills-info mb-3" id="pills-demo-2" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="pills-4-tab" data-toggle="pill" href="#pills-4" role="tab" aria-controls="pills-4" aria-selected="true"><i class="la la-bank"></i>Cartera</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-5-tab" data-toggle="pill" href="#pills-5" role="tab" aria-controls="pills-5" aria-selected="false"><i class="la la-cart-plus"></i>Ventas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-6-tab" data-toggle="pill" href="#pills-6" role="tab" aria-controls="pills-6" aria-selected="false"><i class="la la-bar-chart"></i>Estadísticas</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent-2">
                            <div class="tab-pane fade show active" id="pills-4" role="tabpanel" aria-labelledby="pills-4">
                                <!-- Start Cards Percentages -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="row m-0 col-border-xl">
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-accent float-left m-r-20">
                                                            <i class="icon dripicons-cart"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 append-percent counter" data-count="" id="sin_gestion_porcentaje">0</h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Gestiones En Espera del Mes
                                                        </h6>
                                                        <div class="progress progress-add-to-cart mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-accent" role="progressbar" style="" aria-valuenow="67" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter" data-count="100" id="sin_gestion">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-primary float-left m-r-20">
                                                            <i class="icon dripicons-graph-bar"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 append-percent counter" data-count="" id="exitosas_porcentaje">0</h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Gestiones Exitosas del Mes
                                                        </h6>
                                                        <div class="progress progress-active-sessions mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter" data-count="145" id="exitosas">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>


                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-info float-left m-r-20">
                                                            <i class="icon dripicons-mail"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 append-percent counter" data-count="" id="negativas_porcentaje">0</h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Gestiones Negativas del Mes
                                                        </h6>
                                                        <div class="progress progress-new-account mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="" aria-valuenow="83" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter" data-count="70" id="negativas">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-success float-left m-r-20">
                                                            <i class="la la-dollar f-w-600"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 counter append-percent" data-count="" id="pendientes_porcentaje">0</h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Gestiones Pendientes del Mes
                                                        </h6>
                                                        <div class="progress progress-total-revenue mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="" aria-valuenow="73" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter " data-count="73" id="pendientes">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Percentages -->
                                <!-- Start Cards Restore -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card-deck m-b-30">
                                            <div class="card">
                                                <h5 class="card-header border-none">$ Recuperado Cartera 30</h5>
                                                <div class="card-body p-0">
                                                    <h3 class="card-title text-info p-t-10 p-l-15" id="recuperado_30"></h3>
                                                    <div class="h-200">
                                                        <canvas id="usersChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card">
                                                <h5 class="card-header border-none">$ Recuperado Cartera 60</h5>
                                                <div class="card-body p-0">
                                                    <h3 class="card-title text-warning p-t-10 p-l-15" id="recuperado_60"></h3>
                                                    <div class="h-200">
                                                        <canvas id="bounceRateChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card">
                                                <h5 class="card-header border-none">$ Recuperado Cartera 90</h5>
                                                <div class="card-body p-0">
                                                    <h3 class="card-title text-primary p-t-10 p-l-15" id="recuperado_90"></h3>
                                                    <div class="h-200">
                                                        <canvas id="sessionDuration"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card">
                                                <h5 class="card-header border-none">$ Recuperado Cartera +90</h5>
                                                <div class="card-body p-0">
                                                    <h3 class="card-title text-success p-t-10 p-l-15" id="recuperado_91"></h3>
                                                    <div class="h-200">
                                                        <canvas id="cartera_90"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Restore -->
                                <!-- Start Cards Cartera -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="row m-0 col-border-xl">
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-success float-left m-r-20">
                                                            <i class="la la-dollar f-w-600"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 prepend-currency" data-count="" id="valor_cartera_30"></h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Consumos Cartera 30 días
                                                        </h6>
                                                        <div class="progress progress-cartera-30 mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter append-percent" data-count="" id="valor_cartera_30_porcentaje">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-info float-left m-r-20">
                                                            <i class="la la-dollar f-w-600"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 prepend-currency" data-count="" id="valor_cartera_60"></h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Consumos Cartera 60 días
                                                        </h6>
                                                        <div class="progress progress-cartera-60 mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter append-percent" data-count="" id="valor_cartera_60_porcentaje">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-secondary float-left m-r-20">
                                                            <i class="la la-dollar f-w-600"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 prepend-currency" data-count="" id="valor_cartera_90"></h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Consumos Cartera 90 días
                                                        </h6>
                                                        <div class="progress progress-cartera-90 mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-danger" role="progressbar" style="" aria-valuenow="73" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter append-percent" data-count="" id="valor_cartera_90_porcentaje">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-lg-6 col-xl-3">
                                                    <div class="card-body">
                                                        <div class="icon-rounded icon-rounded-primary float-left m-r-20">
                                                            <i class="la la-dollar f-w-600"></i>
                                                        </div>
                                                        <h5 class="card-title m-b-5 prepend-currency" data-count="" id="valor_cartera_91"></h5>
                                                        <h6 class="text-muted m-t-10">
                                                            Consumos Cartera +90 días
                                                        </h6>
                                                        <div class="progress progress-cartera-91 mt-4" style="height:7px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="" aria-valuenow="73" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted float-right m-t-5 mb-3 counter append-percent" data-count="" id="valor_cartera_91_porcentaje">
                                                            0
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Cartera -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <h5 class="card-header"></h5>
                                            <div class="card-body p-150">
                                                <h5 class="font-size-30">BALANCE FINAL</h5>
                                            </div>
                                            <div class="card-footer">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <h5 class="card-header">Porcentaje Cartera</h5>
                                            <div class="card-body p-10">
                                                <div id="total-revenue" style="height:290px"></div>
                                            </div>
                                            <div class="card-footer">
                                                <ul class="list-reset list-inline-block text-center">
                                                    <li class="text-muted text-info m-r-10"><i class="badge badge-info m-r-5  badge-circle w-10 h-10 "></i>$<span id="cobrado"></span></li>
                                                    <li class="text-muted text-accent m-r-10 "><i class="badge badge-accent m-r-5  badge-circle w-10 h-10 "></i> $<span id="por_cobrar"></span></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="pills-5" role="tabpanel" aria-labelledby="pills-5">
                                <!-- Start Cards Marks -->
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="card bg-info" id="totalVisitsChart">
                                            <div class="card-body p-0">
                                                <div class="card-toolbar top-right">
                                                    <ul class="nav nav-pills nav-pills-light justify-content-end" id="total-visits-tab" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" id="total-visits-link-1" data-toggle="pill" href="#total-visits-tab-1" role="tab" aria-controls="total-visits-tab-1" aria-selected="true">Semana</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-visits-link-2" data-toggle="pill" href="#total-visits-tab-2" role="tab" aria-controls="total-visits-tab-2" aria-selected="false">Mes</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-visits-link-3" data-toggle="pill" href="#total-visits-tab-3" role="tab" aria-controls="total-visits-tab-3" aria-selected="false">Año</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <h5 class="card-title border-none text-white p-l-20 p-t-20 m-b-0">Fridays</h5>
                                                <div class="tab-content" id="total-visits-tab-content">
                                                    <div class="tab-pane fade show active" id="total-visits-tab-1" role="tabpanel" aria-labelledby="total-visits-tab-1">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="fridays_semana">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-visits-tab-2" role="tabpanel" aria-labelledby="total-visits-tab-2">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="fridays_mes">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-visits-tab-3" role="tabpanel" aria-labelledby="total-visits-tab-3">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="fridays_anio">0</span>
                                                    </div>
                                                </div>
                                                <div class="ct-chart h-75 m-t-40"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="card bg-danger" id="totalUniqueVisitsChart">
                                            <div class="card-body p-0">
                                                <div class="card-toolbar top-right">
                                                    <ul class="nav nav-pills nav-pills-light justify-content-end" id="total-uniquevisits-tab" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" id="total-uniquevisits-link-1" data-toggle="pill" href="#total-uniquevisits-tab-1" role="tab" aria-controls="total-uniquevisits-tab-1" aria-selected="true">Semana</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-uniquevisits-link-2" data-toggle="pill" href="#total-uniquevisits-tab-2" role="tab" aria-controls="total-uniquevisits-tab-2" aria-selected="false">Mes</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-uniquevisits-link-3" data-toggle="pill" href="#total-uniquevisits-tab-3" role="tab" aria-controls="total-uniquevisits-tab-3" aria-selected="false">Año</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <h5 class="card-title border-none text-white p-l-20 p-t-20  m-b-0">Pizza Hut
                                                </h5>
                                                <div class="tab-content" id="total-uniquevisits-tab-content">
                                                    <div class="tab-pane fade show active" id="total-uniquevisits-tab-1" role="tabpanel" aria-labelledby="total-uniquevisits-tab-1">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="pizza_hut_semana">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-uniquevisits-tab-2" role="tabpanel" aria-labelledby="total-uniquevisits-tab-2">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="pizza_hut_mes">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-uniquevisits-tab-3" role="tabpanel" aria-labelledby="total-visits-tab-3">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20" data-count="" id="pizza_hut_anio">0</span>
                                                    </div>
                                                </div>

                                                <div class="ct-chart h-75 m-t-40"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="card bg-success" id="totalOther">
                                            <div class="card-body p-0">
                                                <div class="card-toolbar top-right">
                                                    <ul class="nav nav-pills nav-pills-light justify-content-end" id="total-uniquevisits-tab" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" id="total-other-link-1" data-toggle="pill" href="#total-other-tab-1" role="tab" aria-controls="total-uniquevisits-tab-1" aria-selected="true">Semana</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-other-link-2" data-toggle="pill" href="#total-other-tab-2" role="tab" aria-controls="total-uniquevisits-tab-2" aria-selected="false">Mes</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="total-other-link-3" data-toggle="pill" href="#total-other-tab-3" role="tab" aria-controls="total-uniquevisits-tab-3" aria-selected="false">Año</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <h5 class="card-title border-none text-white p-l-20 p-t-20  m-b-0">Otro
                                                </h5>
                                                <div class="tab-content" id="total-uniquevisits-tab-content">
                                                    <div class="tab-pane fade show active" id="total-other-tab-1" role="tabpanel" aria-labelledby="total-other-tab-1">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20 counter" data-count="">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-other-tab-2" role="tabpanel" aria-labelledby="total-other-tab-2">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20 counter" data-count="">0</span>
                                                    </div>
                                                    <div class="tab-pane fade" id="total-other-tab-3" role="tabpanel" aria-labelledby="other-tab-3">
                                                        <span class="card-title text-white font-size-40 font-w-300 p-l-20 counter" data-count="">0</span>
                                                    </div>
                                                </div>

                                                <div class="ct-chart h-75 m-t-40"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Marks -->
                                <!-- Start Cards Gestiones -->
                                <div class="row">
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="card">
                                            <div class="card-header">Últimos Pagos Registrados
                                                <ul class="actions top-right">
                                                    <li><a href="javascript:void(0)" data-q-action="card-expand"><i class="icon dripicons-expand-2"></i></a></li>
                                                </ul>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive" id="outer_pagos">
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="card">
                                            <div class="card-header"><span class="m-t-10 d-inline-block">Top Ten Mejores Clientes</span>
                                                <ul class="nav nav-pills nav-pills-primary float-right" id="pills-demo-sales" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" id="pills-month-tab" data-toggle="tab" href="#sales-month-tab" role="tab">Més</a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="tab-content" id="pills-tabContent-sales">
                                                    <div class="tab-pane fade show active" id="sales-month-tab" role="tabpanel" aria-labelledby="sales-month-tab" >
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Gestiones -->
                            </div>
                            <div class="tab-pane fade" id="pills-6" role="tabpanel" aria-labelledby="pills-6">
                                <!-- Start Cards Cobrado X Marca -->
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="card">
                                            <h5 class="card-header">Consumo por Marcas</h5>
                                            <div class="card-body">
                                                <canvas id="chartjs_lineChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Cobrado X Marca -->
                                <!-- Start Cards Cobrado X Deuda -->
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="card">
                                            <h5 class="card-header">Consumo por semanas</h5>
                                            <div class="card-body">
                                                <canvas id="chartjs_barChart"></canvas>
                                            </div>
                                            <div class="card-footer">
                                                <div class="row">
                                                    <div class="col-sm-2 offset-md-2">
                                                        <h5 class="font-size-20" id="mes3">Mes 1</h5>
                                                    </div>
                                                    <div class="col-sm-2 offset-md-2">
                                                        <h5 class="font-size-20" id="mes2">Mes 2</h5>
                                                    </div>
                                                    <div class="col-sm-2 offset-md-2">
                                                        <h5 class="font-size-20" id="mes1">Mes 3</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Cards Cobrado X Deuda -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </section>
    <!--END PAGE CONTENT -->
</div>
<script src="./assets/vendor/countup.js/dist/countUp.min.js"></script>
<script src="./assets/vendor/chart.js/dist/Chart.bundle.min.js"></script>
<script src="./assets/vendor/flot/jquery.flot.js"></script>
<script src="./assets/vendor/jquery.flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
<script src="./assets/vendor/flot/jquery.flot.resize.js"></script>
<script src="./assets/vendor/flot/jquery.flot.time.js"></script>
<script src="./assets/vendor/flot.curvedlines/curvedLines.js"></script>
<script src="./assets/vendor/datatables.net/js/jquery.dataTables.js"></script>
<script src="./assets/vendor/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>



<script src="./assets/js/cards/total-visits-chart.js"></script>
<script src="./assets/js/cards/total-unique-visits-chart.js"></script>
<script src="./assets/js/cards/other-chart.js"></script>

<script src="./assets/js/cards/traffic-sources.js"></script>
<script src="./assets/js/cards/deuda-chart.js"></script>

<script src="./assets/vendor/d3/dist/d3.min.js"></script>
<script src="./assets/vendor/flot/jquery.flot.time.js"></script>
<script src="./assets/vendor/echarts/echarts-all-3.js"></script>
<script src="./assets/vendor/c3/c3.min.js"></script>

<script src="./assets/vendor/chart.js/dist/Chart.bundle.min.js"></script>

<script src="./ajax/dashboard/cartera.js"></script>
<script src="./ajax/dashboard/ventas.js"></script>
<script src="./ajax/dashboard/estadisticas.js"></script>


<script src="./assets/js/charts/chartjs-init.js"></script>