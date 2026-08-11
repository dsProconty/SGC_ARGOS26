<?php
// CU-01: funciones compartidas para cupos "global" vs "por marca", usadas
// desde ajax/clientes/clientes.php, ajax/portal_empresa/portal_empresa.php
// y ajax/pos/pos.php. Sin type hints ni short-list syntax a propósito —
// ver env.php: el servidor de producción corre PHP anterior a 7.1.

if (!function_exists('cupoMarcasActivas')) {
    // Catálogo de marcas, para poblar selects y columnas dinámicas.
    function cupoMarcasActivas($mysqli) {
        $marcas = array();
        $res = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca ORDER BY mar_descripcion ASC");
        while ($row = mysqli_fetch_assoc($res)) {
            $marcas[] = array('mar_id' => (int)$row['mar_id'], 'mar_descripcion' => $row['mar_descripcion']);
        }
        return $marcas;
    }
}

if (!function_exists('cupoObtenerModo')) {
    // array('modo' => 'global'|'marca', 'valor_global' => float)
    function cupoObtenerModo($mysqli, $cli_id) {
        $cli_id = (int)$cli_id;
        $stmt = $mysqli->prepare("SELECT cli_modo_cupo, cli_valor_beneficio FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return array('modo' => 'global', 'valor_global' => 0.0);
        }
        $modo = ($row['cli_modo_cupo'] === 'marca') ? 'marca' : 'global';
        return array('modo' => $modo, 'valor_global' => (float)$row['cli_valor_beneficio']);
    }
}

if (!function_exists('cupoMaximosPorMarca')) {
    // array mar_id => monto_max, para un convenio en modo 'marca'.
    function cupoMaximosPorMarca($mysqli, $cli_id) {
        $cli_id = (int)$cli_id;
        $out = array();
        $stmt = $mysqli->prepare("SELECT mar_id, ccm_monto_max FROM cliente_cupo_marca WHERE cli_id = ?");
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[(int)$row['mar_id']] = (float)$row['ccm_monto_max'];
        }
        return $out;
    }
}

if (!function_exists('cupoGuardarMaximosPorMarca')) {
    // Reemplaza los topes por marca de un convenio. $montosPorMarca es
    // array mar_id => monto (montos <= 0 se omiten, igual que una fila vacía).
    function cupoGuardarMaximosPorMarca($mysqli, $cli_id, $montosPorMarca) {
        $cli_id = (int)$cli_id;
        $del = $mysqli->prepare("DELETE FROM cliente_cupo_marca WHERE cli_id = ?");
        $del->bind_param('i', $cli_id);
        $del->execute();

        foreach ($montosPorMarca as $mar_id => $monto) {
            $mar_id = (int)$mar_id;
            $monto  = (float)$monto;
            if ($monto <= 0) {
                continue;
            }
            $ins = $mysqli->prepare("INSERT INTO cliente_cupo_marca (cli_id, mar_id, ccm_monto_max) VALUES (?, ?, ?)");
            $ins->bind_param('iid', $cli_id, $mar_id, $monto);
            $ins->execute();
        }
    }
}

if (!function_exists('cupoMarcaDeLocal')) {
    // mar_id del local, o null si el local no existe / no se recibió loc_id.
    function cupoMarcaDeLocal($mysqli, $loc_id) {
        $loc_id = (int)$loc_id;
        if (!$loc_id) {
            return null;
        }
        $stmt = $mysqli->prepare("SELECT mar_id FROM local WHERE loc_id = ?");
        $stmt->bind_param('i', $loc_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int)$row['mar_id'] : null;
    }
}

