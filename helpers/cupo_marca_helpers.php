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

if (!function_exists('cupoValidarPorMarca')) {
    // Decodifica y valida un payload cupo_por_marca (JSON: {mar_id: monto, ...}) contra
    // los topes configurados del convenio. Rechaza cualquier marca sin tope configurado
    // (nunca se trata como "ilimitado") y cualquier monto que exceda su tope.
    // $requiereAlMenosUna = true exige que al menos una marca tenga un monto positivo
    // (usado al crear un empleado; no se exige al editar, donde un no-op es válido).
    // Devuelve array('ok' => bool, 'mensaje' => string|null, 'cupo_por_marca' => array).
    // 'cupo_por_marca' en caso de error viene vacío — el llamador no debe usarlo si 'ok' es false.
    function cupoValidarPorMarca($mysqli, $cli_id, $rawJson, $requiereAlMenosUna) {
        $cupoPorMarca = json_decode($rawJson !== null && $rawJson !== '' ? $rawJson : '{}', true);
        if (!is_array($cupoPorMarca)) {
            $cupoPorMarca = array();
        }

        $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
        $algunaValida = false;

        foreach ($cupoPorMarca as $mar_id => $monto) {
            $monto = (float)$monto;
            if ($monto <= 0) {
                continue;
            }
            if (!isset($maximos[(int)$mar_id])) {
                return array('ok' => false, 'mensaje' => 'El convenio no tiene un tope configurado para esa marca — no se puede asignar cupo ahí', 'cupo_por_marca' => array());
            }
            $tope = $maximos[(int)$mar_id];
            if ($tope > 0 && $monto > $tope) {
                return array('ok' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')', 'cupo_por_marca' => array());
            }
            $algunaValida = true;
        }

        if ($requiereAlMenosUna && !$algunaValida) {
            return array('ok' => false, 'mensaje' => 'Asigne un cupo en al menos una marca', 'cupo_por_marca' => array());
        }

        return array('ok' => true, 'mensaje' => null, 'cupo_por_marca' => $cupoPorMarca);
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
        cupoAsegurarColumnasEstado($mysqli);
        $cli_id = (int)$cli_id;

        // Editar los topes borra y reinserta las filas: hay que conservar qué marcas estaban
        // bloqueadas para el convenio, o editar un monto las reactivaría sin que nadie lo note.
        $estados = array();
        $prev = $mysqli->prepare("SELECT mar_id, ccm_estado FROM cliente_cupo_marca WHERE cli_id = ?");
        $prev->bind_param('i', $cli_id);
        $prev->execute();
        $resPrev = $prev->get_result();
        while ($r = $resPrev->fetch_assoc()) {
            $estados[(int)$r['mar_id']] = $r['ccm_estado'];
        }

        $del = $mysqli->prepare("DELETE FROM cliente_cupo_marca WHERE cli_id = ?");
        $del->bind_param('i', $cli_id);
        $del->execute();

        foreach ($montosPorMarca as $mar_id => $monto) {
            $mar_id = (int)$mar_id;
            $monto  = (float)$monto;
            if ($monto <= 0) {
                continue;
            }
            $estado = (isset($estados[$mar_id]) && $estados[$mar_id] === 'bloqueado') ? 'bloqueado' : 'activo';
            $ins = $mysqli->prepare("INSERT INTO cliente_cupo_marca (cli_id, mar_id, ccm_monto_max, ccm_estado) VALUES (?, ?, ?, ?)");
            $ins->bind_param('iids', $cli_id, $mar_id, $monto, $estado);
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

if (!function_exists('cupoAsegurarColumnasEstado')) {
    // pcm_estado / pcm_tarjeta (migrations/bloque23): estado y tarjeta de la persona
    // en cada marca, como en el sistema viejo. Se crean solas la primera vez que
    // se necesitan, para que un deploy no dependa de haber corrido el .sql antes.
    // También los bloqueos de convenio (cliente.cli_estado) y de una marca del convenio
    // (cliente_cupo_marca.ccm_estado) + su historial (cliente_trazabilidad), migrations/bloque24.
    function cupoAsegurarColumnasEstado($mysqli) {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;

        // Una sola consulta cuando todo ya existe (el caso normal).
        $res = $mysqli->query(
            "SELECT
               (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND ((TABLE_NAME = 'personal_cupo_marca' AND COLUMN_NAME IN ('pcm_estado', 'pcm_tarjeta'))
                     OR (TABLE_NAME = 'cliente_cupo_marca' AND COLUMN_NAME = 'ccm_estado')
                     OR (TABLE_NAME = 'cliente' AND COLUMN_NAME = 'cli_estado'))) AS cols,
               (SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cliente_trazabilidad') AS tabla"
        );
        $row = $res ? $res->fetch_assoc() : null;
        if ($row && (int)$row['cols'] === 4 && (int)$row['tabla'] === 1) {
            return;
        }

        require_once __DIR__ . '/db_helpers.php';
        agregarColumnaSiNoExiste($mysqli, 'personal_cupo_marca', 'pcm_estado', "VARCHAR(10) NOT NULL DEFAULT 'activo'");
        agregarColumnaSiNoExiste($mysqli, 'personal_cupo_marca', 'pcm_tarjeta', 'VARCHAR(20) NULL');
        agregarColumnaSiNoExiste($mysqli, 'cliente_cupo_marca', 'ccm_estado', "VARCHAR(10) NOT NULL DEFAULT 'activo'");
        agregarColumnaSiNoExiste($mysqli, 'cliente', 'cli_estado', "VARCHAR(10) NOT NULL DEFAULT 'activo'");
        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS cliente_trazabilidad (
               tra_id INT NOT NULL AUTO_INCREMENT,
               cli_id INT NOT NULL,
               id_user INT NOT NULL,
               tra_campo VARCHAR(50) NOT NULL,
               tra_campo_label VARCHAR(100) NOT NULL,
               tra_valor_anterior VARCHAR(255) NULL,
               tra_valor_nuevo VARCHAR(255) NULL,
               tra_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (tra_id),
               KEY idx_ctra_cli (cli_id, tra_fecha)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}

if (!function_exists('cupoConvenioEstado')) {
    // 'activo' | 'bloqueado' — bloqueo de TODO el convenio (cliente.cli_estado).
    function cupoConvenioEstado($mysqli, $cli_id) {
        cupoAsegurarColumnasEstado($mysqli);
        $cli_id = (int)$cli_id;
        $stmt = $mysqli->prepare("SELECT cli_estado FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && $row['cli_estado'] === 'bloqueado') ? 'bloqueado' : 'activo';
    }
}

if (!function_exists('cupoConvenioMarcaEstado')) {
    // 'activo' | 'bloqueado' — una marca bloqueada para TODO el convenio (cliente_cupo_marca.ccm_estado).
    // Sin fila => 'activo' (la falta de tope ya la rechaza cupoValidarPorMarca, no es asunto de este estado).
    function cupoConvenioMarcaEstado($mysqli, $cli_id, $mar_id) {
        cupoAsegurarColumnasEstado($mysqli);
        $cli_id = (int)$cli_id;
        $mar_id = (int)$mar_id;
        $stmt = $mysqli->prepare("SELECT ccm_estado FROM cliente_cupo_marca WHERE cli_id = ? AND mar_id = ?");
        $stmt->bind_param('ii', $cli_id, $mar_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && $row['ccm_estado'] === 'bloqueado') ? 'bloqueado' : 'activo';
    }
}

if (!function_exists('cupoMarcasDelConvenio')) {
    // Marcas con tope del convenio (modo 'marca'): mar_id, nombre, tope, estado y cuántas personas
    // (no archivadas) tienen cupo/tarjeta en esa marca.
    function cupoMarcasDelConvenio($mysqli, $cli_id) {
        cupoAsegurarColumnasEstado($mysqli);
        $cli_id = (int)$cli_id;
        $out = array();
        $stmt = $mysqli->prepare(
            "SELECT m.mar_id, m.mar_descripcion, c.ccm_monto_max, c.ccm_estado,
                    (SELECT COUNT(*) FROM personal_cupo_marca x JOIN personal p ON p.per_id = x.per_id
                      WHERE p.cli_id = c.cli_id AND x.mar_id = c.mar_id AND p.per_estado != 'archivado') AS personas
             FROM cliente_cupo_marca c JOIN marca m ON m.mar_id = c.mar_id
             WHERE c.cli_id = ? ORDER BY m.mar_descripcion ASC"
        );
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[] = array(
                'mar_id'          => (int)$row['mar_id'],
                'mar_descripcion' => $row['mar_descripcion'],
                'tope'            => (float)$row['ccm_monto_max'],
                'estado'          => ($row['ccm_estado'] === 'bloqueado') ? 'bloqueado' : 'activo',
                'personas'        => (int)$row['personas'],
            );
        }
        return $out;
    }
}

if (!function_exists('cupoRegistrarTrazaConvenio')) {
    // Deja constancia de un bloqueo/activación de convenio o de una de sus marcas.
    function cupoRegistrarTrazaConvenio($mysqli, $cli_id, $id_user, $campo, $label, $anterior, $nuevo) {
        cupoAsegurarColumnasEstado($mysqli);
        $cli_id  = (int)$cli_id;
        $id_user = (int)$id_user;
        $anterior = substr((string)$anterior, 0, 255);
        $nuevo    = substr((string)$nuevo, 0, 255);
        $stmt = $mysqli->prepare(
            "INSERT INTO cliente_trazabilidad (cli_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iissss', $cli_id, $id_user, $campo, $label, $anterior, $nuevo);
        $stmt->execute();
    }
}

if (!function_exists('cupoEmpleadoEnMarca')) {
    // array('asignado'=>x,'disponible'=>y,'estado'=>'activo'|'bloqueado') de un empleado en una marca.
    // Sin fila => cupo 0 implícito (regla de negocio confirmada con cliente).
    function cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id) {
        cupoAsegurarColumnasEstado($mysqli);
        $per_id = (int)$per_id;
        $mar_id = (int)$mar_id;
        $stmt = $mysqli->prepare("SELECT pcm_asignado, pcm_disponible, pcm_estado FROM personal_cupo_marca WHERE per_id = ? AND mar_id = ?");
        $stmt->bind_param('ii', $per_id, $mar_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return array('asignado' => 0.0, 'disponible' => 0.0, 'estado' => 'activo');
        }
        return array(
            'asignado'   => (float)$row['pcm_asignado'],
            'disponible' => (float)$row['pcm_disponible'],
            'estado'     => ($row['pcm_estado'] === 'bloqueado') ? 'bloqueado' : 'activo',
        );
    }
}

if (!function_exists('cupoEmpleadoDetalleMarcas')) {
    // Filas para el modal "Cupo por marca" de Clientes > Personal: una por cada
    // marca donde la persona tiene tarjeta (marca, tarjeta, estado, cupo, saldo).
    function cupoEmpleadoDetalleMarcas($mysqli, $per_id) {
        cupoAsegurarColumnasEstado($mysqli);
        $per_id = (int)$per_id;
        $out = array();
        $stmt = $mysqli->prepare(
            "SELECT m.mar_id, m.mar_descripcion, p.pcm_tarjeta, p.pcm_estado, p.pcm_asignado, p.pcm_disponible
             FROM personal_cupo_marca p JOIN marca m ON m.mar_id = p.mar_id
             WHERE p.per_id = ? ORDER BY m.mar_descripcion ASC"
        );
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[] = array(
                'mar_id'          => (int)$row['mar_id'],
                'mar_descripcion' => $row['mar_descripcion'],
                'tarjeta'         => $row['pcm_tarjeta'],
                'estado'          => ($row['pcm_estado'] === 'bloqueado') ? 'bloqueado' : 'activo',
                'asignado'        => (float)$row['pcm_asignado'],
                'disponible'      => (float)$row['pcm_disponible'],
            );
        }
        return $out;
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
