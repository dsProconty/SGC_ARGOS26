<?php
require_once __DIR__ . "/../../config/database.php";

$tipo = $_GET['tipo'];
header("Content-Type: application/xls");
header("Content-Disposition: attachment; filename=$tipo.xls");

switch ($tipo) {
    case 'ventas por locales':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
        $marca = $_GET['marca'];
?>
        <table style="width: 20%;" border="1" class="table table-bordered">
            <tr>
                <td colspan=3 style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"> DETALLE VENTAS</td>
            </tr>
            <tr>
                <td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DESDE: <?php echo $fechaini; ?> HASTA: <?php echo $fechafin; ?></td>
            </tr>
            <tr>
                <td>Local</td>
                <td>Valor</td>
                <td>Propina</td>
            </tr>
            <?php

            $total = 0.00;

            $iva = 0.00;

            if($marca == 'TODOS'){
                $query = "SELECT l.loc_direccion, SUM(c.con_valor_total) as con_valor_total, SUM(c.con_iva) as con_iva, m.mar_descripcion
                FROM consumo c
                JOIN local l ON c.loc_id = l.loc_id
                JOIN marca m ON l.mar_id = m.mar_id
                WHERE c.con_fecha >= '$fechaini' AND DATE(c.con_fecha) <= '$fechafin'
                GROUP BY l.loc_id, m.mar_id
                ORDER BY m.mar_descripcion, l.loc_direccion";
            }else{
                $query = "SELECT l.loc_direccion, SUM(c.con_valor_total) as con_valor_total, SUM(c.con_iva) as con_iva, m.mar_descripcion
                FROM consumo c
                JOIN local l ON c.loc_id = l.loc_id
                JOIN marca m ON l.mar_id = m.mar_id
                WHERE c.con_fecha >= '$fechaini' AND DATE(c.con_fecha) <= '$fechafin'
                AND m.mar_descripcion = '$marca'
                GROUP BY l.loc_id
                ORDER BY l.loc_direccion";
            }

            

            $result = mysqli_query($mysqli, $query);

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo  utf8_decode($row['loc_direccion']) ?></td>
                    <td>
                        <?php
                        echo $row['con_valor_total'];
                        $total += $row['con_valor_total'];
                        $iva += $row['con_iva'];
                        ?>
                    </td>
                    <td><?php ?></td>
                </tr>
            <?php
            }
            ?>
            <tr></tr>

            <tr>
                <td colspan=2>TOTAL</td>
                <td><?php echo $total - $iva; ?></td>
            </tr>
            <tr></tr>
            <tr>
                <td colspan=2>IVA</td>
                <td><?php echo $iva; ?></td>
            </tr>
            <tr>
                <td colspan=2>VALOR A PAGAR</td>
                <td><?php echo $total ?></td>
            </tr>

        </table>
    <?php
        break;
    case 'cobranzas anteriores':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MARCA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DESDE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">HASTA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL A PAGAR</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR COBRADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">SALDO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">NUMERO COMPROBANTE O CHEQUE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ENTREGADO A</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">OBSERVACION</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,c.car_fecha_inicio,c.car_fecha_fin,
                            c.cli_valor_pagar,g.ges_observacion,u.name_user,sum(p.pag_monto) as pag_monto
            from pago p,gestion g,cartera c,cliente cli,usuario u
            where p.pag_id = g.pag_id and g.us_id = u.id_user
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and DATE(p.pag_fecha) >= '$fechaini' and DATE(p.pag_fecha) <= '$fechafin'
            group by c.car_id order by g.ges_id asc";

            $result = mysqli_query($mysqli, $query);

            $pagado = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php ?></td>
                    <td><?php echo $row['car_fecha_inicio'] ?></td>
                    <td><?php echo $row['car_fecha_fin'] ?></td>
                    <td><?php echo $row['cli_valor_pagar'] ?></td>
                    <td><?php echo $row['pag_monto']; ?></td>
                    <td><?php echo $row['cli_valor_pagar'] - $row['pag_monto']; ?></td>
                    <td></td>
                    <td><?php echo $row['name_user'] ?></td>
                    <td><?php echo utf8_decode($row['ges_observacion']) ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'total cobranza':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr colspan="3"><?php echo utf8_decode('TOTALIZACIÓN COBRANZA') ?></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,sum(p.pag_monto) as pag_monto,c.car_tipo
            from pago p,gestion g,cartera c,cliente cli
            where p.pag_id = g.pag_id
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and DATE(p.pag_fecha) >= '$fechaini' and DATE(p.pag_fecha) <= '$fechafin'
            group by c.car_id order by g.ges_id asc";

            $result = mysqli_query($mysqli, $query);

            $pagado = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['car_tipo'] ?></td>
                    <td><?php echo $row['pag_monto']; ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'detalle cobranza':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr colspan="3"><?php echo utf8_decode('DETALLE COBRANZA') ?></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">PERIODO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,sum(p.pag_monto) as pag_monto,c.car_fecha_inicio,c.car_fecha_fin
            from pago p,gestion g,cartera c,cliente cli
            where p.pag_id = g.pag_id
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and DATE(p.pag_fecha) >= '$fechaini' and DATE(p.pag_fecha) <= '$fechafin'
            group by c.car_id order by g.ges_id asc";

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['car_fecha_inicio'] . ' / ' . $row['car_fecha_fin'] ?></td>
                    <td><?php echo $row['pag_monto'];
                        $total += $row['pag_monto']; ?></td>
                </tr>
            <?php
            }
            ?>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
                <td></td>
                <td><?php echo $total; ?></td>
            </tr>
        </table>
    <?php
        break;
    case 'dinero por edades de cartera':
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="6" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">
                    <?php echo utf8_decode('DINERO POR EDADES DE CARTERA') ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA 30 DÍAS</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA 60 DÍAS</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA 90 DÍAS</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA +90 DÍAS</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
            </tr>
            <?php
            $query = "SELECT cli.cli_descripcion,
                        SUM(CASE WHEN c.car_tipo = '30' THEN COALESCE(c.cli_valor_pagar, 0) ELSE 0 END) AS cartera_30,
                        SUM(CASE WHEN c.car_tipo = '60' THEN COALESCE(c.cli_valor_pagar, 0) ELSE 0 END) AS cartera_60,
                        SUM(CASE WHEN c.car_tipo = '90' THEN COALESCE(c.cli_valor_pagar, 0) ELSE 0 END) AS cartera_90,
                        SUM(CASE WHEN c.car_tipo = '91' THEN COALESCE(c.cli_valor_pagar, 0) ELSE 0 END) AS cartera_91,
                        SUM(COALESCE(c.cli_valor_pagar, 0)) AS total
                      FROM cartera c
                      JOIN cliente cli ON c.cli_id = cli.cli_id
                      WHERE c.car_estado IN ('pendiente', 'notificacion', 'compromiso')
                      GROUP BY cli.cli_id, cli.cli_descripcion
                      ORDER BY cli.cli_descripcion ASC";

            $result = mysqli_query($mysqli, $query);
            $total_30 = $total_60 = $total_90 = $total_91 = $grand_total = 0;
            $hay_datos = false;

            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $total_30     += $row['cartera_30'];
                $total_60     += $row['cartera_60'];
                $total_90     += $row['cartera_90'];
                $total_91     += $row['cartera_91'];
                $grand_total  += $row['total'];
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo number_format($row['cartera_30'], 2) ?></td>
                    <td><?php echo number_format($row['cartera_60'], 2) ?></td>
                    <td><?php echo number_format($row['cartera_90'], 2) ?></td>
                    <td><?php echo number_format($row['cartera_91'], 2) ?></td>
                    <td><?php echo number_format($row['total'], 2) ?></td>
                </tr>
            <?php } ?>
            <?php if (!$hay_datos): ?>
                <tr><td colspan="6">Sin datos de cartera activa.</td></tr>
            <?php else: ?>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><strong>TOTALES</strong></td>
                    <td><strong><?php echo number_format($total_30, 2) ?></strong></td>
                    <td><strong><?php echo number_format($total_60, 2) ?></strong></td>
                    <td><strong><?php echo number_format($total_90, 2) ?></strong></td>
                    <td><strong><?php echo number_format($total_91, 2) ?></strong></td>
                    <td><strong><?php echo number_format($grand_total, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;
    case 'cartera recuperada':
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="5">
                    <?php echo utf8_decode('CARTERA RECUPERADA') ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MARCA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TIPO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR CARTERA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">RECUPERADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DEUDA ACTUAL</td>
            </tr>
            <?php

            $query = "SELECT c.car_tipo,cli.cli_descripcion,c.cli_valor_pagar,sum(p.pag_monto) as pag_monto
            FROM cartera c,cliente cli,gestion g,pago p 
            where c.cli_id = cli.cli_id and c.car_id = g.car_id and g.pag_id = p.pag_id group by c.car_id";

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['car_tipo'] ?></td>
                    <td><?php echo $row['cli_valor_pagar'] ?></td>
                    <td><?php echo $row['pag_monto']; ?></td>
                    <td><?php echo $row['cli_valor_pagar'] - $row['pag_monto'] ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'cliente consumos':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="4">
                    <?php echo utf8_decode('CARTERA RECUPERADA') ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CONSUMO TOTAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA ULT. CONSUMO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MARCA ULTIMO CONSUMO</td>
            </tr>
            <?php

            $query = "SELECT cli.cli_descripcion,max(c.con_fecha)as con_fecha,sum(c.con_valor_total) as con_valor_total,m.mar_descripcion
            from consumo c,personal p,cliente cli,local l,marca m 
            where cli.cli_id = p.cli_id and c.per_id = p.per_id
            and c.loc_id = l.loc_id and m.mar_id = l.mar_id
            and c.con_fecha >= '$fechaini' and c.con_fecha <= '$fechafin'
            group by cli.cli_id order by con_valor_total desc";

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['con_valor_total'] ?></td>
                    <td><?php echo $row['con_fecha'] ?></td>
                    <td><?php echo $row['mar_descripcion']; ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'cliente - consumos':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="4">
                    <?php echo utf8_decode('CARTERA RECUPERADA') ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA ULT. CONSUMO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MARCA ULTIMO CONSUMO</td>
            </tr>
            <?php

            $query = "SELECT cli.cli_descripcion,max(c.con_fecha)as con_fecha,m.mar_descripcion
            from consumo c,personal p,cliente cli,local l,marca m 
            where cli.cli_id = p.cli_id and c.per_id = p.per_id
            and c.loc_id = l.loc_id and m.mar_id = l.mar_id
            and c.con_fecha >= '$fechaini' and c.con_fecha <= '$fechafin'
            group by cli.cli_id order by con_valor_total desc";

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['con_fecha'] ?></td>
                    <td><?php echo $row['mar_descripcion']; ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'cobranza por gestor':
        $fechaini = $_GET['fecha_inicio'];
        $fechafin = $_GET['fecha_fin'];
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="4">
                    <?php echo utf8_decode('COBRANZA POR GESTOR') ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">GESTOR</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CARTERA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL DEUDA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR COBRADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">SALDO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">OBSERVACION COBRANZA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA PAGO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">OBSERVACION GESTION</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,c.car_fecha_inicio,c.car_fecha_fin,c.car_tipo,
                    c.cli_valor_pagar,g.ges_observacion,u.name_user,p.pag_monto,p.pag_observacion,p.pag_fecha
                    from pago p,gestion g,cartera c,cliente cli,usuario u
                    where p.pag_id = g.pag_id and g.us_id = u.id_user
                    and g.car_id = c.car_id and c.cli_id = cli.cli_id and DATE(p.pag_fecha) >= '$fechaini' and DATE(p.pag_fecha) <= '$fechafin'
                    group by c.car_id order by name_user";

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo $row['name_user'] ?></td>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['car_tipo'] ?></td>
                    <td><?php echo $row['cli_valor_pagar'] ?></td>
                    <td><?php echo $row['pag_monto'] ?></td>
                    <td><?php echo $row['cli_valor_pagar'] - $row['pag_monto'] ?></td>
                    <td><?php echo $row['pag_observacion'] ?></td>
                    <td><?php echo $row['pag_fecha'] ?></td>
                    <td><?php echo utf8_decode($row['ges_observacion']) ?></td>

                </tr>
            <?php
            }
            ?>
        </table>
    <?php
        break;
    case 'consumos del mes':
        $marca = $_GET['marca'];
        $inicioMes = date("Y-m-01");
        $finMes = date("Y-m-t");
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">
                    <?php echo utf8_decode('CONSUMOS DEL MES ' . $marca) ?>
                </td>
            </tr>
            <tr>
                <td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">
                    Fecha Inicio : <?php echo $inicioMes?> Fecha Fin: <?php echo $finMes ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">PROPINA</td>
            </tr>
            <?php

            if($marca == 'TODOS'){
                $query = "SELECT cli.cli_descripcion, p.per_nombre, c.con_fecha,c.con_hora, c.con_valor_total from consumo c,local l,marca m,personal p,cliente cli
                where c.loc_id = l.loc_id and l.mar_id = m.mar_id and c.per_id = p.per_id and p.cli_id = cli.cli_id
                and extract(month from c.con_fecha) = extract(month from (select now())) 
                and extract(year from c.con_fecha) = extract(year from (select now())) 
                order by con_fecha,cli_descripcion desc";
            }else{
                $query = "SELECT cli.cli_descripcion, p.per_nombre, c.con_fecha,c.con_hora, c.con_valor_total from consumo c,local l,marca m,personal p,cliente cli
                where c.loc_id = l.loc_id and l.mar_id = m.mar_id and c.per_id = p.per_id and p.cli_id = cli.cli_id
                and m.mar_descripcion = '$marca' and extract(month from c.con_fecha) = extract(month from (select now())) 
                and extract(year from c.con_fecha) = extract(year from (select now())) order by con_fecha,cli_descripcion desc";
            }

            

            $result = mysqli_query($mysqli, $query);

            $total = 0.00;
            $hay_datos = false;

            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo utf8_decode($row['per_nombre']) ?></td>
                    <td><?php echo utf8_decode($row['con_fecha'] . ' / ' . $row['con_hora']) ?></td>
                    <td><?php echo $row['con_valor_total']; $total += $row['con_valor_total']; ?></td>
                    <td></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="5">No hay consumos registrados para el mes en curso con la marca seleccionada.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><strong>TOTAL</strong></td>
                    <td><strong><?php echo number_format($total, 2); ?></strong></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </table>
