<?php
// Cartera de Cobranza: una fila por convenio y tipo (30 / 60 / 90 / 91 = +90 días) en la tabla `cartera`,
// con la suma de los consumos de la ventana de ese tipo (cli_valor_pagar). Las ventanas dependen del día de
// corte del convenio (cliente.cli_dia_corte): inicio INCLUSIVO, fin EXCLUSIVO.
//
// Lo usan: cron/actualizar_cartera.php (cron del cPanel) y services/load_data.php?action=carga_cartera
// (Super Admin, a mano).
//
// Reglas (antes cada corrida pisaba todo y devolvía las carteras a "sin_gestion"):
//   - Si el convenio no tiene fila de ese tipo y hay consumos en la ventana  -> se crea (sin_gestion).
//   - Si ya tiene fila y la ventana es la MISMA                              -> solo se refresca el monto;
//       el estado (pendiente / cobrada / compromiso...) y la fecha de ingreso NO se tocan.
//   - Si ya tiene fila y la ventana CAMBIÓ (empezó otro ciclo de corte)      -> se actualiza la ventana y
//       vuelve a sin_gestion, porque es una deuda nueva por gestionar.
//   - Si no hay consumos en la ventana no se toca nada.
// El tipo 91 (+90) guarda en car_fecha_fin el inicio de la ventana de 90 (suma consumos ANTERIORES a esa fecha),
// igual que lo espera la pantalla de gestión; antes el UPDATE guardaba otra fecha y la lista no cuadraba con adentro.
//
// Sin type hints ni sintaxis de PHP nuevo: producción corre PHP < 7.1.

if (!function_exists('cartera_suma_consumos')) {
    // Suma de consumos de un convenio. $ini = null => sin límite inferior.
    function cartera_suma_consumos($mysqli, $cli_id, $ini, $fin) {
        $cli_id = (int)$cli_id;
        $sql = "SELECT SUM(con.con_valor_total) AS monto_total
                FROM consumo con
                JOIN personal p ON con.per_id = p.per_id
                WHERE p.cli_id = $cli_id AND con.con_fecha < '" . mysqli_real_escape_string($mysqli, $fin) . "'";
        if ($ini !== null) {
            $sql .= " AND con.con_fecha >= '" . mysqli_real_escape_string($mysqli, $ini) . "'";
        }
        $res = mysqli_query($mysqli, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (!$row || $row['monto_total'] === null || $row['monto_total'] === '') {
            return null;
        }
        return (float)$row['monto_total'];
    }
}

if (!function_exists('cartera_actualizar_tipo')) {
    // Devuelve 'creada' | 'ciclo_nuevo' | 'refrescada' | 'sin_consumos'
    function cartera_actualizar_tipo($mysqli, $cli_id, $tipo, $ini, $fin, $hoy) {
        $cli_id = (int)$cli_id;
        $tipo   = (int)$tipo;
        $monto  = cartera_suma_consumos($mysqli, $cli_id, $ini, $fin);
        if ($monto === null) {
            return 'sin_consumos';
        }
        $montoSql = number_format($monto, 2, '.', '');
        $iniSql   = ($ini === null) ? 'NULL' : "'" . mysqli_real_escape_string($mysqli, $ini) . "'";
        $finSql   = "'" . mysqli_real_escape_string($mysqli, $fin) . "'";
        $hoySql   = mysqli_real_escape_string($mysqli, $hoy);

        $res = mysqli_query($mysqli, "SELECT car_id, car_fecha_inicio, car_fecha_fin FROM cartera WHERE cli_id = $cli_id AND car_tipo = '$tipo' ORDER BY car_id ASC LIMIT 1");
        $car = $res ? mysqli_fetch_assoc($res) : null;

        if (!$car) {
            mysqli_query($mysqli, "INSERT INTO cartera (car_fecha_inicio, car_fecha_fin, car_estado, car_fecha_ingreso, cli_valor_pagar, cli_id, car_tipo)
                                   VALUES ($iniSql, $finSql, 'sin_gestion', '$hoySql', '$montoSql', $cli_id, '$tipo')");
            return 'creada';
        }

        $mismaVentana = ((string)$car['car_fecha_fin'] === (string)$fin)
            && ((string)$car['car_fecha_inicio'] === (string)($ini === null ? '' : $ini));
        $carId = (int)$car['car_id'];
        if ($mismaVentana) {
            mysqli_query($mysqli, "UPDATE cartera SET cli_valor_pagar = '$montoSql' WHERE car_id = $carId");
            return 'refrescada';
        }
        mysqli_query($mysqli, "UPDATE cartera SET car_fecha_inicio = $iniSql, car_fecha_fin = $finSql, car_estado = 'sin_gestion',
                                      car_fecha_ingreso = '$hoySql', cli_valor_pagar = '$montoSql' WHERE car_id = $carId");
        return 'ciclo_nuevo';
    }
}

if (!function_exists('cartera_actualizar')) {
    // Recorre todos los convenios. Devuelve array('convenios' => n, 'creada' => n, 'ciclo_nuevo' => n, 'refrescada' => n, 'sin_consumos' => n)
    function cartera_actualizar($mysqli) {
        $resumen = array('convenios' => 0, 'creada' => 0, 'ciclo_nuevo' => 0, 'refrescada' => 0, 'sin_consumos' => 0);
        $hoy = date('Y-m-d');
        $y = (int)date('Y');
        $m = (int)date('m');

        $res = mysqli_query($mysqli, "SELECT cli_id, cli_dia_corte FROM cliente");
        while ($cli = mysqli_fetch_assoc($res)) {
            $resumen['convenios']++;
            $dia = (int)$cli['cli_dia_corte'];
            if ($dia < 1) {
                $dia = 1;
            }

            $fecha30Fin = date('Y-m-d', mktime(0, 0, 0, $m,     $dia - 1, $y));
            $fecha30Ini = date('Y-m-d', mktime(0, 0, 0, $m - 1, $dia,     $y));
            $fecha60Fin = date('Y-m-d', mktime(0, 0, 0, $m - 1, $dia - 1, $y));
            $fecha60Ini = date('Y-m-d', mktime(0, 0, 0, $m - 2, $dia,     $y));
            $fecha90Fin = date('Y-m-d', mktime(0, 0, 0, $m - 2, $dia - 1, $y));
            $fecha90Ini = date('Y-m-d', mktime(0, 0, 0, $m - 3, $dia,     $y));

            $r = cartera_actualizar_tipo($mysqli, $cli['cli_id'], 30, $fecha30Ini, $fecha30Fin, $hoy);
            $resumen[$r]++;
            $r = cartera_actualizar_tipo($mysqli, $cli['cli_id'], 60, $fecha60Ini, $fecha60Fin, $hoy);
            $resumen[$r]++;
            $r = cartera_actualizar_tipo($mysqli, $cli['cli_id'], 90, $fecha90Ini, $fecha90Fin, $hoy);
            $resumen[$r]++;
            // +90 días: todo lo anterior al inicio de la ventana de 90 (sin fecha de inicio)
            $r = cartera_actualizar_tipo($mysqli, $cli['cli_id'], 91, null, $fecha90Ini, $hoy);
            $resumen[$r]++;
        }
        return $resumen;
    }
}
