<?php
date_default_timezone_set('America/Guayaquil');
require_once "../../config/database.php";

$action = $_GET['action'];

switch ($action) {
    case 'consumos_semana':

        $marca = $_GET['marca'];

        $diaSemana = date("w") - 1;
        # Calcular el tiempo (no la fecha) de cuándo fue el inicio de semana
        $tiempoDeInicioDeSemana = strtotime("-" . $diaSemana . " days"); # Restamos -X days
        # Y formateamos ese tiempo
        $fechaInicioSemana = date("Y-m-d", $tiempoDeInicioDeSemana);
        # Ahora para el fin, sumamos
        $tiempoDeFinDeSemana = strtotime("+6 days", $tiempoDeInicioDeSemana); # Sumamos +X days, pero partiendo del tiempo de inicio
        # Y formateamos
        $fechaFinSemana = date("Y-m-d", $tiempoDeFinDeSemana);

        $queryConsumos = "SELECT sum(c.con_valor_total) as total_consumo from consumo c, local l, marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$fechaInicioSemana' and c.con_fecha <= '$fechaFinSemana'";

        $res = mysqli_query($mysqli, $queryConsumos);

        $row = mysqli_fetch_array($res);

        echo $row['total_consumo'];

        break;
    case 'consumos_mes':
        $marca = $_GET['marca'];

        $queryConsumos = "SELECT sum(c.con_valor_total) as total_consumo from consumo c, local l, marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and extract(month from c.con_fecha) = extract(month from (select now())) and extract(year from c.con_fecha) = extract(year from (select now()))";

        $res = mysqli_query($mysqli, $queryConsumos);

        $row = mysqli_fetch_array($res);

        echo $row['total_consumo'];
        break;

    case 'consumos_anio':
        $marca = $_GET['marca'];

        $queryConsumos = "SELECT sum(c.con_valor_total) as total_consumo from consumo c, local l, marca m 
            where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
            and extract(year from c.con_fecha) = extract(year from (select now()))";

        $res = mysqli_query($mysqli, $queryConsumos);

        $row = mysqli_fetch_array($res);

        echo $row['total_consumo'];
        break;
    case 'ultimos_pagos':
        $query = "SELECT u.name_user,c.cli_descripcion,p.pag_fecha,p.pag_monto 
        from gestion g,pago p,cartera ca, cliente c,usuario u 
        where g.car_id = ca.car_id and ca.cli_id = c.cli_id and g.us_id = u.id_user 
        and g.pag_id = p.pag_id order by p.pag_fecha desc limit 20";

        $res = mysqli_query($mysqli, $query);
?>
        <table id="recent-transaction-table" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>GESTOR</th>
                    <th>CLIENTE</th>
                    <th>FECHA</th>
                    <th>PAGO</th>
                    <th class="no-sort">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = mysqli_fetch_array($res)) { ?>
                    <tr>
                        <td><?php echo $row['name_user']; ?></td>
                        <td><?php echo $row['cli_descripcion']; ?></td>
                        <td><?php echo $row['pag_fecha']; ?></td>
                        <td><?php echo $row['pag_monto']; ?></td>
                        <td>
                            <a href="javascript:void(0)"><i class="icon dripicons-download"></i></a>
                        </td>
                    </tr>
                <?php
                }
                ?>

            </tbody>
        </table>
    <?php
        break;
    case 'top_ten_clientes':
        $query = "SELECT cl.cli_descripcion, sum(c.con_valor_total) as total,max(c.con_fecha) as ultimo_consumo 
         from consumo c,cliente cl,personal p where c.per_id = p.per_id and p.cli_id = cl.cli_id 
         and extract(month from c.con_fecha) = extract(month from (select now())) 
         and extract(year from c.con_fecha) = extract(year from (select now()))
         group by cl.cli_id order by total desc limit 10";

        $res = mysqli_query($mysqli, $query);

    ?>
        <div class="table-responsive">
            <table class="table v-align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="p-l-20">Cliente</th>
                        <th>Consumo Total</th>
                        <th>Fecha Ultimo Consumo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_array($res)) { ?>
                        <tr>
                            <td>
                                <strong class="nowrap"><?php echo $row['cli_descripcion'] ?></strong>
                            </td>
                            <td><?php echo $row['total'] ?></td>
                            <td><?php echo $row['ultimo_consumo'] ?></td>
                        </tr>
                    <?php
                    }
                    ?>

                </tbody>
            </table>
        </div>
<?php

        break;
    default:
        # code...
        break;
}
