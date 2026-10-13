<?php
require_once('../config/database.php');

ini_set('max_execution_time', 0);

$action = $_GET['action'];

switch ($action) {
    case 'carga_cartera':
        $ayer = mktime(0, 0, 0, date("Y"), date("m"), date("d") - 1);

        $queryCliCorte = "SELECT cli_id,cli_dia_corte,cli_descripcion from cliente";

        $resCliCorte = mysqli_query($mysqli, $queryCliCorte);

        while ($rowCli = mysqli_fetch_array($resCliCorte)) {
            if ($rowCli['cli_dia_corte'] == '0') {
                $diaCorte = '1';
            } else {
                $diaCorte = $rowCli['cli_dia_corte'];
            }

            $fecha30Fin = date("Y-m-d", mktime(0, 0, 0, date("m"), $diaCorte - 1, date("Y")));
            $fecha30Ini = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, $diaCorte, date("Y")));

            $fecha60Fin = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, $diaCorte - 1, date("Y")));
            $fecha60Ini = date("Y-m-d", mktime(0, 0, 0, date("m") - 2, $diaCorte, date("Y")));

            $fecha90Fin = date("Y-m-d", mktime(0, 0, 0, date("m") - 2, $diaCorte - 1, date("Y")));
            $fecha90Ini = date("Y-m-d", mktime(0, 0, 0, date("m") - 3, $diaCorte, date("Y")));

            $fechaHoy = date("Y-m-d");

            echo ('-----------------------<br/>');
            echo ($rowCli['cli_descripcion'] . '<br/>');
            echo ('30Ini:' . $fecha30Ini . '<br/>');
            echo ('30Fin:' . $fecha30Fin . '<br/>');
            echo ('60Ini:' . $fecha60Ini . '<br/>');
            echo ('60Fin:' . $fecha60Fin . '<br/>');
            echo ('90Ini:' . $fecha90Ini . '<br/>');
            echo ('90Fin:' . $fecha90Fin . '<br/>');

            $queryBus30 = "SELECT car_id from cartera where cli_id = '$rowCli[cli_id]' and car_tipo = '30'";

            $resQ = mysqli_query($mysqli, $queryBus30);

            if (mysqli_num_rows($resQ) == 0) {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha30Ini' and con.con_fecha < '$fecha30Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {

                    $queryCartera = "INSERT INTO cartera(car_fecha_inicio,car_fecha_fin,car_estado,car_fecha_ingreso,cli_valor_pagar,cli_id,car_tipo) 
                                    values('$fecha30Ini','$fecha30Fin','sin_gestion','$fechaHoy','$row[monto_total]','$rowCli[cli_id]','30')";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error cartera 30:' . mysqli_error($mysqli));
                }
            } else {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha30Ini' and con.con_fecha < '$fecha30Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {

                    $queryCartera = "UPDATE cartera set car_fecha_inicio = '$fecha30Ini', car_fecha_fin = '$fecha30Fin',
                                     car_estado = 'sin_gestion', car_fecha_ingreso = '$fechaHoy',cli_valor_pagar='$row[monto_total]'
                                     where cli_id = '$rowCli[cli_id]' and car_tipo = '30'";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error update cartera 30:' . mysqli_error($mysqli));
                }
            }


            $queryBus60 = "SELECT car_id from cartera where cli_id = '$rowCli[cli_id]' and car_tipo = '60'";

            $resQ = mysqli_query($mysqli, $queryBus60);

            if (mysqli_num_rows($resQ) == 0) {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha60Ini)' and con.con_fecha < '$fecha60Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {
                    $queryCartera = "INSERT INTO cartera(car_fecha_inicio,car_fecha_fin,car_estado,car_fecha_ingreso,cli_valor_pagar,cli_id,car_tipo) 
                    values('$fecha60Ini','$fecha60Fin','sin_gestion','$fechaHoy','$row[monto_total]','$rowCli[cli_id]','60')";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error cartera 60:' . mysqli_error($mysqli));
                }
            } else {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha60Ini' and con.con_fecha < '$fecha60Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {

                    $queryCartera = "UPDATE cartera set car_fecha_inicio = '$fecha60Ini', car_fecha_fin = '$fecha60Fin',
                                     car_estado = 'sin_gestion', car_fecha_ingreso = '$fechaHoy',cli_valor_pagar='$row[monto_total]'
                                     where cli_id = '$rowCli[cli_id]' and car_tipo = '60'";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error update cartera 60:' . mysqli_error($mysqli));
                }
            }


            $queryBus90 = "SELECT car_id from cartera where cli_id = '$rowCli[cli_id]' and car_tipo = '90'";

            $resQ = mysqli_query($mysqli, $queryBus90);

            if (mysqli_num_rows($resQ) == 0) {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha90Ini' and con.con_fecha < '$fecha90Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {
                    $queryCartera = "INSERT INTO cartera(car_fecha_inicio,car_fecha_fin,car_estado,car_fecha_ingreso,cli_valor_pagar,cli_id,car_tipo) 
                    values('$fecha90Ini','$fecha90Fin','sin_gestion','$fechaHoy','$row[monto_total]','$rowCli[cli_id]','90')";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error cartera 90:' . mysqli_error($mysqli));
                }
            } else {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha >= '$fecha90Ini' and con.con_fecha < '$fecha90Fin'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {

                    $queryCartera = "UPDATE cartera set car_fecha_inicio = '$fecha90Ini', car_fecha_fin = '$fecha90Fin',
                                     car_estado = 'sin_gestion', car_fecha_ingreso = '$fechaHoy',cli_valor_pagar='$row[monto_total]'
                                     where cli_id = '$rowCli[cli_id]' and car_tipo = '90'";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error update cartera 90:' . mysqli_error($mysqli));
                }
            }


            $queryBus91 = "SELECT car_id from cartera where cli_id = '$rowCli[cli_id]' and car_tipo = '91'";

            $resQ = mysqli_query($mysqli, $queryBus91);

            if (mysqli_num_rows($resQ) == 0) {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha < '$fecha90Ini'";


                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {
                    $queryCartera = "INSERT INTO cartera(car_fecha_fin,car_estado,car_fecha_ingreso,cli_valor_pagar,cli_id,car_tipo) 
                    values('$fecha90Ini','sin_gestion','$fechaHoy','$row[monto_total]','$rowCli[cli_id]','91')";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error cartera +90:' . mysqli_error($mysqli));
                }
            } else {
                $queryCartera = "SELECT sum(con_valor_total) as monto_total 
                from consumo con, personal p, cliente cli 
                where con.per_id = p.per_id and p.cli_id = cli.cli_id and cli.cli_id = '$rowCli[cli_id]'
                and con.con_fecha < '$fecha90Ini'";

                $resultCar = mysqli_query($mysqli, $queryCartera);

                $row = mysqli_fetch_array($resultCar);

                if ($row['monto_total'] != '') {

                    $queryCartera = "UPDATE cartera set car_fecha_fin = '$fecha90Fin',
                                     car_estado = 'sin_gestion', car_fecha_ingreso = '$fechaHoy',cli_valor_pagar='$row[monto_total]'
                                     where cli_id = '$rowCli[cli_id]' and car_tipo = '91'";

                    $resCar = mysqli_query($mysqli, $queryCartera) or die('error update cartera 91:' . mysqli_error($mysqli));
                }
            }
        }

        break;
}