if (!function_exists('cupoEmpleadoEnMarca')) {
    // array('asignado'=>x,'disponible'=>y) de un empleado en una marca.
    // Sin fila => cupo 0 implícito (regla de negocio confirmada con cliente).
    function cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id) {
        $per_id = (int)$per_id;
        $mar_id = (int)$mar_id;
        $stmt = $mysqli->prepare("SELECT pcm_asignado, pcm_disponible FROM personal_cupo_marca WHERE per_id = ? AND mar_id = ?");
        $stmt->bind_param('ii', $per_id, $mar_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return array('asignado' => 0.0, 'disponible' => 0.0);
        }
        return array('asignado' => (float)$row['pcm_asignado'], 'disponible' => (float)$row['pcm_disponible']);
    }
}

if (!function_exists('cupoEmpleadoPorMarca')) {
    // array mar_id => array('asignado'=>x,'disponible'=>y) — todas las marcas
    // en las que el empleado tiene fila (para listados/resúmenes).
    function cupoEmpleadoPorMarca($mysqli, $per_id) {
        $per_id = (int)$per_id;
        $out = array();
        $stmt = $mysqli->prepare("SELECT mar_id, pcm_asignado, pcm_disponible FROM personal_cupo_marca WHERE per_id = ?");
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[(int)$row['mar_id']] = array(
                'asignado'   => (float)$row['pcm_asignado'],
                'disponible' => (float)$row['pcm_disponible'],
            );
        }
        return $out;
    }
}

if (!function_exists('cupoUpsertEmpleadoMarca')) {
    // Crea o actualiza el cupo asignado de un empleado en una marca. Ajusta
    // el disponible de forma proporcional al consumo ya existente (mismo
    // criterio que ya usa el cupo global: nuevo_disponible = max(0, nuevo_asignado - consumido)).
    // Devuelve el nuevo disponible.
    function cupoUpsertEmpleadoMarca($mysqli, $per_id, $mar_id, $nuevoAsignado) {
        $per_id        = (int)$per_id;
        $mar_id        = (int)$mar_id;
        $nuevoAsignado = (float)$nuevoAsignado;

        $actual    = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id);
        $consumido = $actual['asignado'] - $actual['disponible'];
        $nuevoDisponible = $nuevoAsignado - $consumido;
        if ($nuevoDisponible < 0) {
            $nuevoDisponible = 0;
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO personal_cupo_marca (per_id, mar_id, pcm_asignado, pcm_disponible)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE pcm_asignado = VALUES(pcm_asignado), pcm_disponible = VALUES(pcm_disponible)"
        );
        $stmt->bind_param('iidd', $per_id, $mar_id, $nuevoAsignado, $nuevoDisponible);
        $stmt->execute();

        return $nuevoDisponible;
    }
}

if (!function_exists('cupoDescontarEmpleadoMarca')) {
    // Descuenta un monto del disponible de un empleado en una marca (venta en POS).
    function cupoDescontarEmpleadoMarca($mysqli, $per_id, $mar_id, $monto) {
        $per_id = (int)$per_id;
        $mar_id = (int)$mar_id;
        $monto  = (float)$monto;
        $stmt = $mysqli->prepare("UPDATE personal_cupo_marca SET pcm_disponible = pcm_disponible - ? WHERE per_id = ? AND mar_id = ?");
        $stmt->bind_param('dii', $monto, $per_id, $mar_id);
        $stmt->execute();
    }
}

if (!function_exists('cupoDevolverEmpleadoMarca')) {
    // Devuelve un monto al disponible de un empleado en una marca (anulación
    // de venta en POS), sin superar el asignado.
    function cupoDevolverEmpleadoMarca($mysqli, $per_id, $mar_id, $monto) {
        $per_id = (int)$per_id;
        $mar_id = (int)$mar_id;
        $monto  = (float)$monto;
        $stmt = $mysqli->prepare(
            "UPDATE personal_cupo_marca SET pcm_disponible = LEAST(pcm_asignado, pcm_disponible + ?) WHERE per_id = ? AND mar_id = ?"
        );
        $stmt->bind_param('dii', $monto, $per_id, $mar_id);
        $stmt->execute();
    }
}
