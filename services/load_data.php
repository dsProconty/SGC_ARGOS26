<?php
require_once('../vendor/econea/nusoap/src/nusoap.php');
require_once('../config/database.php');

ini_set('max_execution_time', 0);

$action = $_GET['action'];

switch ($action) {
    case 'carga_data':
        $estado = 'adeuda';
        $fechainicio = '2020-04-01';
        $fechafin = date('Y-m-d');

        //url del webservice
        $wsdl = "http://ibrisystemas.com/cardcontrol/webservice/wsibrisystemas.php?wsdl";

        // En local el webservice no está disponible, se omite la carga
        $esLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
        if ($esLocal) {
            $resultado = [];
        } else {
            //instanciando un nuevo objeto cliente para consumir el webservice
            $client = new nusoap_client($wsdl, 'wsdl');

            //pasando los parámetros a un array
            $param = array('estadopago' => $estado, 'fechainicio' => $fechainicio, 'fechafin' => $fechafin);

            //llamando al método y pasándole el array con los parámetros
            $resultado = $client->call('consultarTransaciones', $param);
            $err = $client->getError();
            if ($err) {
                echo $err;
            }
        }

        $array_cant = count($resultado);

        for ($i = 0; $i < $array_cant; $i++) {

            $busqueda_existe = "SELECT cli_descripcion from cliente where cli_descripcion ='" . utf8_encode($resultado[$i]['Empresa']) . "'";
            $response = mysqli_query($mysqli, $busqueda_existe) or die('error: ' . mysqli_error($mysqli));
            $cant_cliente = mysqli_num_rows($response);
            if ($cant_cliente == 0) {
                $query = "INSERT INTO cliente(cli_descripcion,cli_ciudad,cli_contacto,cli_email,cli_dia_corte,cli_telefono)
                        values('" . utf8_encode($resultado[$i]['Empresa']) . "','" . utf8_encode($resultado[$i]['Ciudad']) . "','" . utf8_encode($resultado[$i]['Contacto']) . "','" . utf8_encode($resultado[$i]['Email']) . "','" . utf8_encode($resultado[$i]['DiaCorte']) . "','" . utf8_encode($resultado[$i]['Telefono']) . "')";
                echo $query .'<br/>';
                $row = mysqli_query($mysqli, $query);

                $busqueda_id = "SELECT cli_id from cliente where cli_descripcion = '" . utf8_encode($resultado[$i]['Empresa']) . "'";
                $resultado_busqueda = mysqli_query($mysqli, $busqueda_id);
                $row = mysqli_fetch_array($resultado_busqueda);
                $cli_id = $row['cli_id'];
                $busqueda_personal = "SELECT per_id from personal where per_documento = '" . utf8_encode($resultado[$i]['Documento']) . "'";
                $result_personal = mysqli_query($mysqli, $busqueda_personal);
                $row_personal = mysqli_fetch_array($result_personal);
                if (mysqli_num_rows($result_personal) > 0) {
                    $id_personal = $row_personal['per_id'];
                } else {
                    $id_personal = '1';
                }

                $cant_personal = mysqli_num_rows($result_personal);

                if ($cant_personal == 0) {
                    $query_Id_Personal = "SELECT max(per_id) as max from personal";
                    $resIdPersonal = mysqli_query($mysqli, $query_Id_Personal);
                    $rowId = mysqli_fetch_array($resIdPersonal);
                    if ($rowId['max'] != '') {
                        $id_personal = $rowId['max'] + 1;
                    } else {
                        $id_personal = '1';
                    }

                    $query_Ins_Personal = "INSERT into personal(per_id,per_nombre, per_documento, cli_id) 
                                values('$id_personal','" . utf8_encode($resultado[$i]['Nombre']) . "','" . utf8_encode($resultado[$i]['Documento']) . "'," . "'$cli_id'" . ")";
                    $result_Ins = mysqli_query($mysqli, $query_Ins_Personal);
                }

                $query_bus_marca = "SELECT mar_id from marca where mar_descripcion = '" . utf8_encode($resultado[$i]['Marca']) . "'";
                $result_bus_marca = mysqli_query($mysqli, $query_bus_marca);
                $row_bus_marca = mysqli_fetch_array($result_bus_marca);
                $cant_marca = mysqli_num_rows($result_bus_marca);
                $id_marca = '';
                if ($cant_marca == 0) {
                    $query_Id_Marca = "SELECT max(mar_id) as max_marca from marca";
                    $resIDMarca = mysqli_query($mysqli, $query_Id_Marca);
                    $rowId = mysqli_fetch_array($resIDMarca);
                    if ($rowId['max_marca'] != '') {
                        $id_marca = $rowId['max_marca'] + 1;
                    } else {
                        $id_marca = '1';
                    }

                    $query_Ins_Marca = "INSERT INTO marca(mar_id,mar_descripcion) values('$id_marca','" . utf8_encode($resultado[$i]['Marca']) . "')";

                    mysqli_query($mysqli, $query_Ins_Marca);
                } else {
                    $id_marca = $row_bus_marca['mar_id'];
                }

                $query_bus_local = "SELECT loc_id from local where loc_direccion = '" . utf8_encode($resultado[$i]['Local']) . "'";
                $result_bus_local = mysqli_query($mysqli, $query_bus_local) or die('error:' . mysqli_error($mysqli));
                $row_bus_local = mysqli_fetch_array($result_bus_local);
                $cant_local = mysqli_num_rows($result_bus_local);
                $id_local = '';
                if ($cant_local == 0) {
                    $query_Id_Marca = "SELECT max(loc_id) as max_local from local";
                    $resIDMarca = mysqli_query($mysqli, $query_Id_Marca);
                    $rowId = mysqli_fetch_array($resIDMarca);
                    if ($rowId['max_local'] != '') {
                        $id_local = $rowId['max_local'] + 1;
                    } else {
                        $id_local = '1';
                    }

                    $query_Ins_Local = "INSERT INTO local(loc_id,loc_direccion,mar_id)
                                values('$id_local','" . utf8_encode($resultado[$i]['Local']) . "','$id_marca')";
                    mysqli_query($mysqli, $query_Ins_Local);
                } else {
                    $id_local = $row_bus_local['loc_id'];
                }

                $queryIdConsumo = "SELECT con_id from consumo where id_transaccion = '" . utf8_encode($resultado[$i]['IdTransaccion']) . "'";

                $resId = mysqli_query($mysqli, $queryIdConsumo);

                $cantId = mysqli_num_rows($resId);

                if ($cantId = 0) {

                    $queryConsumo = "INSERT INTO consumo(con_fecha,con_hora,con_numero_tarjeta,con_valor_neto,
                                                con_iva,con_valor_total,con_autorizacion,con_estado,
                                                loc_id,per_id,id_transaccion)
                            values('" . utf8_encode($resultado[$i]['Fecha']) . "','" . utf8_encode($resultado[$i]['Hora']) . "',
                            '" . utf8_encode($resultado[$i]['Tarjeta']) . "','" . utf8_encode($resultado[$i]['ValorNeto']) . "',
                            '" . utf8_encode($resultado[$i]['Iva']) . "','" . utf8_encode($resultado[$i]['ValorTotal']) . "',
                            '" . utf8_encode($resultado[$i]['Autorizacion']) . "','pendiente','$id_local','$id_personal','" . utf8_encode($resultado[$i]['IdTransaccion']) . "')";

                    mysqli_query($mysqli, $queryConsumo) or die('error: ' . mysqli_error($mysqli));
                }
            } else {
                $busqueda_id = "SELECT cli_id from cliente where cli_descripcion = '" . utf8_encode($resultado[$i]['Empresa']) . "'";
                $resultado_busqueda = mysqli_query($mysqli, $busqueda_id);
                $row = mysqli_fetch_array($resultado_busqueda);
                $cli_id = $row['cli_id'];
                $busqueda_personal = "SELECT per_id from personal where per_documento = '" . utf8_encode($resultado[$i]['Documento']) . "'";
                $result_personal = mysqli_query($mysqli, $busqueda_personal);
                $cant_personal = mysqli_num_rows($result_personal);

                $row_personal = mysqli_fetch_array($result_personal);
                if (mysqli_num_rows($result_personal) > 0) {
                    $id_personal = $row_personal['per_id'];
                } else {
                    $id_personal = '1';
                }

                if ($cant_personal == 0) {
                    $query_Id_Personal = "SELECT max(per_id) as max from personal";
                    $resIdPersonal = mysqli_query($mysqli, $query_Id_Personal);
                    $rowId = mysqli_fetch_array($resIdPersonal);
                    if ($rowId['max'] != '') {
                        $id_personal = $rowId['max'] + 1;
                    } else {
                        $id_personal = '1';
                    }

                    $query_Ins_Personal = "INSERT into personal(per_id,per_nombre, per_documento, cli_id) 
                                values('$id_personal','" . utf8_encode($resultado[$i]['Nombre']) . "','" . utf8_encode($resultado[$i]['Documento']) . "'," . "'$cli_id'" . ")";
                    $result_Ins = mysqli_query($mysqli, $query_Ins_Personal);
                }

                $query_bus_marca = "SELECT mar_id from marca where mar_descripcion = '" . utf8_encode($resultado[$i]['Marca']) . "'";
                $result_bus_marca = mysqli_query($mysqli, $query_bus_marca);
                $row_bus_marca = mysqli_fetch_array($result_bus_marca);
                $cant_marca = mysqli_num_rows($result_bus_marca);
                $id_marca = '';
                if ($cant_marca == 0) {
                    $query_Id_Marca = "SELECT max(mar_id) as max_marca from marca";
                    $resIDMarca = mysqli_query($mysqli, $query_Id_Marca);
                    $rowId = mysqli_fetch_array($resIDMarca);
                    if ($rowId['max_marca'] != '') {
                        $id_marca = $rowId['max_marca'] + 1;
                    } else {
                        $id_marca = '1';
                    }

                    $query_Ins_Marca = "INSERT INTO marca(mar_id,mar_descripcion) values('$id_marca','" . utf8_encode($resultado[$i]['Marca']) . "')";

                    mysqli_query($mysqli, $query_Ins_Marca);
                } else {
                    $id_marca = $row_bus_marca['mar_id'];
                }

                $query_bus_local = "SELECT loc_id from local where loc_direccion = '" . utf8_encode($resultado[$i]['Local']) . "'";
                $result_bus_local = mysqli_query($mysqli, $query_bus_local) or die('error:' . mysqli_error($mysqli));
                $row_bus_local = mysqli_fetch_array($result_bus_local);
                $cant_local = mysqli_num_rows($result_bus_local);
                $id_local = '';
                if ($cant_local == 0) {
                    $query_Id_Marca = "SELECT max(loc_id) as max_local from local";
                    $resIDMarca = mysqli_query($mysqli, $query_Id_Marca);
                    $rowId = mysqli_fetch_array($resIDMarca);
                    if ($rowId['max_local'] != '') {
                        $id_local = $rowId['max_local'] + 1;
                    } else {
                        $id_local = '1';
                    }

                    $query_Ins_Local = "INSERT INTO local(loc_id,loc_direccion,mar_id)
                                values('$id_local','" . utf8_encode($resultado[$i]['Local']) . "','$id_marca')";
                    mysqli_query($mysqli, $query_Ins_Local);
                } else {
                    $id_local = $row_bus_local['loc_id'];
                }

                $queryIdConsumo = "SELECT con_id from consumo where id_transaccion = '" . utf8_encode($resultado[$i]['IdTransaccion']) . "'";

                $resId = mysqli_query($mysqli, $queryIdConsumo) or die('error con:' . mysqli_error($mysqli));

                $cantId = mysqli_num_rows($resId);

                if ($cantId == 0) {
                    $queryConsumo = "INSERT INTO consumo(con_fecha,con_hora,con_numero_tarjeta,con_valor_neto,
                                            con_iva,con_valor_total,con_autorizacion,con_estado,
                                            loc_id,per_id,id_transaccion)
                        values('" . utf8_encode($resultado[$i]['Fecha']) . "','" . utf8_encode($resultado[$i]['Hora']) . "',
                        '" . utf8_encode($resultado[$i]['Tarjeta']) . "','" . utf8_encode($resultado[$i]['ValorNeto']) . "',
                        '" . utf8_encode($resultado[$i]['Iva']) . "','" . utf8_encode($resultado[$i]['ValorTotal']) . "',
                        '" . utf8_encode($resultado[$i]['Autorizacion']) . "','pendiente','$id_local','$id_personal','" . utf8_encode($resultado[$i]['IdTransaccion']) . "')";

                    mysqli_query($mysqli, $queryConsumo) or die('error: ' . mysqli_error($mysqli));
                }


            }
        }
        session_start();
        if($_SESSION['permisos_acceso']=='Operador'){
            echo "<script language=Javascript> location.href=\"../main.php?module=gestiones&cartera=30\"; </script>"; 
        }else{
            echo "<script language=Javascript> location.href=\"../main.php?module=dashboard\"; </script>"; 
        }
        break;
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
