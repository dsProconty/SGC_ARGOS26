<?php
require_once "../../config/database.php";

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
                <td colspan=3 style="background-color:LIGHTSTEELBLUE"> DETALLE VENTAS</td>
            </tr>
            <tr>
                <td colspan="3" style="background-color:LIGHTSTEELBLUE">DESDE: <?php echo $fechaini; ?> HASTA: <?php echo $fechafin; ?></td>
            </tr>
            <tr>
                <td>Empresa</td>
                <td>Valor</td>
                <td>Propina</td>
            </tr>
            <?php

            $total = 0.00;

            $iva = 0.00;

            if($marca == 'TODOS'){
                $query = "SELECT loc_direccion, con_valor_total,con_iva,mar_descripcion from consumo c,local l,marca m 
                where c.loc_id = l.loc_id and c.con_fecha >= '$fechaini' and c.con_fecha<='$fechafin'
                and l.mar_id = m.mar_id group by l.loc_id";
            }else{
                $query = "SELECT loc_direccion, con_valor_total,con_iva,mar_descripcion from consumo c,local l,marca m 
                where c.loc_id = l.loc_id and c.con_fecha >= '$fechaini' and c.con_fecha<='$fechafin'
                and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' group by l.loc_id";
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
                <td style="background-color:LIGHTSTEELBLUE">EMPRESA</td>
                <td style="background-color:LIGHTSTEELBLUE">MARCA</td>
                <td style="background-color:LIGHTSTEELBLUE">DESDE</td>
                <td style="background-color:LIGHTSTEELBLUE">HASTA</td>
                <td style="background-color:LIGHTSTEELBLUE">TOTAL A PAGAR</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR COBRADO</td>
                <td style="background-color:LIGHTSTEELBLUE">SALDO</td>
                <td style="background-color:LIGHTSTEELBLUE">NUMERO COMPROBANTE O CHEQUE</td>
                <td style="background-color:LIGHTSTEELBLUE">ENTREGADO A</td>
                <td style="background-color:LIGHTSTEELBLUE">OBSERVACION</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,c.car_fecha_inicio,c.car_fecha_fin,
                            c.cli_valor_pagar,g.ges_observacion,u.name_user,sum(p.pag_monto) as pag_monto
            from pago p,gestion g,cartera c,cliente cli,usuario u
            where p.pag_id = g.pag_id and g.us_id = u.id_user
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and p.pag_fecha >= '$fechaini' and p.pag_fecha <= '$fechafin'
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
                <td style="background-color:LIGHTSTEELBLUE">EMPRESA</td>
                <td style="background-color:LIGHTSTEELBLUE">CARTERA</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,sum(p.pag_monto) as pag_monto,c.car_tipo
            from pago p,gestion g,cartera c,cliente cli
            where p.pag_id = g.pag_id
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and p.pag_fecha >= '$fechaini' and p.pag_fecha <= '$fechafin'
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
                <td style="background-color:LIGHTSTEELBLUE">EMPRESA</td>
                <td style="background-color:LIGHTSTEELBLUE">PERIODO</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,sum(p.pag_monto) as pag_monto,c.car_fecha_inicio,c.car_fecha_fin
            from pago p,gestion g,cartera c,cliente cli
            where p.pag_id = g.pag_id
            and g.car_id = c.car_id and c.cli_id = cli.cli_id and p.pag_fecha >= '$fechaini' and p.pag_fecha <= '$fechafin'
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
                <td style="background-color:LIGHTSTEELBLUE">TOTAL</td>
                <td></td>
                <td><?php echo $total; ?></td>
            </tr>
        </table>
    <?php
        break;
    case 'dinero por edades de cartera':
    ?>

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
                <td style="background-color:LIGHTSTEELBLUE">MARCA</td>
                <td style="background-color:LIGHTSTEELBLUE">TIPO</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR CARTERA</td>
                <td style="background-color:LIGHTSTEELBLUE">RECUPERADO</td>
                <td style="background-color:LIGHTSTEELBLUE">DEUDA ACTUAL</td>
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
                <td style="background-color:LIGHTSTEELBLUE">CLIENTE</td>
                <td style="background-color:LIGHTSTEELBLUE">CONSUMO TOTAL</td>
                <td style="background-color:LIGHTSTEELBLUE">FECHA ULT. CONSUMO</td>
                <td style="background-color:LIGHTSTEELBLUE">MARCA ULTIMO CONSUMO</td>
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
                <td style="background-color:LIGHTSTEELBLUE">CLIENTE</td>
                <td style="background-color:LIGHTSTEELBLUE">FECHA ULT. CONSUMO</td>
                <td style="background-color:LIGHTSTEELBLUE">MARCA ULTIMO CONSUMO</td>
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
                <td style="background-color:LIGHTSTEELBLUE">GESTOR</td>
                <td style="background-color:LIGHTSTEELBLUE">EMPRESA</td>
                <td style="background-color:LIGHTSTEELBLUE">CARTERA</td>
                <td style="background-color:LIGHTSTEELBLUE">TOTAL DEUDA</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR COBRADO</td>
                <td style="background-color:LIGHTSTEELBLUE">SALDO</td>
                <td style="background-color:LIGHTSTEELBLUE">OBSERVACION COBRANZA</td>
                <td style="background-color:LIGHTSTEELBLUE">FECHA PAGO</td>
                <td style="background-color:LIGHTSTEELBLUE">OBSERVACION GESTION</td>
            </tr>
            <?php

            $query = "SELECT c.car_id,cli.cli_descripcion,c.car_fecha_inicio,c.car_fecha_fin,c.car_tipo,
                    c.cli_valor_pagar,g.ges_observacion,u.name_user,p.pag_monto,p.pag_observacion,p.pag_fecha
                    from pago p,gestion g,cartera c,cliente cli,usuario u
                    where p.pag_id = g.pag_id and g.us_id = u.id_user
                    and g.car_id = c.car_id and c.cli_id = cli.cli_id and p.pag_fecha >= '$fechaini' and p.pag_fecha <= '$fechafin'
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
                <td colspan="5" style="background-color:LIGHTSTEELBLUE">
                    <?php echo utf8_decode('CONSUMOS DEL MES ' . $marca) ?>
                </td>
            </tr>
            <tr>
                <td colspan="5" style="background-color:LIGHTSTEELBLUE">
                    Fecha Inicio : <?php echo $inicioMes?> Fecha Fin: <?php echo $finMes ?>
                </td>
            </tr>
            <tr>
                <td style="background-color:LIGHTSTEELBLUE">EMPRESA</td>
                <td style="background-color:LIGHTSTEELBLUE">CLIENTE</td>
                <td style="background-color:LIGHTSTEELBLUE">FECHA</td>
                <td style="background-color:LIGHTSTEELBLUE">VALOR</td>
                <td style="background-color:LIGHTSTEELBLUE">PROPINA</td>
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

            while ($row = mysqli_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo utf8_decode($row['cli_descripcion']) ?></td>
                    <td><?php echo utf8_decode($row['per_nombre']) ?></td>
                    <td><?php echo utf8_decode($row['con_fecha'] . ' / ' . $row['con_hora']) ?></td>
                    <td><?php echo $row['con_valor_total'] ?></td>
                    <td></td>
                </tr>
            <?php
            }
            ?>
        </table>
<?php
        break;

    default:
        # code...
        break;
}

?>