<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../services/giftcard_solicitud.php";

// H-001/PV-B: los reportes de Gift Card (giftpoint, reporte gifcards, registro
// pagos gift) leían cgc_estado directo, sin pasar nunca por
// gc_expirar_codigos_vencidos() — un código vencido seguía saliendo "activo"
// en el reporte hasta que alguien lo consultaba en otra pantalla (POS,
// módulo Gift Card). Se corrige aquí igual que en esas otras pantallas.
gc_expirar_codigos_vencidos($mysqli);

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
            if ($cliente > 0) $where[] = "COALESCE(cli.cli_id, clgc.cli_id) = $cliente";
            if ($fechaini !== '') $where[] = "con.con_fecha >= '$fechaini'";
            if ($fechafin !== '') $where[] = "con.con_fecha <= '$fechafin'";
            $whereSql = implode(' AND ', $where);

            // LEFT JOIN: una venta de Gift Card sin empleado no tiene per_id, pero
            // sigue perteneciendo al convenio que compró esa Gift Card (vía el lote).
            $query = "SELECT con.con_fecha, con.con_hora, COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label,
                             COALESCE(cli.cli_descripcion, clgc.cli_descripcion) AS cli_descripcion,
                             con.con_numero_tarjeta, per.per_documento, per.per_nombre,
                             con.con_autorizacion, con.con_valor_total, con.con_giftcard_codigo
                      FROM consumo con
                      LEFT JOIN personal per ON con.per_id = per.per_id
                      LEFT JOIN cliente cli ON per.cli_id = cli.cli_id
                      LEFT JOIN codigo_gift_card cgc ON con.con_giftcard_codigo = cgc.cgc_codigo
                      LEFT JOIN lote_gift_card lgc ON cgc.lgc_id = lgc.lgc_id
                      LEFT JOIN cliente clgc ON lgc.cli_id = clgc.cli_id
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
                    <td><?php echo $row['cli_descripcion'] !== null ? utf8_decode($row['cli_descripcion']) : '' ?></td>
                    <td><?php echo $row['con_numero_tarjeta'] ?></td>
                    <td><?php echo $row['per_documento'] ?? '' ?></td>
                    <td><?php echo $row['per_nombre'] !== null ? utf8_decode($row['per_nombre']) : ($row['con_giftcard_codigo'] !== null ? 'Gift Card ' . $row['con_giftcard_codigo'] : '') ?></td>
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

    case 'detalle de tarjetas':
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="6" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('DETALLE DE TARJETAS - CLIENTES') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CLIENTE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CONTACTO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMAIL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TELEFONO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">COMISION %</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DIA DE CORTE</td>
            </tr>
            <?php
            $query = "SELECT cli_descripcion, cli_contacto, cli_email, cli_telefono, cli_comision, cli_dia_corte FROM cliente ORDER BY cli_descripcion ASC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo utf8_decode($row['cli_contacto']) ?></td>
                    <td><?php echo $row['cli_email'] ?></td>
                    <td><?php echo $row['cli_telefono'] ?></td>
                    <td><?php echo number_format($row['cli_comision'], 2) ?></td>
                    <td><?php echo $row['cli_dia_corte'] ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="6">No hay clientes registrados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'detalle de tarjetas por cliente':
        $cliente = (int)($_GET['cliente'] ?? 0);
        if ($cliente <= 0) {
            echo "<table><tr><td>Debe seleccionar un cliente.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="6" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('DETALLE DE TARJETAS POR CLIENTE') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPLEADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DOCUMENTO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TARJETA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO ASIGNADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO DISPONIBLE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ESTADO</td>
            </tr>
            <?php
            $query = "SELECT per_nombre, per_documento, per_numero_tarjeta, per_cupo_asignado, per_cupo_disponible, per_estado
                      FROM personal WHERE cli_id = $cliente AND per_estado != 'archivado' ORDER BY per_nombre ASC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['per_nombre']) ?></td>
                    <td><?php echo $row['per_documento'] ?></td>
                    <td><?php echo $row['per_numero_tarjeta'] ?></td>
                    <td><?php echo number_format($row['per_cupo_asignado'], 2) ?></td>
                    <td><?php echo number_format($row['per_cupo_disponible'], 2) ?></td>
                    <td><?php echo $row['per_estado'] ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="6">Este cliente no tiene empleados registrados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'giftpoint':
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="7" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('GIFTPOINT - LISTADO DE CODIGOS') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CODIGO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO INICIAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CONSUMIDO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO DISPONIBLE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ESTADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA ACTIVACION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA CADUCIDAD</td>
            </tr>
            <?php
            $query = "SELECT cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad
                      FROM codigo_gift_card ORDER BY cgc_fecha_activacion DESC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $consumido = $row['cgc_cupo_inicial'] - $row['cgc_cupo_disponible'];
            ?>
                <tr>
                    <td><?php echo $row['cgc_codigo'] ?></td>
                    <td><?php echo number_format($row['cgc_cupo_inicial'], 2) ?></td>
                    <td><?php echo number_format($consumido, 2) ?></td>
                    <td><?php echo number_format($row['cgc_cupo_disponible'], 2) ?></td>
                    <td><?php echo $row['cgc_estado'] ?></td>
                    <td><?php echo $row['cgc_fecha_activacion'] ?></td>
                    <td><?php echo $row['cgc_fecha_caducidad'] ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="7">No hay códigos de gift card registrados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'giftpoint transacciones':
        $codigo = mysqli_real_escape_string($mysqli, trim($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            echo "<table><tr><td>Debe indicar un código de gift card.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="7" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('TRANSACCIONES DE LA TARJETA ' . $codigo) ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">HORA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">LOCAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">DOCUMENTO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">NOMBRES</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">AUTORIZACION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php
            $query = "SELECT con.con_fecha, con.con_hora, COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label,
                             per.per_documento, per.per_nombre, con.con_autorizacion, con.con_monto_giftcard
                      FROM consumo con
                      LEFT JOIN personal per ON con.per_id = per.per_id
                      LEFT JOIN local l ON con.loc_id = l.loc_id
                      WHERE con.con_giftcard_codigo = '$codigo'
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
                    <td><?php echo $row['per_documento'] ?? '' ?></td>
                    <td><?php echo $row['per_nombre'] !== null ? utf8_decode($row['per_nombre']) : '<em>Venta directa de Gift Card (sin empleado)</em>' ?></td>
                    <td><?php echo $row['con_autorizacion'] ?></td>
                    <td><?php echo number_format($row['con_monto_giftcard'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="7">No hay transacciones registradas para este código.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'reporte gifcards':
        $fechaini = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? '');
        $fechafin = mysqli_real_escape_string($mysqli, $_GET['fecha_fin'] ?? '');
        $cliente = (int)($_GET['cliente'] ?? 0);
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="8" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('REPORTE GIFCARDS') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CODIGO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO INICIAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO DISPONIBLE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ESTADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA CADUCIDAD</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ULTIMA AUTORIZACION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ULTIMO LOCAL DE CONSUMO</td>
            </tr>
            <?php
            $where = [];
            if ($fechaini !== '') $where[] = "cgc.cgc_fecha_activacion >= '$fechaini'";
            if ($fechafin !== '') $where[] = "cgc.cgc_fecha_activacion <= '$fechafin'";
            if ($cliente > 0) $where[] = "lgc.cli_id = $cliente";
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $query = "SELECT cgc.cgc_codigo, cgc.cgc_cupo_inicial, cgc.cgc_cupo_disponible, cgc.cgc_estado, cgc.cgc_fecha_caducidad,
                             cli.cli_descripcion, uc.con_autorizacion, uc.loc_label
                      FROM codigo_gift_card cgc
                      LEFT JOIN lote_gift_card lgc ON cgc.lgc_id = lgc.lgc_id
                      LEFT JOIN cliente cli ON lgc.cli_id = cli.cli_id
                      LEFT JOIN (
                          SELECT c1.con_giftcard_codigo, c1.con_autorizacion, COALESCE(l1.loc_nombre, l1.loc_direccion) AS loc_label
                          FROM consumo c1
                          LEFT JOIN local l1 ON c1.loc_id = l1.loc_id
                          WHERE c1.con_id = (SELECT MAX(c2.con_id) FROM consumo c2 WHERE c2.con_giftcard_codigo = c1.con_giftcard_codigo)
                      ) uc ON uc.con_giftcard_codigo = cgc.cgc_codigo
                      $whereSql
                      ORDER BY cgc.cgc_fecha_activacion DESC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo $row['cgc_codigo'] ?></td>
                    <td><?php echo $row['cli_descripcion'] !== null ? utf8_decode($row['cli_descripcion']) : '' ?></td>
                    <td><?php echo number_format($row['cgc_cupo_inicial'], 2) ?></td>
                    <td><?php echo number_format($row['cgc_cupo_disponible'], 2) ?></td>
                    <td><?php echo $row['cgc_estado'] ?></td>
                    <td><?php echo $row['cgc_fecha_caducidad'] ?></td>
                    <td><?php echo $row['con_autorizacion'] ?></td>
                    <td><?php echo $row['loc_label'] !== null ? utf8_decode($row['loc_label']) : '' ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="8">No hay gift cards para los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'registro pagos gift':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);
        if ($cliente <= 0 || $anio <= 0 || $mes <= 0) {
            echo "<table><tr><td>Debe seleccionar cliente, año y mes.</td></tr></table>";
            break;
        }
        $q_cli = mysqli_query($mysqli, "SELECT cli_descripcion FROM cliente WHERE cli_id = $cliente");
        $cliData = mysqli_fetch_assoc($q_cli);
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="4" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">
                <?php echo utf8_decode('REGISTRO PAGOS GIFT - ' . ($cliData ? $cliData['cli_descripcion'] : '') . ' - ' . $mes . '/' . $anio) ?>
            </td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CODIGO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO INICIAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">CUPO DISPONIBLE</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">ESTADO</td>
            </tr>
            <?php
            $query = "SELECT cgc.cgc_codigo, cgc.cgc_cupo_inicial, cgc.cgc_cupo_disponible, cgc.cgc_estado
                      FROM lote_gift_card lgc
                      JOIN codigo_gift_card cgc ON cgc.lgc_id = lgc.lgc_id
                      WHERE lgc.cli_id = $cliente
                        AND YEAR(lgc.lgc_periodo_facturacion) = $anio
                        AND MONTH(lgc.lgc_periodo_facturacion) = $mes";
            $result = mysqli_query($mysqli, $query);
            $total = 0.00;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $total += $row['cgc_cupo_inicial'];
            ?>
                <tr>
                    <td><?php echo $row['cgc_codigo'] ?></td>
                    <td><?php echo number_format($row['cgc_cupo_inicial'], 2) ?></td>
                    <td><?php echo number_format($row['cgc_cupo_disponible'], 2) ?></td>
                    <td><?php echo $row['cgc_estado'] ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="4">No hay gift cards emitidas para este cliente en el período.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL A PAGAR</td>
                    <td><strong><?php echo number_format($total, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'comision mensual empresas':
        $marca = (int)($_GET['marca'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);
        if ($marca <= 0 || $anio <= 0 || $mes <= 0) {
            echo "<table><tr><td>Debe seleccionar marca, año y mes.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('COMISION MENSUAL EMPRESAS') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">% COMISION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VENTA NETA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">IVA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">COMISION</td>
            </tr>
            <?php
            // Una venta de Gift Card sin empleado (per_id NULL) igual pertenece al
            // convenio que compró esa Gift Card — se resuelve por el lote para que
            // su venta/comisión no desaparezca del total de esa empresa.
            $query = "SELECT cli.cli_descripcion, cli.cli_comision,
                             SUM(con.con_valor_total - con.con_iva) AS venta_neta, SUM(con.con_iva) AS iva
                      FROM consumo con
                      JOIN local l ON con.loc_id = l.loc_id
                      LEFT JOIN personal per ON con.per_id = per.per_id
                      LEFT JOIN codigo_gift_card cgc ON con.con_giftcard_codigo = cgc.cgc_codigo
                      LEFT JOIN lote_gift_card lgc ON cgc.lgc_id = lgc.lgc_id
                      JOIN cliente cli ON cli.cli_id = COALESCE(per.cli_id, lgc.cli_id)
                      WHERE l.mar_id = $marca
                        AND YEAR(con.con_fecha) = $anio
                        AND MONTH(con.con_fecha) = $mes
                      GROUP BY cli.cli_id
                      ORDER BY cli.cli_descripcion ASC";
            $result = mysqli_query($mysqli, $query);
            $totalNeto = 0;
            $totalIva = 0;
            $totalComision = 0;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $comision = $row['venta_neta'] * $row['cli_comision'] / 100;
                $totalNeto += $row['venta_neta'];
                $totalIva += $row['iva'];
                $totalComision += $comision;
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo number_format($row['cli_comision'], 2) ?></td>
                    <td><?php echo number_format($row['venta_neta'], 2) ?></td>
                    <td><?php echo number_format($row['iva'], 2) ?></td>
                    <td><?php echo number_format($comision, 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="5">No hay ventas registradas para esta marca en el período.</td></tr>
            <?php else: ?>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL</td>
                    <td></td>
                    <td><strong><?php echo number_format($totalNeto, 2) ?></strong></td>
                    <td><strong><?php echo number_format($totalIva, 2) ?></strong></td>
                    <td><strong><?php echo number_format($totalComision, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'detalle cobranza ventas':
        $marca = (int)($_GET['marca'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);
        if ($marca <= 0 || $anio <= 0 || $mes <= 0) {
            echo "<table><tr><td>Debe seleccionar marca, año y mes.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('DETALLE COBRANZA - VENTAS POR EMPRESA') ?></td></tr>
            <tr><td colspan="5" style="background-color:#eee;font-weight:bold;"><?php echo utf8_decode('VENTAS BUSINESS CARD') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VENTA NETA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">IVA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">% COMISION</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">COMISION</td>
            </tr>
            <?php
            $queryBc = "SELECT cli.cli_descripcion, cli.cli_comision,
                               SUM(con.con_valor_total - con.con_iva) AS venta_neta, SUM(con.con_iva) AS iva
                        FROM consumo con
                        JOIN personal per ON con.per_id = per.per_id
                        JOIN cliente cli ON per.cli_id = cli.cli_id
                        JOIN local l ON con.loc_id = l.loc_id
                        WHERE l.mar_id = $marca
                          AND YEAR(con.con_fecha) = $anio AND MONTH(con.con_fecha) = $mes
                          AND con.con_monto_giftcard = 0
                        GROUP BY cli.cli_id
                        ORDER BY cli.cli_descripcion ASC";
            $resultBc = mysqli_query($mysqli, $queryBc);
            $totalVentaNeta = 0;
            $totalIva = 0;
            $totalComision = 0;
            $hayBc = false;
            while ($row = mysqli_fetch_array($resultBc)) {
                $hayBc = true;
                $comision = $row['venta_neta'] * $row['cli_comision'] / 100;
                $totalVentaNeta += $row['venta_neta'];
                $totalIva += $row['iva'];
                $totalComision += $comision;
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo number_format($row['venta_neta'], 2) ?></td>
                    <td><?php echo number_format($row['iva'], 2) ?></td>
                    <td><?php echo number_format($row['cli_comision'], 2) ?></td>
                    <td><?php echo number_format($comision, 2) ?></td>
                </tr>
            <?php
            }
            if (!$hayBc): ?>
                <tr><td colspan="5">Sin ventas Business Card en el período.</td></tr>
            <?php endif; ?>
            <tr><td colspan="5" style="background-color:#eee;font-weight:bold;"><?php echo utf8_decode('VENTAS GIFT CARD') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;" colspan="4">EMPRESA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php
            // Igual que en el bloque anterior: una venta de Gift Card sin empleado
            // se atribuye a la empresa dueña del convenio vía el lote que generó
            // esa Gift Card, no solo a través del empleado que la usó.
            $queryGift = "SELECT cli.cli_descripcion, SUM(con.con_monto_giftcard) AS valor_gift
                          FROM consumo con
                          JOIN local l ON con.loc_id = l.loc_id
                          LEFT JOIN personal per ON con.per_id = per.per_id
                          LEFT JOIN codigo_gift_card cgc ON con.con_giftcard_codigo = cgc.cgc_codigo
                          LEFT JOIN lote_gift_card lgc ON cgc.lgc_id = lgc.lgc_id
                          JOIN cliente cli ON cli.cli_id = COALESCE(per.cli_id, lgc.cli_id)
                          WHERE l.mar_id = $marca
                            AND YEAR(con.con_fecha) = $anio AND MONTH(con.con_fecha) = $mes
                            AND con.con_monto_giftcard > 0
                          GROUP BY cli.cli_id
                          ORDER BY cli.cli_descripcion ASC";
            $resultGift = mysqli_query($mysqli, $queryGift);
            $totalGift = 0;
            $hayGift = false;
            while ($row = mysqli_fetch_array($resultGift)) {
                $hayGift = true;
                $totalGift += $row['valor_gift'];
            ?>
                <tr>
                    <td colspan="4"><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo number_format($row['valor_gift'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hayGift): ?>
                <tr><td colspan="5">Sin ventas Gift Card en el período.</td></tr>
            <?php endif; ?>
            <tr>
                <td colspan="4" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL VENTA NETA</td>
                <td><strong><?php echo number_format($totalVentaNeta, 2) ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL IVA</td>
                <td><strong><?php echo number_format($totalIva, 2) ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL VENTA GIFT</td>
                <td><strong><?php echo number_format($totalGift, 2) ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL COMISION</td>
                <td><strong><?php echo number_format($totalComision, 2) ?></strong></td>
            </tr>
        </table>
    <?php
        break;

    case 'ventas por locales liquidacion':
        $marca = (int)($_GET['marca'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);
        if ($marca <= 0 || $anio <= 0 || $mes <= 0) {
            echo "<table><tr><td>Debe seleccionar marca, año y mes.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="2" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('VENTAS POR LOCALES (LIQUIDACION) - ' . $mes . '/' . $anio) ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">LOCAL</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR</td>
            </tr>
            <?php
            $query = "SELECT COALESCE(l.loc_nombre, l.loc_direccion) AS loc_label, SUM(con.con_valor_total) AS valor
                      FROM consumo con
                      JOIN local l ON con.loc_id = l.loc_id
                      WHERE l.mar_id = $marca
                        AND YEAR(con.con_fecha) = $anio AND MONTH(con.con_fecha) = $mes
                      GROUP BY l.loc_id
                      ORDER BY loc_label ASC";
            $result = mysqli_query($mysqli, $query);
            $totalVenta = 0;
            $totalIva = 0;
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
                $totalVenta += $row['valor'];
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['loc_label']) ?></td>
                    <td><?php echo number_format($row['valor'], 2) ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="2">No hay ventas registradas para esta marca en el período.</td></tr>
            <?php else:
                $q_iva = mysqli_query($mysqli, "SELECT SUM(con.con_iva) AS iva FROM consumo con JOIN local l ON con.loc_id = l.loc_id WHERE l.mar_id = $marca AND YEAR(con.con_fecha) = $anio AND MONTH(con.con_fecha) = $mes");
                $totalIva = (float)mysqli_fetch_assoc($q_iva)['iva'];
            ?>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL VENTA</td>
                    <td><strong><?php echo number_format($totalVenta, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">SUB TOTAL VENTA (SIN IVA)</td>
                    <td><strong><?php echo number_format($totalVenta - $totalIva, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">IVA</td>
                    <td><strong><?php echo number_format($totalIva, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">TOTAL FACT</td>
                    <td><strong><?php echo number_format($totalVenta, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    case 'reporte recargas':
        $cliente = (int)($_GET['cliente'] ?? 0);
        $empleado = (int)($_GET['empleado'] ?? 0);
        if ($cliente <= 0) {
            echo "<table><tr><td>Debe seleccionar un cliente.</td></tr></table>";
            break;
        }
    ?>
        <table border="1" class="table table-bordered">
            <tr><td colspan="5" style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;"><?php echo utf8_decode('REPORTE RECARGAS (HISTORIAL DE CUPO)') ?></td></tr>
            <tr>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">FECHA</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">EMPLEADO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR ANTERIOR</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">VALOR NUEVO</td>
                <td style="background-color:#6d1b3a;color:#ffffff;font-weight:bold;">REALIZADO POR</td>
            </tr>
            <?php
            $where = ["per.cli_id = $cliente", "tra.tra_campo = 'per_cupo_asignado'"];
            if ($empleado > 0) $where[] = "tra.per_id = $empleado";
            $whereSql = implode(' AND ', $where);

            $query = "SELECT tra.tra_fecha, tra.tra_valor_anterior, tra.tra_valor_nuevo, per.per_nombre, u.name_user
                      FROM personal_trazabilidad tra
                      JOIN personal per ON tra.per_id = per.per_id
                      LEFT JOIN usuario u ON tra.id_user = u.id_user
                      WHERE $whereSql
                      ORDER BY tra.tra_fecha DESC";
            $result = mysqli_query($mysqli, $query);
            $hay_datos = false;
            while ($row = mysqli_fetch_array($result)) {
                $hay_datos = true;
            ?>
                <tr>
                    <td><?php echo $row['tra_fecha'] ?></td>
                    <td><?php echo utf8_decode($row['per_nombre']) ?></td>
                    <td><?php echo $row['tra_valor_anterior'] ?></td>
                    <td><?php echo $row['tra_valor_nuevo'] ?></td>
                    <td><?php echo $row['name_user'] !== null ? utf8_decode($row['name_user']) : '' ?></td>
                </tr>
            <?php
            }
            if (!$hay_datos): ?>
                <tr><td colspan="5">No hay cambios de cupo registrados para los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </table>
    <?php
        break;

    default:
        # code...
        break;
}

?>