<?php
        break;

    case 'transacciones por local':
        $marca = (int)($_GET['marca'] ?? 0);
        $provincia = $_GET['provincia'] ?? '';
        $local = (int)($_GET['local'] ?? 0);
        $cliente = (int)($_GET['cliente'] ?? 0);
        $fechaini = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? '');
        $fechafin = mysqli_real_escape_string($mysqli, $_GET['fecha_fin'] ?? '');
        if ($marca <= 0) {
            echo "<table><tr><td>Debe seleccionar una marca.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="9" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('TRANSACCIONES POR LOCAL') ?></td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">HORA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">LOCAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TARJETA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DOCUMENTO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">NOMBRES</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">AUTORIZACION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR CONSUMO</td>
            </tr>
            <?php
            $where = ["l.mar_id = $marca"];
            if ($provincia === 'SIN_ESPECIFICAR') {
                $where[] = "(l.loc_provincia IS NULL OR l.loc_provincia = '')";
            } elseif ($provincia !== '') {
                $where[] = "l.loc_provincia = '" . mysqli_real_escape_string($mysqli, $provincia) . "'";
            }
            if ($local > 0) $where[] = "l.loc_id = $local";
            if ($cliente > 0) $where[] = "cli.cli_id = $cliente";
            if ($fechaini !== '') $where[] = "con.con_fecha >= '$fechaini'";
            if ($fechafin !== '') $where[] = "con.con_fecha <= '$fechafin'";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT con.con_fecha, con.con_hora, COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label,
                             cli.cli_descripcion, con.con_numero_tarjeta, per.per_documento, per.per_nombre,
                             con.con_autorizacion, con.con_valor_total
                      FROM consumo con
                      JOIN personal per ON con.per_id = per.per_id
                      JOIN cliente cli ON per.cli_id = cli.cli_id
                      JOIN local l ON con.loc_id = l.loc_id
                      WHERE $whereSql
                      ORDER BY con.con_fecha DESC, con.con_hora DESC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo $row['con_fecha'] ?></td>
                    <td><?php echo $row['con_hora'] ?></td>
                    <td><?php echo utf8_decode($row['loc_label']) ?></td>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['con_numero_tarjeta'] ?></td>
                    <td><?php echo $row['per_documento'] ?></td>
                    <td><?php echo utf8_decode($row['per_nombre']) ?></td>
                    <td><?php echo $row['con_autorizacion'] ?></td>
                    <td><?php echo $row['con_valor_total'] ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="9">No hay transacciones para los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'total ventas':
        $marca = (int)($_GET['marca'] ?? 0);
        if ($marca <= 0) {
            echo "<table><tr><td>Debe seleccionar una marca.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr>
                <td colspan="14" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('TOTAL VENTAS - VALORES PONDERADOS (SIN IVA)') ?></td>
            </tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">AÑO</td>
                <?php
                $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
                foreach ($meses as $mesLabel) {
                    echo '<td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">' . $mesLabel . '</td>';
                }
                ?>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
            </tr>
            <?php
            $query = "SELECT YEAR(con.con_fecha) AS anio, MONTH(con.con_fecha) AS mes,
                             SUM(con.con_valor_total - con.con_iva) AS neto
                      FROM consumo con
                      JOIN local l ON con.loc_id = l.loc_id
                      WHERE l.mar_id = $marca
                      GROUP BY YEAR(con.con_fecha), MONTH(con.con_fecha)
                      ORDER BY anio ASC, mes ASC";
            $result = mysqli_query($mysqli, $query);
            $matriz = [];
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $matriz[$row['anio']][(int)$row['mes']] = (float)$row['neto'];
            }
            if (!$hay_datos): ?>
                <tr><td colspan="14">No hay ventas registradas para esta marca.</td></tr>
            <?php else:
                foreach ($matriz as $anio => $mesesData) {
                    $totalAnio = 0;
                    echo "<tr><td>$anio</td>";
                    for ($m = 1; $m <= 12; $m++) {
                        $valor = $mesesData[$m] ?? 0;
                        $totalAnio += $valor;
                        echo '<td>' . number_format($valor, 2) . '</td>';
                    }
                    echo '<td><strong>' . number_format($totalAnio, 2) . '</strong></td></tr>';
                }
            endif;
            ?>
        </table>
    <?php
        break;

    case 'registro cobranza':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $fechaini = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? '');
        $fechafin = mysqli_real_escape_string($mysqli, $_GET['fecha_fin'] ?? '');
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="6" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('REGISTRO COBRANZA') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA REGISTRO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA DESDE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA HASTA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MORA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
            </tr>
            <?php
            $where = [];
            if ($cliente > 0) $where[] = "car.cli_id = $cliente";
            if ($fechaini !== '') $where[] = "car.car_fecha_ingreso >= '$fechaini'";
            if ($fechafin !== '') $where[] = "car.car_fecha_ingreso <= '$fechafin'";
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $query = "SELECT cli.cli_descripcion, car.car_fecha_ingreso, car.car_fecha_inicio, car.car_fecha_fin,
                             car.car_tipo, car.cli_valor_pagar
                      FROM cartera car
                      JOIN cliente cli ON car.cli_id = cli.cli_id
                      $whereSql
                      ORDER BY car.car_fecha_ingreso DESC";
            $result = mysqli_query($mysqli, $query);
            $total = 0.00;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $total += $row['cli_valor_pagar'];
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['car_fecha_ingreso'] ?></td>
                    <td><?php echo $row['car_fecha_inicio'] ?></td>
                    <td><?php echo $row['car_fecha_fin'] ?></td>
                    <td><?php echo $row['car_tipo'] ?></td>
                    <td><?php echo number_format($row['cli_valor_pagar'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="6">No hay registros de cartera para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
                    <td><strong><?php echo number_format($total, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'ventas locales':
        $marca = (int)($_GET['marca'] ?? 0);
        $fechaini = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? '');
        $fechafin = mysqli_real_escape_string($mysqli, $_GET['fecha_fin'] ?? '');
        if ($marca <= 0) {
            echo "<table><tr><td>Debe seleccionar una marca.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('VENTAS LOCALES') ?></td></tr>
            <?php
            $where = ["l.mar_id = $marca"];
            if ($fechaini !== '') $where[] = "con.con_fecha >= '$fechaini'";
            if ($fechafin !== '') $where[] = "con.con_fecha <= '$fechafin'";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT COALESCE(l.loc_provincia, '') AS provincia, COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label,
                             SUM(con.con_valor_total) AS bruto, SUM(con.con_valor_total - con.con_iva) AS neto
                      FROM consumo con
                      JOIN local l ON con.loc_id = l.loc_id
                      WHERE $whereSql
                      GROUP BY provincia, l.loc_id
                      ORDER BY provincia ASC, loc_label ASC";
            $result = mysqli_query($mysqli, $query);
            $grupos = [];
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $prov = $row['provincia'] !== '' ? $row['provincia'] : 'Sin especificar';
                $grupos[$prov][] = $row;
            }
            if (!$hay_datos): ?>
                <tr><td colspan="3">No hay ventas registradas para esta marca en el período.</td></tr>
            <?php else:
                foreach ($grupos as $provincia => $locales) {
                    echo '<tr><td colspan="3" style="background-color:#eee;font-weight:bold;">' . utf8_decode(strtoupper($provincia)) . '</td></tr>';
                    echo '<tr><td>LOCAL</td><td>VALOR BRUTO</td><td>VALOR NETO (SIN IVA)</td></tr>';
                    $totalBruto = 0;
                    $totalNeto = 0;
                    foreach ($locales as $row) {
                        $totalBruto += $row['bruto'];
                        $totalNeto += $row['neto'];
                        echo '<tr><td>' . utf8_decode($row['loc_label']) . '</td><td>' . number_format($row['bruto'], 2) . '</td><td>' . number_format($row['neto'], 2) . '</td></tr>';
                    }
                    echo '<tr><td><strong>TOTAL ' . utf8_decode(strtoupper($provincia)) . '</strong></td><td><strong>' . number_format($totalBruto, 2) . '</strong></td><td><strong>' . number_format($totalNeto, 2) . '</strong></td></tr>';
                }
            endif;
            ?>
        </table>
    <?php
        break;

    case 'cobranza pendiente por empresa':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $fechaCorte = mysqli_real_escape_string($mysqli, $_GET['fecha_corte'] ?? '');
        if ($fechaCorte === '') {
            echo "<table><tr><td>Debe indicar la fecha de corte.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('COBRANZA PENDIENTE POR EMPRESA') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">AÑO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php
            $where = ["car.car_estado IN ('pendiente','notificacion','compromiso')", "car.car_fecha_ingreso <= '$fechaCorte'"];
            if ($cliente > 0) $where[] = "car.cli_id = $cliente";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT cli.cli_descripcion, YEAR(car.car_fecha_ingreso) AS anio, SUM(car.cli_valor_pagar) AS valor
                      FROM cartera car
                      JOIN cliente cli ON car.cli_id = cli.cli_id
                      WHERE $whereSql
                      GROUP BY car.cli_id, anio
                      ORDER BY cli.cli_descripcion ASC, anio ASC";
            $result = mysqli_query($mysqli, $query);
            $total = 0.00;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $total += $row['valor'];
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo $row['anio'] ?></td>
                    <td><?php echo number_format($row['valor'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="3">No hay cartera pendiente para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
                    <td><strong><?php echo number_format($total, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'cobranza pendiente por mes':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $fechaCorte = mysqli_real_escape_string($mysqli, $_GET['fecha_corte'] ?? '');
        if ($fechaCorte === '') {
            echo "<table><tr><td>Debe indicar la fecha de corte.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('COBRANZA PENDIENTE POR MES') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">AÑO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">MES</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php
            $where = ["car.car_estado IN ('pendiente','notificacion','compromiso')", "car.car_fecha_ingreso <= '$fechaCorte'"];
            if ($cliente > 0) $where[] = "car.cli_id = $cliente";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT YEAR(car.car_fecha_ingreso) AS anio, MONTH(car.car_fecha_ingreso) AS mes, SUM(car.cli_valor_pagar) AS valor
                      FROM cartera car
                      WHERE $whereSql
                      GROUP BY anio, mes
                      ORDER BY anio ASC, mes ASC";
            $result = mysqli_query($mysqli, $query);
            $total = 0.00;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $total += $row['valor'];
            ?>
                <tr>
                    <td><?php echo $row['anio'] ?></td>
                    <td><?php echo $row['mes'] ?></td>
                    <td><?php echo number_format($row['valor'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="3">No hay cartera pendiente para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
                    <td><strong><?php echo number_format($total, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'pendiente empresas':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        if ($anio <= 0) {
            echo "<table><tr><td>Debe seleccionar un año.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="14" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('REPORTE PENDIENTE EMPRESAS ' . $anio) ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <?php
                $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
                foreach ($meses as $mesLabel) echo '<td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">' . $mesLabel . '</td>';
                ?>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
            </tr>
            <?php
            $whereCli = $cliente > 0 ? "AND cli.cli_id = $cliente" : "";
            $queryClientes = "SELECT cli_id, cli_descripcion FROM cliente cli WHERE 1=1 $whereCli ORDER BY cli_descripcion ASC";
            $resClientes = mysqli_query($mysqli, $queryClientes);

            $queryValores = "SELECT car.cli_id, MONTH(car.car_fecha_ingreso) AS mes, SUM(car.cli_valor_pagar) AS valor
                             FROM cartera car
                             WHERE car.car_estado IN ('pendiente','notificacion','compromiso')
                               AND YEAR(car.car_fecha_ingreso) = $anio
                             GROUP BY car.cli_id, mes";
            $resValores = mysqli_query($mysqli, $queryValores);
            $valores = [];
            while ($v = mysqli_fetch_assoc($resValores)) {
                $valores[$v['cli_id']][(int)$v['mes']] = (float)$v['valor'];
            }

            $totalGeneral = 0;
            $hay_datos = false;
            while ($cli = mysqli_fetch_assoc($resClientes)) {
                $hay_datos = true;
                $totalEmpresa = 0;
                echo '<tr><td>' . utf8_decode($cli['cli_descripcion']) . '</td>';
                for ($m = 1; $m <= 12; $m++) {
                    $v = $valores[$cli['cli_id']][$m] ?? 0;
                    $totalEmpresa += $v;
                    echo '<td>' . number_format($v, 2) . '</td>';
                }
                $totalGeneral += $totalEmpresa;
                echo '<td><strong>' . number_format($totalEmpresa, 2) . '</strong></td></tr>';
            }
            if (!$hay_datos): ?>
                <tr><td colspan="14">No hay clientes registrados.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="13" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL POR COBRAR</td>
                    <td><strong><?php echo number_format($totalGeneral, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'ranking de locales':
        $marca = (int)($_GET['marca'] ?? 0);
        $fechaini = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? '');
        $fechafin = mysqli_real_escape_string($mysqli, $_GET['fecha_fin'] ?? '');
        if ($marca <= 0) {
            echo "<table><tr><td>Debe seleccionar una marca.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('RANKING DE LOCALES POR VENTAS') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">LOCAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR BRUTO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR NETO (SIN IVA)</td>
            </tr>
            <?php
            $where = ["l.mar_id = $marca"];
            if ($fechaini !== '') $where[] = "con.con_fecha >= '$fechaini'";
            if ($fechafin !== '') $where[] = "con.con_fecha <= '$fechafin'";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label,
                             SUM(con.con_valor_total) AS bruto, SUM(con.con_valor_total - con.con_iva) AS neto
                      FROM consumo con
                      JOIN local l ON con.loc_id = l.loc_id
                      WHERE $whereSql
                      GROUP BY l.loc_id
                      ORDER BY bruto DESC";
            $result = mysqli_query($mysqli, $query);
            $totalBruto = 0;
            $totalNeto = 0;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $totalBruto += $row['bruto'];
                $totalNeto += $row['neto'];
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['loc_label']) ?></td>
                    <td><?php echo number_format($row['bruto'], 2) ?></td>
                    <td><?php echo number_format($row['neto'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="3">No hay ventas registradas para esta marca en el período.</td></tr>
            <?php else: ?>
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td><strong><?php echo number_format($totalBruto, 2) ?></strong></td>
                    <td><strong><?php echo number_format($totalNeto, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    default:
        # code...
        break;
}

?>