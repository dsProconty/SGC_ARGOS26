# Cupos Globales y Diferenciados por Marca — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a convenio (`cliente` con beneficio `Cupo`) elegir entre un cupo **global** (compartido entre todas las marcas, comportamiento actual) o un cupo **diferenciado por marca** (montos independientes por empleado y marca), aplicado consistentemente en el alta/edición de empleados, la Carga Masiva, y el punto de venta.

**Architecture:** Dos tablas nuevas (`cliente_cupo_marca`, `personal_cupo_marca`) más una columna `cliente.cli_modo_cupo`. Toda la lógica de lectura/escritura de cupo por marca se centraliza en un archivo de funciones (`helpers/cupo_marca_helpers.php`) reutilizado desde los tres puntos que hoy tocan `per_cupo_asignado`/`per_cupo_disponible`: **Clientes** (admin), **Portal Empresa** (autoservicio) y **POS**. Cuando `cli_modo_cupo = 'global'`, el comportamiento y las columnas usadas son exactamente las de hoy — cero riesgo de regresión para convenios existentes.

**Tech Stack:** PHP + mysqli (sin framework), MySQL/MariaDB, jQuery + Bootstrap 4, SheetJS (`XLSX`) para Excel en el navegador.

---

## Restricciones del entorno (leer antes de escribir código)

1. **PHP < 7.1 en producción** (ver comentario en `env.php:6-7`). Todo el código PHP nuevo debe evitar: type hints escalares y de retorno en firmas de función (`function f(float $x): array` — NO), `list()` corto con claves, `str_contains`/`str_starts_with` (usar `strpos`), arrow functions `fn() =>`, `match`, argumentos con nombre, operador `?->`. El operador `??` SÍ es seguro (ya se usa en todo el código existente).
2. **No hay framework de tests** (sin PHPUnit, sin carpeta `tests/`). La verificación de este plan se hace con:
   - `mysql` CLI contra la base local (`sgipro_sgc_argos`) para migraciones y datos.
   - Scripts PHP CLI desechables (`php -r "..."` o un archivo temporal) que llaman directamente a las funciones de `helpers/cupo_marca_helpers.php` contra la BD local — no requieren sesión web.
   - QA manual en el navegador para los endpoints AJAX (dependen de `$_SESSION`), siguiendo el mismo patrón que ya usa este proyecto (sin suite automatizada).
3. **Rutas reales de la app** (confirmado en `content.php` y `shared/sidebar.php`): el módulo administrativo activo es `pages/clientes/view.php` + `ajax/clientes/clientes.php`. **NO tocar** `convenio/view.php` / `ajax/convenio/convenio.php` — no están enlazados en ningún menú ni ruta, es código muerto.
4. **No tocar el árbol `shared/`** — es una copia vieja sin usar (confirmado: `content.php` incluye desde `pages/`, no desde `shared/pages/`).
5. **Antes de cada push a `feature/nuevas-funcionalidades`**, incrementar `VERSION` según `CLAUDE.md` — se recuerda en la última tarea.

---

## File Structure

**Crear:**
- `migrations/bloque14_cupo_por_marca.sql` — columna `cli_modo_cupo`, tablas `cliente_cupo_marca` y `personal_cupo_marca`.
- `helpers/cupo_marca_helpers.php` — toda la lógica de lectura/escritura de cupo por marca, reutilizada por los 3 módulos.

**Modificar:**
- `ajax/clientes/clientes.php` — `crear`, `editar` (modo + montos por marca del convenio), `personal_editar` (cupo por marca al editar empleado desde admin), `personal_carga_masiva` (Añadir/Actualizar cupo con columnas por marca).
- `ajax/portal_empresa/portal_empresa.php` — `resumen` (desglose por marca), `cupo_convenio` (máximos por marca), `crear_empleado`, `editar_empleado`.
- `ajax/pos/pos.php` — `buscar`, `registrar`, `anular_venta`.
- `pages/clientes/view.php` — modal Cliente (selector de modo + inputs por marca), modal Empleado (inputs por marca), modal Carga Masiva (plantilla y lectura de columnas dinámicas).
- `pages/portal_empresa/view.php` — modales Nuevo/Editar Empleado (inputs por marca), tarjetas de resumen (desglose por marca).

---

## Task 1: Migración — columna de modo y tablas de cupo por marca

**Files:**
- Create: `migrations/bloque14_cupo_por_marca.sql`

- [ ] **Step 1: Escribir la migración**

```sql
-- CU-01: cupos globales y diferenciados por marca.
-- cli_modo_cupo define, por convenio, si el cupo (cli_valor_beneficio) es
-- global entre todas las marcas o si se reparte en montos independientes
-- por marca (cliente_cupo_marca / personal_cupo_marca). Los convenios
-- existentes quedan en 'global' por defecto: cero cambio de comportamiento.
ALTER TABLE cliente ADD COLUMN cli_modo_cupo VARCHAR(10) NOT NULL DEFAULT 'global' COMMENT 'global | marca — solo aplica si cli_tipo_beneficio = Cupo';

CREATE TABLE IF NOT EXISTS `cliente_cupo_marca` (
  `cli_id` INT NOT NULL,
  `mar_id` INT NOT NULL,
  `ccm_monto_max` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Máximo que el convenio permite asignar a un empleado en esa marca',
  PRIMARY KEY (`cli_id`, `mar_id`),
  CONSTRAINT `fk_ccm_cliente` FOREIGN KEY (`cli_id`) REFERENCES `cliente` (`cli_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccm_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tope de cupo por marca de un convenio en modo "marca" (CU-01)';

CREATE TABLE IF NOT EXISTS `personal_cupo_marca` (
  `per_id` INT NOT NULL,
  `mar_id` INT NOT NULL,
  `pcm_asignado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pcm_disponible` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`per_id`, `mar_id`),
  CONSTRAINT `fk_pcm_personal` FOREIGN KEY (`per_id`) REFERENCES `personal` (`per_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcm_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cupo de un empleado en una marca específica, cuando su convenio está en modo "marca" (CU-01). Ausencia de fila = cupo 0 en esa marca.';
```

- [ ] **Step 2: Aplicar la migración en la base local**

Run: `/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos < migrations/bloque14_cupo_por_marca.sql`
Expected: sin salida (éxito silencioso). Si hay un error de sintaxis o FK, corregirlo antes de continuar.

- [ ] **Step 3: Verificar la estructura resultante**

Run:
```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SHOW COLUMNS FROM cliente LIKE 'cli_modo_cupo'; SHOW TABLES LIKE 'cliente_cupo_marca'; SHOW TABLES LIKE 'personal_cupo_marca'; SELECT cli_id, cli_modo_cupo FROM cliente LIMIT 3;"
```
Expected: la columna `cli_modo_cupo` existe con `Default = global`; las dos tablas aparecen listadas; los 3 clientes de ejemplo muestran `cli_modo_cupo = global`.

- [ ] **Step 4: Commit**

```bash
git add migrations/bloque14_cupo_por_marca.sql
git commit -m "CU-01: migración — modo de cupo y tablas de cupo por marca"
```

---

## Task 2: Helpers compartidos de cupo por marca

**Files:**
- Create: `helpers/cupo_marca_helpers.php`

- [ ] **Step 1: Escribir el archivo de funciones**

```php
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
```

- [ ] **Step 2: Verificar con un script PHP CLI desechable (sin sesión, contra la BD local)**

Crear un archivo temporal `verify_cupo_helpers.php` en la raíz del proyecto (fuera de git, se borra al final):

```php
<?php
require_once 'config/database.php';
require_once 'helpers/cupo_marca_helpers.php';

// Usar el cliente demo (cli_id=13, 'Empresa Demo S.A.', ya tiene cli_tipo_beneficio='Cupo')
$cli_id = 13;

echo "=== cupoMarcasActivas ===\n";
$marcas = cupoMarcasActivas($mysqli);
echo count($marcas) . " marcas encontradas\n";
$mar_pizza = null;
foreach ($marcas as $m) { if ($m['mar_descripcion'] === 'Pizza Hut') $mar_pizza = $m['mar_id']; }
echo "mar_id de Pizza Hut: " . var_export($mar_pizza, true) . "\n";

echo "=== cupoObtenerModo (antes de guardar nada) ===\n";
$modo = cupoObtenerModo($mysqli, $cli_id);
var_export($modo); echo "\n";
assert($modo['modo'] === 'global');

echo "=== cupoGuardarMaximosPorMarca + cupoMaximosPorMarca ===\n";
cupoGuardarMaximosPorMarca($mysqli, $cli_id, array($mar_pizza => 100));
$max = cupoMaximosPorMarca($mysqli, $cli_id);
var_export($max); echo "\n";
assert($max[$mar_pizza] === 100.0);

echo "=== cupoUpsertEmpleadoMarca + cupoEmpleadoEnMarca ===\n";
// Empleado demo: usar el primer per_id que exista para cli_id=13, o crear uno de prueba
$per = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT per_id FROM personal WHERE cli_id = $cli_id LIMIT 1"));
if (!$per) {
    mysqli_query($mysqli, "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible) VALUES ('Test Helper', '9999999999', '1111222233334444', $cli_id, 'activo', 1, 1)");
    $per_id = mysqli_insert_id($mysqli);
} else {
    $per_id = (int)$per['per_id'];
}
cupoUpsertEmpleadoMarca($mysqli, $per_id, $mar_pizza, 50);
$emp = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_pizza);
var_export($emp); echo "\n";
assert($emp['asignado'] === 50.0 && $emp['disponible'] === 50.0);

echo "=== cupoDescontarEmpleadoMarca (venta de 20) ===\n";
cupoDescontarEmpleadoMarca($mysqli, $per_id, $mar_pizza, 20);
$emp = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_pizza);
var_export($emp); echo "\n";
assert($emp['disponible'] === 30.0);

echo "=== cupoDevolverEmpleadoMarca (anulación de esos 20) ===\n";
cupoDevolverEmpleadoMarca($mysqli, $per_id, $mar_pizza, 20);
$emp = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_pizza);
var_export($emp); echo "\n";
assert($emp['disponible'] === 50.0);

echo "=== cupoMarcaDeLocal ===\n";
$loc = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT loc_id FROM local WHERE mar_id = $mar_pizza LIMIT 1"));
$mar_de_local = cupoMarcaDeLocal($mysqli, $loc['loc_id']);
echo "mar_id resuelto: $mar_de_local (esperado: $mar_pizza)\n";
assert($mar_de_local === $mar_pizza);

echo "TODO OK\n";
```

Run: `/c/xampp/php/php.exe -d zend.assertions=1 -d assert.exception=1 verify_cupo_helpers.php`
Expected: imprime cada bloque sin que ningún `assert()` lance excepción, termina con `TODO OK`.

- [ ] **Step 3: Borrar el script de verificación**

Run: `rm verify_cupo_helpers.php`
Expected: el archivo no debe quedar en el repo (no se agrega a git, es solo de verificación local).

- [ ] **Step 4: Commit**

```bash
git add helpers/cupo_marca_helpers.php
git commit -m "CU-01: helpers compartidos de cupo por marca"
```

---

## Task 3: Backend — Cliente/Convenio: modo de cupo y montos por marca

**Files:**
- Modify: `ajax/clientes/clientes.php:1-2` (require del helper)
- Modify: `ajax/clientes/clientes.php:48-77` (case `crear`)
- Modify: `ajax/clientes/clientes.php:80-110` (case `editar`)
- Modify: `ajax/clientes/clientes.php:35-45` (case `get`, para devolver los montos por marca al editar)

- [ ] **Step 1: Incluir el helper**

En `ajax/clientes/clientes.php`, después de `require_once "../../config/database.php";` (línea 2):

```php
require_once "../../config/database.php";
require_once "../../helpers/cupo_marca_helpers.php";
```

- [ ] **Step 2: Extender `case 'get'` para devolver los montos por marca**

Reemplazar (líneas 35-45):

```php
    // ── GET — datos JSON para modal editar ───────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        echo $row
            ? json_encode(['success' => true, 'data' => $row])
            : json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado']);
        break;
```

por:

```php
    // ── GET — datos JSON para modal editar ───────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            echo json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado']);
            break;
        }
        $row['cupo_por_marca'] = cupoMaximosPorMarca($mysqli, $id);
        echo json_encode(['success' => true, 'data' => $row]);
        break;
```

- [ ] **Step 3: Extender `case 'crear'`**

Reemplazar el bloque completo (líneas 48-77):

```php
    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        header('Content-Type: application/json');
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'El nombre es requerido']); break; }

        $stmt = $mysqli->prepare(
            "INSERT INTO cliente
             (cli_descripcion, cli_numero_convenio, cli_ciudad, cli_contacto,
              cli_email, cli_email2, cli_telefono, cli_telefono2, cli_dia_corte,
              cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $conv  = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu   = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont  = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1   = trim($_POST['cli_email']    ?? '') ?: null;
        $em2   = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1  = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2  = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia   = trim($_POST['cli_dia_corte']?? '0');
        $tben  = $_POST['cli_tipo_beneficio'] ?? null;
        $vben  = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar  = $_POST['cli_tipo_cartera'] ?? null;
        $com   = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;

        $stmt->bind_param('ssssssssssdsd', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com);
        echo $stmt->execute()
            ? json_encode(['success' => true,  'mensaje' => 'Cliente creado exitosamente', 'id' => $mysqli->insert_id])
            : json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
        break;
```

por:

```php
    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        header('Content-Type: application/json');
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'El nombre es requerido']); break; }

        $modo_cupo = ($_POST['cli_modo_cupo'] ?? 'global') === 'marca' ? 'marca' : 'global';

        $stmt = $mysqli->prepare(
            "INSERT INTO cliente
             (cli_descripcion, cli_numero_convenio, cli_ciudad, cli_contacto,
              cli_email, cli_email2, cli_telefono, cli_telefono2, cli_dia_corte,
              cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision, cli_modo_cupo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $conv  = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu   = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont  = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1   = trim($_POST['cli_email']    ?? '') ?: null;
        $em2   = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1  = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2  = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia   = trim($_POST['cli_dia_corte']?? '0');
        $tben  = $_POST['cli_tipo_beneficio'] ?? null;
        $vben  = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar  = $_POST['cli_tipo_cartera'] ?? null;
        $com   = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;

        $stmt->bind_param('ssssssssssdsds', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $modo_cupo);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
            break;
        }

        $nuevo_id = $mysqli->insert_id;
        if ($tben === 'Cupo' && $modo_cupo === 'marca') {
            $montos = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            cupoGuardarMaximosPorMarca($mysqli, $nuevo_id, is_array($montos) ? $montos : array());
        }

        echo json_encode(['success' => true, 'mensaje' => 'Cliente creado exitosamente', 'id' => $nuevo_id]);
        break;
```

- [ ] **Step 4: Extender `case 'editar'`**

Reemplazar el bloque completo (líneas 80-110):

```php
    // ── EDITAR ────────────────────────────────────────────────────────────────
    case 'editar':
        header('Content-Type: application/json');
        $id   = (int)($_POST['cli_id'] ?? 0);
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (!$id || empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }

        $conv = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu  = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1  = trim($_POST['cli_email']    ?? '') ?: null;
        $em2  = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1 = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2 = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia  = trim($_POST['cli_dia_corte']?? '0');
        $tben = $_POST['cli_tipo_beneficio'] ?? null;
        $vben = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar = $_POST['cli_tipo_cartera'] ?? null;
        $com  = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;

        $stmt = $mysqli->prepare(
            "UPDATE cliente SET
              cli_descripcion=?, cli_numero_convenio=?, cli_ciudad=?, cli_contacto=?,
              cli_email=?, cli_email2=?, cli_telefono=?, cli_telefono2=?, cli_dia_corte=?,
              cli_tipo_beneficio=?, cli_valor_beneficio=?, cli_tipo_cartera=?, cli_comision=?
             WHERE cli_id=?"
        );
        $stmt->bind_param('ssssssssssdsdi', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $id);
        echo $stmt->execute()
            ? json_encode(['success' => true,  'mensaje' => 'Cliente actualizado'])
            : json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
        break;
```

por:

```php
    // ── EDITAR ────────────────────────────────────────────────────────────────
    case 'editar':
        header('Content-Type: application/json');
        $id   = (int)($_POST['cli_id'] ?? 0);
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (!$id || empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }

        $conv = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu  = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1  = trim($_POST['cli_email']    ?? '') ?: null;
        $em2  = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1 = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2 = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia  = trim($_POST['cli_dia_corte']?? '0');
        $tben = $_POST['cli_tipo_beneficio'] ?? null;
        $vben = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar = $_POST['cli_tipo_cartera'] ?? null;
        $com  = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;
        $modo_cupo = ($_POST['cli_modo_cupo'] ?? 'global') === 'marca' ? 'marca' : 'global';

        $stmt = $mysqli->prepare(
            "UPDATE cliente SET
              cli_descripcion=?, cli_numero_convenio=?, cli_ciudad=?, cli_contacto=?,
              cli_email=?, cli_email2=?, cli_telefono=?, cli_telefono2=?, cli_dia_corte=?,
              cli_tipo_beneficio=?, cli_valor_beneficio=?, cli_tipo_cartera=?, cli_comision=?, cli_modo_cupo=?
             WHERE cli_id=?"
        );
        $stmt->bind_param('ssssssssssdsdsi', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $modo_cupo, $id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
            break;
        }

        if ($tben === 'Cupo' && $modo_cupo === 'marca') {
            $montos = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            cupoGuardarMaximosPorMarca($mysqli, $id, is_array($montos) ? $montos : array());
        }

        echo json_encode(['success' => true, 'mensaje' => 'Cliente actualizado']);
        break;
```

- [ ] **Step 5: Verificar con mysql CLI + curl (requiere sesión — ver nota)**

Este endpoint exige `$_SESSION['id_user']` (login), así que la verificación completa es manual en el navegador (Task 4 la cubre end-to-end). Para verificar solo el SQL sin sesión, confirmar que la migración soporta el nuevo `bind_param` con 14 campos:

Run: `/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "DESCRIBE cliente;" | grep cli_modo_cupo`
Expected: una línea mostrando `cli_modo_cupo varchar(10) NO ... global`.

- [ ] **Step 6: Commit**

```bash
git add ajax/clientes/clientes.php
git commit -m "CU-01: backend Clientes — modo de cupo y montos por marca en crear/editar"
```

---

## Task 4: Frontend — Modal Cliente: selector de modo y montos por marca

**Files:**
- Modify: `pages/clientes/view.php:405-439` (sección "Configuración Comercial" del modal)
- Modify: `pages/clientes/view.php` (JS: guardado, apertura para editar, `actualizarPrefijo`)

- [ ] **Step 1: Agregar el selector de modo y el contenedor de montos por marca al HTML**

Ubicar el bloque `<div class="row">` que contiene `cli_tipo_beneficio` (líneas 407-439) e insertar, inmediatamente después de `</div>` que cierra ese `row` (línea 439), un nuevo bloque:

```html
                    <div class="row" id="row_modo_cupo" style="display:none;">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modo de cupo</label>
                                <select class="form-control" id="cli_modo_cupo" name="cli_modo_cupo">
                                    <option value="global">Global (compartido entre marcas)</option>
                                    <option value="marca">Por marca (independiente por marca)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="row_cupo_por_marca" style="display:none;">
                        <div class="col-12">
                            <label class="mb-1">Monto máximo por marca</label>
                            <div id="cupo_marca_inputs" class="row"></div>
                        </div>
                    </div>
```

- [ ] **Step 2: Cargar el catálogo de marcas al abrir el modal y renderizar los inputs**

Buscar la función `window.abrirNuevo` (o equivalente) y el manejador de `.btn-editar` en `pages/clientes/view.php` (la misma sección JS ya vista en la Task de spec: `abrirNuevo`, `$(document).on('click', '.btn-editar', ...)`). Agregar, cerca del inicio del `<script>` de este archivo (junto a las demás variables globales como `_cliId`), una caché de marcas y la función de render:

```javascript
var _marcasCatalogo = null; // cache: [{mar_id, mar_descripcion}, ...]

function cargarMarcasCatalogo(callback) {
    if (_marcasCatalogo) { callback(_marcasCatalogo); return; }
    $.getJSON('ajax/locales/locales.php', { action: 'list_marcas' }, function (res) {
        _marcasCatalogo = (res && res.success) ? res.data : [];
        callback(_marcasCatalogo);
    }).fail(function () {
        _marcasCatalogo = [];
        callback(_marcasCatalogo);
    });
}

function renderCupoPorMarcaInputs(montosExistentes) {
    montosExistentes = montosExistentes || {};
    cargarMarcasCatalogo(function (marcas) {
        var html = '';
        marcas.forEach(function (m) {
            var valor = montosExistentes[m.mar_id] || '';
            html += '<div class="col-md-4 mb-2">'
                + '<label class="small mb-1">' + htmlEsc(m.mar_descripcion) + '</label>'
                + '<div class="input-group input-group-sm">'
                + '<div class="input-group-prepend"><span class="input-group-text">$</span></div>'
                + '<input type="number" class="form-control cupo-marca-input" data-mar-id="' + m.mar_id + '" min="0" step="0.01" value="' + valor + '" placeholder="0.00">'
                + '</div></div>';
        });
        $('#cupo_marca_inputs').html(html);
    });
}

function leerCupoPorMarcaInputs() {
    var out = {};
    $('.cupo-marca-input').each(function () {
        var marId = $(this).data('mar-id');
        var val   = parseFloat($(this).val());
        if (val > 0) out[marId] = val;
    });
    return out;
}

function toggleModoCupoUI() {
    var esCupo = $('#cli_tipo_beneficio').val() === 'Cupo';
    var esMarca = $('#cli_modo_cupo').val() === 'marca';
    $('#row_modo_cupo').toggle(esCupo);
    $('#row_cupo_por_marca').toggle(esCupo && esMarca);
    $('#div_valor_beneficio, #label_valor, #cli_valor_beneficio').closest('.col-md-4').toggle(!(esCupo && esMarca));
}

$('#cli_tipo_beneficio, #cli_modo_cupo').on('change', toggleModoCupoUI);
```

(Nota: si el proyecto no tiene ya una función `htmlEsc`, reutilizar la existente en el mismo archivo — ya se usa en `renderTabla`/equivalentes de este módulo; no duplicarla.)

- [ ] **Step 3: Precargar el modo y montos al abrir "Editar"**

En el callback de éxito de `action: 'get'` (donde se rellenan los campos del modal para editar), agregar después de rellenar `cli_tipo_beneficio`/`cli_valor_beneficio`:

```javascript
$('#cli_modo_cupo').val(c.cli_modo_cupo || 'global');
toggleModoCupoUI();
if (c.cli_modo_cupo === 'marca') {
    renderCupoPorMarcaInputs(c.cupo_por_marca || {});
}
```

Y en la apertura de "Nuevo Cliente" (limpieza de campos), agregar:

```javascript
$('#cli_modo_cupo').val('global');
$('#cupo_marca_inputs').html('');
toggleModoCupoUI();
```

- [ ] **Step 4: Incluir el modo y los montos al guardar**

En el `payload`/objeto de datos que arma `$('#btn_guardar')` (o el submit del `formCliente`) antes del `$.post`/`$.ajax` a `action=crear`/`action=editar`, agregar:

```javascript
cli_modo_cupo: $('#cli_modo_cupo').val() || 'global',
cupo_por_marca: JSON.stringify(leerCupoPorMarcaInputs())
```

- [ ] **Step 5: Verificación manual en navegador**

1. Iniciar sesión como Super Admin en `http://localhost/SGC_ARGOS26` (o la ruta local configurada).
2. Ir a **Clientes** → editar "Empresa Demo S.A." (tiene `cli_tipo_beneficio = Cupo`, `cli_valor_beneficio = 500`).
3. Confirmar que aparece el select "Modo de cupo" en `Global`.
4. Cambiarlo a "Por marca" → deben aparecer inputs, uno por cada marca (Pizza Hut, Happy, Vaco y Vaca, Fridays, Mcdonalds, KFC, EL JOTA, Hornero).
5. Poner `50` en Pizza Hut y `30` en Fridays, guardar.
6. Verificar en BD: `/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM cliente_cupo_marca WHERE cli_id=13;"` → debe mostrar 2 filas con esos montos.
7. Reabrir el modal de edición → los inputs deben mostrar los mismos valores precargados.

- [ ] **Step 6: Commit**

```bash
git add pages/clientes/view.php
git commit -m "CU-01: frontend Clientes — selector de modo de cupo y montos por marca"
```

---

## Task 5: Backend — Portal Empresa: `cupo_convenio` extendido

**Files:**
- Modify: `ajax/portal_empresa/portal_empresa.php:1-5` (require del helper)
- Modify: `ajax/portal_empresa/portal_empresa.php:117-124` (case `cupo_convenio`)

- [ ] **Step 1: Incluir el helper**

Después de `require_once '../../config/database.php';` (línea 4):

```php
require_once '../../config/database.php';
require_once '../../helpers/cupo_marca_helpers.php';
```

- [ ] **Step 2: Reemplazar `case 'cupo_convenio'`**

Reemplazar (líneas 120-124):

```php
    case 'cupo_convenio':
        $q = "SELECT cli_valor_beneficio FROM cliente WHERE cli_id = $cli_id LIMIT 1";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $q));
        echo json_encode(['success' => true, 'cupo' => (float)($r['cli_valor_beneficio'] ?? 0)]);
        break;
```

por:

```php
    case 'cupo_convenio':
        $modo = cupoObtenerModo($mysqli, $cli_id);
        if ($modo['modo'] === 'marca') {
            $marcas = cupoMarcasActivas($mysqli);
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            $porMarca = array();
            foreach ($marcas as $m) {
                $porMarca[] = array(
                    'mar_id'          => $m['mar_id'],
                    'mar_descripcion' => $m['mar_descripcion'],
                    'monto_max'       => isset($maximos[$m['mar_id']]) ? $maximos[$m['mar_id']] : 0.0,
                );
            }
            echo json_encode(['success' => true, 'modo' => 'marca', 'por_marca' => $porMarca]);
        } else {
            echo json_encode(['success' => true, 'modo' => 'global', 'cupo' => $modo['valor_global']]);
        }
        break;
```

- [ ] **Step 3: Verificar con curl usando una sesión real**

No requiere navegador completo: iniciar sesión una vez en el navegador para obtener la cookie `PHPSESSID`, luego:

```bash
curl -s "http://localhost/SGC_ARGOS26/ajax/portal_empresa/portal_empresa.php?action=cupo_convenio" -H "Cookie: PHPSESSID=<pegar-aqui>"
```
Expected (convenio en modo global, sin cambios aún): `{"success":true,"modo":"global","cupo":500}` (o el valor real del convenio de la sesión).

- [ ] **Step 4: Commit**

```bash
git add ajax/portal_empresa/portal_empresa.php
git commit -m "CU-01: backend Portal Empresa — cupo_convenio devuelve modo y montos por marca"
```

---

## Task 6: Backend — Portal Empresa: crear empleado con cupo por marca

**Files:**
- Modify: `ajax/portal_empresa/portal_empresa.php:129-169` (case `crear_empleado`)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar (líneas 129-169):

```php
    // ----------------------------------------------------------
    // Crear nuevo empleado
    // ----------------------------------------------------------
    case 'crear_empleado':
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $cupo      = (float)($_POST['per_cupo'] ?? 0);

        if (!$nombre || !$documento || $cupo <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Verificar que la cédula no exista ya
        $chk = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT per_id FROM personal WHERE per_documento = '$documento' LIMIT 1"));
        if ($chk) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe un empleado con esa cédula']);
            break;
        }

        // FIX 3: Validate cupo does not exceed empresa max
        $empresa = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT cli_valor_beneficio FROM cliente WHERE cli_id = $cli_id LIMIT 1"));
        $cupo_max = (float)($empresa['cli_valor_beneficio'] ?? 0);
        if ($cupo_max > 0 && $cupo > $cupo_max) {
            echo json_encode(['success' => false, 'mensaje' => 'El cupo del empleado ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo asignado a la empresa ($' . number_format($cupo_max, 2) . ')']);
            break;
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        // Generar número de tarjeta único de 16 dígitos
        $num_tarjeta = str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0');
        $q = "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, per_correo, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible)
              VALUES ('$nombre', '$documento', '$num_tarjeta', $correo_sql, $cli_id, 'activo', $cupo, $cupo)";

        if (mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => true, 'per_id' => mysqli_insert_id($mysqli)]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
        }
        break;
```

por:

```php
    // ----------------------------------------------------------
    // Crear nuevo empleado
    // ----------------------------------------------------------
    case 'crear_empleado':
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$nombre || !$documento) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $cupoPorMarca = array();
        $cupo = 0;
        if ($modo['modo'] === 'marca') {
            $cupoPorMarca = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            if (!is_array($cupoPorMarca)) { $cupoPorMarca = array(); }
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            $algunaMarca = false;
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto <= 0) { continue; }
                $algunaMarca = true;
                $tope = isset($maximos[(int)$mar_id]) ? $maximos[(int)$mar_id] : 0;
                if ($tope > 0 && $monto > $tope) {
                    echo json_encode(['success' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')']);
                    exit;
                }
            }
            if (!$algunaMarca) {
                echo json_encode(['success' => false, 'mensaje' => 'Asigne un cupo en al menos una marca']);
                break;
            }
        } else {
            $cupo = (float)($_POST['per_cupo'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            $cupo_max = $modo['valor_global'];
            if ($cupo_max > 0 && $cupo > $cupo_max) {
                echo json_encode(['success' => false, 'mensaje' => 'El cupo del empleado ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo asignado a la empresa ($' . number_format($cupo_max, 2) . ')']);
                break;
            }
        }

        // Verificar que la cédula no exista ya
        $chk = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT per_id FROM personal WHERE per_documento = '$documento' LIMIT 1"));
        if ($chk) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe un empleado con esa cédula']);
            break;
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        // Generar número de tarjeta único de 16 dígitos
        $num_tarjeta = str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0');
        $q = "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, per_correo, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible)
              VALUES ('$nombre', '$documento', '$num_tarjeta', $correo_sql, $cli_id, 'activo', $cupo, $cupo)";

        if (!mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
            break;
        }

        $nuevo_per_id = mysqli_insert_id($mysqli);
        if ($modo['modo'] === 'marca') {
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto > 0) {
                    cupoUpsertEmpleadoMarca($mysqli, $nuevo_per_id, $mar_id, $monto);
                }
            }
        }

        echo json_encode(['success' => true, 'per_id' => $nuevo_per_id]);
        break;
```

- [ ] **Step 2: Verificación manual en navegador**

1. Con "Empresa Demo S.A." ya en modo `marca` (Task 4), entrar a **Portal Empresa** logueado como usuario de esa empresa.
2. Click "Nuevo Empleado" → debe mostrar un input por marca en vez del campo único "Cupo asignado" (esto último se completa junto con Task 9; si Task 9 aún no está hecha, verificar por ahora solo que el backend acepta `cupo_por_marca` vía `curl` con la cookie de sesión):

```bash
curl -s -X POST "http://localhost/SGC_ARGOS26/ajax/portal_empresa/portal_empresa.php" \
  -H "Cookie: PHPSESSID=<pegar-aqui>" \
  --data-urlencode "action=crear_empleado" \
  --data-urlencode "per_nombre=Prueba Marca" \
  --data-urlencode "per_documento=0999999911" \
  --data-urlencode "cupo_por_marca={\"<mar_id_pizza>\":40}"
```
Expected: `{"success":true,"per_id":<n>}`. Luego: `/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM personal_cupo_marca WHERE per_id=<n>;"` → una fila con `pcm_asignado=40.00, pcm_disponible=40.00`.

- [ ] **Step 3: Commit**

```bash
git add ajax/portal_empresa/portal_empresa.php
git commit -m "CU-01: backend Portal Empresa — crear_empleado con cupo por marca"
```

---

## Task 7: Backend — Portal Empresa: editar empleado con cupo por marca

**Files:**
- Modify: `ajax/portal_empresa/portal_empresa.php:175-269` (case `editar_empleado`)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar (líneas 175-269, desde `case 'editar_empleado':` hasta el `break;` que le sigue):

```php
    // ----------------------------------------------------------
    // Editar empleado
    // ----------------------------------------------------------
    case 'editar_empleado':
        $per_id    = (int)($_POST['per_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$per_id || !$nombre || !$documento) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validate belongs to this empresa
        $emp_check = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id, per_nombre, per_documento, per_correo, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE per_id = $per_id AND cli_id = $cli_id LIMIT 1"));
        if (!$emp_check) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $chkCed = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id FROM personal WHERE per_documento = '$documento' AND per_id != $per_id LIMIT 1"));
        if ($chkCed) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe otro empleado registrado con esa cédula']);
            break;
        }

        $cupo = 0;
        $cupoPorMarca = array();
        if ($modo['modo'] === 'marca') {
            $cupoPorMarca = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            if (!is_array($cupoPorMarca)) { $cupoPorMarca = array(); }
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto <= 0) { continue; }
                $tope = isset($maximos[(int)$mar_id]) ? $maximos[(int)$mar_id] : 0;
                if ($tope > 0 && $monto > $tope) {
                    echo json_encode(['success' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')']);
                    exit;
                }
            }
        } else {
            $cupo = (float)($_POST['per_cupo'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            $cupo_max = $modo['valor_global'];
            if ($cupo_max > 0 && $cupo > $cupo_max) {
                echo json_encode(['success' => false,
                    'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($cupo_max, 2) . ')']);
                break;
            }
        }

        // Detect and record changes for traceability
        $id_user_sesion = (int)$_SESSION['id_user'];
        $cambios = [];

        if ($emp_check['per_nombre'] !== $nombre) {
            $cambios[] = ['campo' => 'per_nombre', 'label' => 'Nombre', 'anterior' => $emp_check['per_nombre'], 'nuevo' => $nombre];
        }
        if ($emp_check['per_documento'] !== $documento) {
            $cambios[] = ['campo' => 'per_documento', 'label' => 'Cédula', 'anterior' => $emp_check['per_documento'], 'nuevo' => $documento];
        }
        if ($emp_check['per_correo'] !== $correo) {
            $cambios[] = ['campo' => 'per_correo', 'label' => 'Correo', 'anterior' => $emp_check['per_correo'], 'nuevo' => $correo];
        }

        if ($modo['modo'] === 'global') {
            $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
            if (abs($cupo_anterior - $cupo) > 0.001) {
                $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
                $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo,
                    'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
            }
            $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
            if (abs($cupo_anterior - $cupo) > 0.001) {
                $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
                $cupo_disponible_nuevo = max(0, $cupo - $consumido);
            }
        } else {
            $cupo_disponible_nuevo = 0; // no se usa en modo marca
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        $q_update = "UPDATE personal SET
                        per_nombre = '$nombre',
                        per_documento = '$documento',
                        per_correo = $correo_sql"
                  . ($modo['modo'] === 'global' ? ", per_cupo_asignado = $cupo, per_cupo_disponible = $cupo_disponible_nuevo" : '')
                  . " WHERE per_id = $per_id AND cli_id = $cli_id";

        if (!mysqli_query($mysqli, $q_update)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . mysqli_error($mysqli)]);
            break;
        }

        if ($modo['modo'] === 'marca') {
            $marcasCatalogo = cupoMarcasActivas($mysqli);
            $nombresPorId = array();
            foreach ($marcasCatalogo as $m) { $nombresPorId[$m['mar_id']] = $m['mar_descripcion']; }
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $mar_id = (int)$mar_id;
                $monto  = (float)$monto;
                if ($monto <= 0) { continue; }
                $antes = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id);
                if (abs($antes['asignado'] - $monto) > 0.001) {
                    $labelMarca = isset($nombresPorId[$mar_id]) ? $nombresPorId[$mar_id] : ('marca #' . $mar_id);
                    $cambios[] = [
                        'campo' => 'per_cupo_marca_' . $mar_id,
                        'label' => 'Cupo en ' . $labelMarca,
                        'anterior' => '$' . number_format($antes['asignado'], 2),
                        'nuevo'    => '$' . number_format($monto, 2),
                    ];
                }
                cupoUpsertEmpleadoMarca($mysqli, $per_id, $mar_id, $monto);
            }
        }

        // Insert traceability records
        foreach ($cambios as $c) {
            $campo    = mysqli_real_escape_string($mysqli, $c['campo']);
            $label    = mysqli_real_escape_string($mysqli, $c['label']);
            $anterior = mysqli_real_escape_string($mysqli, $c['anterior'] ?? '');
            $nuevo    = mysqli_real_escape_string($mysqli, $c['nuevo'] ?? '');
            mysqli_query($mysqli, "INSERT INTO personal_trazabilidad
                (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                VALUES ($per_id, $id_user_sesion, '$campo', '$label', '$anterior', '$nuevo')");
        }

        echo json_encode(['success' => true, 'cambios' => count($cambios)]);
        break;
```

- [ ] **Step 2: Verificación manual**

Editar el empleado de prueba creado en Task 6 (subir su cupo de Pizza Hut de 40 a 60), guardar, y verificar:

```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM personal_cupo_marca WHERE per_id=<n>; SELECT tra_campo_label, tra_valor_anterior, tra_valor_nuevo FROM personal_trazabilidad WHERE per_id=<n> ORDER BY tra_id DESC LIMIT 1;"
```
Expected: `pcm_asignado=60.00`; el último registro de trazabilidad muestra `Cupo en Pizza Hut`, `$40.00` → `$60.00`.

- [ ] **Step 3: Commit**

```bash
git add ajax/portal_empresa/portal_empresa.php
git commit -m "CU-01: backend Portal Empresa — editar_empleado con cupo por marca"
```

---

## Task 8: Backend — Portal Empresa: resumen con desglose por marca

**Files:**
- Modify: `ajax/portal_empresa/portal_empresa.php:25-36` (case `resumen`)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar (líneas 25-36):

```php
    // ----------------------------------------------------------
    // Resumen general de la empresa
    // ----------------------------------------------------------
    case 'resumen':
        $q = "SELECT
                COUNT(*) AS total_empleados,
                SUM(per_cupo_asignado)  AS total_asignado,
                SUM(per_cupo_disponible) AS total_disponible,
                SUM(per_cupo_asignado - per_cupo_disponible) AS total_consumido,
                SUM(CASE WHEN per_estado = 'activo' THEN 1 ELSE 0 END) AS activos
              FROM personal
              WHERE cli_id = $cli_id";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $q));
        echo json_encode(['success' => true, 'data' => $r]);
        break;
```

por:

```php
    // ----------------------------------------------------------
    // Resumen general de la empresa
    // ----------------------------------------------------------
    case 'resumen':
        $modo = cupoObtenerModo($mysqli, $cli_id);

        $qBase = "SELECT COUNT(*) AS total_empleados,
                         SUM(CASE WHEN per_estado = 'activo' THEN 1 ELSE 0 END) AS activos
                  FROM personal WHERE cli_id = $cli_id";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $qBase));

        if ($modo['modo'] === 'marca') {
            $qMarca = "SELECT pcm.mar_id, m.mar_descripcion,
                              SUM(pcm.pcm_asignado) AS asignado,
                              SUM(pcm.pcm_disponible) AS disponible
                       FROM personal_cupo_marca pcm
                       JOIN personal p ON pcm.per_id = p.per_id
                       JOIN marca m ON pcm.mar_id = m.mar_id
                       WHERE p.cli_id = $cli_id
                       GROUP BY pcm.mar_id, m.mar_descripcion
                       ORDER BY m.mar_descripcion ASC";
            $res = mysqli_query($mysqli, $qMarca);
            $porMarca = [];
            $totalAsignado = 0.0;
            $totalDisponible = 0.0;
            while ($row = mysqli_fetch_assoc($res)) {
                $asignado   = (float)$row['asignado'];
                $disponible = (float)$row['disponible'];
                $porMarca[] = [
                    'mar_id' => (int)$row['mar_id'],
                    'mar_descripcion' => $row['mar_descripcion'],
                    'asignado' => $asignado,
                    'disponible' => $disponible,
                    'consumido' => $asignado - $disponible,
                ];
                $totalAsignado += $asignado;
                $totalDisponible += $disponible;
            }
            $r['total_asignado']   = $totalAsignado;
            $r['total_disponible'] = $totalDisponible;
            $r['total_consumido']  = $totalAsignado - $totalDisponible;
            $r['modo'] = 'marca';
            $r['por_marca'] = $porMarca;
        } else {
            $qGlobal = "SELECT SUM(per_cupo_asignado) AS total_asignado,
                               SUM(per_cupo_disponible) AS total_disponible,
                               SUM(per_cupo_asignado - per_cupo_disponible) AS total_consumido
                        FROM personal WHERE cli_id = $cli_id";
            $rGlobal = mysqli_fetch_assoc(mysqli_query($mysqli, $qGlobal));
            $r['total_asignado']   = (float)$rGlobal['total_asignado'];
            $r['total_disponible'] = (float)$rGlobal['total_disponible'];
            $r['total_consumido']  = (float)$rGlobal['total_consumido'];
            $r['modo'] = 'global';
        }

        echo json_encode(['success' => true, 'data' => $r]);
        break;
```

- [ ] **Step 2: Verificación**

```bash
curl -s "http://localhost/SGC_ARGOS26/ajax/portal_empresa/portal_empresa.php?action=resumen" -H "Cookie: PHPSESSID=<pegar-aqui>"
```
Expected (empresa en modo `marca` con los datos de prueba): `"modo":"marca"`, `"por_marca"` con la fila de Pizza Hut mostrando `asignado: 60`.

- [ ] **Step 3: Commit**

```bash
git add ajax/portal_empresa/portal_empresa.php
git commit -m "CU-01: backend Portal Empresa — resumen con desglose por marca"
```

---

## Task 9: Frontend — Portal Empresa: modales de empleado y resumen

**Files:**
- Modify: `pages/portal_empresa/view.php:196-215` (form Nuevo Empleado)
- Modify: `pages/portal_empresa/view.php:321-337` (form Editar Empleado)
- Modify: `pages/portal_empresa/view.php:24-57` (tarjetas de resumen)
- Modify: `pages/portal_empresa/view.php` (JS: apertura de modales, guardado, carga de resumen)

- [ ] **Step 1: HTML — reemplazar el campo único de cupo en "Nuevo Empleado"**

Reemplazar (líneas 208-215):

```html
                <div class="form-group">
                    <label>Cupo asignado ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" class="form-control" id="new_per_cupo" min="0.01" step="0.01" placeholder="0.00">
                    </div>
                    <small class="text-muted" id="new_cupo_max_hint"></small>
                </div>
```

por:

```html
                <div class="form-group" id="grupo_new_cupo_global">
                    <label>Cupo asignado ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" class="form-control" id="new_per_cupo" min="0.01" step="0.01" placeholder="0.00">
                    </div>
                    <small class="text-muted" id="new_cupo_max_hint"></small>
                </div>
                <div class="form-group" id="grupo_new_cupo_marca" style="display:none;">
                    <label>Cupo asignado por marca ($)</label>
                    <div id="new_cupo_marca_inputs" class="row"></div>
                </div>
```

- [ ] **Step 2: HTML — mismo reemplazo en "Editar Empleado"**

Reemplazar (líneas 328-336):

```html
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cupo asignado ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" class="form-control" id="edit_per_cupo" min="0.01" step="0.01">
                            </div>
                            <small class="text-muted" id="edit_cupo_max_hint"></small>
                        </div>
                    </div>
```

por:

```html
                    <div class="col-md-6" id="grupo_edit_cupo_global">
                        <div class="form-group">
                            <label>Cupo asignado ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" class="form-control" id="edit_per_cupo" min="0.01" step="0.01">
                            </div>
                            <small class="text-muted" id="edit_cupo_max_hint"></small>
                        </div>
                    </div>
                    <div class="col-md-12" id="grupo_edit_cupo_marca" style="display:none;">
                        <label class="mb-1">Cupo asignado por marca ($)</label>
                        <div id="edit_cupo_marca_inputs" class="row"></div>
                    </div>
```

- [ ] **Step 3: HTML — desglose por marca en el resumen**

Después del `</div>` que cierra `<div class="row" id="row_resumen">` (línea 57), agregar:

```html
        <div class="row mt-2" id="row_resumen_marca" style="display:none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-2">
                        <p class="text-muted mb-2"><small>Desglose por marca</small></p>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Marca</th><th class="text-right">Asignado</th><th class="text-right">Consumido</th><th class="text-right">Disponible</th></tr></thead>
                                <tbody id="tbody_resumen_marca"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
```

- [ ] **Step 4: JS — cargar modo y renderizar inputs**

Agregar, junto a las demás variables/funciones globales del `<script>` de este archivo:

```javascript
var AJAX_URL = 'ajax/portal_empresa/portal_empresa.php'; // reutilizar si ya existe con ese nombre en el archivo
var _modoCupoEmpresa = 'global';

function cargarModoCupoYActualizarUI(callback) {
    $.getJSON(AJAX_URL, { action: 'cupo_convenio' }, function (r) {
        if (!r.success) { callback(); return; }
        _modoCupoEmpresa = r.modo;
        if (r.modo === 'marca') {
            $('#grupo_new_cupo_global, #grupo_edit_cupo_global').hide();
            $('#grupo_new_cupo_marca, #grupo_edit_cupo_marca').show();
            renderCupoMarcaInputs('#new_cupo_marca_inputs', r.por_marca, {});
        } else {
            $('#grupo_new_cupo_global, #grupo_edit_cupo_global').show();
            $('#grupo_new_cupo_marca, #grupo_edit_cupo_marca').hide();
            $('#new_per_cupo').data('cupo-max', r.cupo).attr('max', r.cupo);
        }
        callback(r);
    });
}

function renderCupoMarcaInputs(containerSelector, porMarca, valoresActuales) {
    valoresActuales = valoresActuales || {};
    var html = '';
    (porMarca || []).forEach(function (m) {
        var valor = valoresActuales[m.mar_id] || '';
        html += '<div class="col-md-6 mb-2">'
            + '<label class="small mb-1">' + m.mar_descripcion + ' <span class="text-muted">(máx. $' + parseFloat(m.monto_max).toFixed(2) + ')</span></label>'
            + '<div class="input-group input-group-sm">'
            + '<div class="input-group-prepend"><span class="input-group-text">$</span></div>'
            + '<input type="number" class="form-control cupo-marca-input" data-mar-id="' + m.mar_id + '" data-max="' + m.monto_max + '" min="0" step="0.01" value="' + valor + '" placeholder="0.00">'
            + '</div></div>';
    });
    $(containerSelector).html(html);
}

function leerCupoMarcaInputs(containerSelector) {
    var out = {};
    $(containerSelector + ' .cupo-marca-input').each(function () {
        var marId = $(this).data('mar-id');
        var val   = parseFloat($(this).val());
        if (val > 0) out[marId] = val;
    });
    return out;
}
```

- [ ] **Step 5: JS — enganchar en la apertura de modales**

En el manejador de click de `#btn_nuevo_empleado` (el que hoy hace `$.getJSON(AJAX_URL + '?action=cupo_convenio', ...)` alrededor de la línea 503), reemplazar ese bloque por una llamada a `cargarModoCupoYActualizarUI` antes de mostrar el modal:

```javascript
$('#new_per_cupo').val('');
cargarModoCupoYActualizarUI(function () {
    $('#modal_nuevo_emp').modal('show');
});
```

En `editarEmpleado`-equivalente (donde hoy se hace `$.getJSON(AJAX_URL + '?action=cupo_convenio', ...)` alrededor de la línea 586 para precargar el máximo al editar), agregar el precargado de valores existentes por marca. El endpoint `detalle_empleado` no trae hoy el desglose por marca del empleado — para no tener que tocarlo, leer directamente los valores desde la fila ya cargada en la tabla de nómina si están disponibles, o pedirlos con una llamada específica reutilizando `personal_cupo_marca` a través de un nuevo parámetro `per_id` en `cupo_convenio`:

Modificar el `case 'cupo_convenio':` de Task 5 para aceptar opcionalmente `per_id` y devolver también los valores actuales del empleado:

```php
    case 'cupo_convenio':
        $modo = cupoObtenerModo($mysqli, $cli_id);
        if ($modo['modo'] === 'marca') {
            $marcas = cupoMarcasActivas($mysqli);
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            $per_id_consulta = (int)($_GET['per_id'] ?? 0);
            $actuales = $per_id_consulta ? cupoEmpleadoPorMarca($mysqli, $per_id_consulta) : array();
            $porMarca = array();
            foreach ($marcas as $m) {
                $porMarca[] = array(
                    'mar_id'          => $m['mar_id'],
                    'mar_descripcion' => $m['mar_descripcion'],
                    'monto_max'       => isset($maximos[$m['mar_id']]) ? $maximos[$m['mar_id']] : 0.0,
                    'monto_actual'    => isset($actuales[$m['mar_id']]) ? $actuales[$m['mar_id']]['asignado'] : 0.0,
                );
            }
            echo json_encode(['success' => true, 'modo' => 'marca', 'por_marca' => $porMarca]);
        } else {
            echo json_encode(['success' => true, 'modo' => 'global', 'cupo' => $modo['valor_global']]);
        }
        break;
```

Y ajustar `cargarModoCupoYActualizarUI` (Step 4) para aceptar un `per_id` opcional:

```javascript
function cargarModoCupoYActualizarUI(perId, callback) {
    if (typeof perId === 'function') { callback = perId; perId = 0; }
    $.getJSON(AJAX_URL, { action: 'cupo_convenio', per_id: perId || 0 }, function (r) {
        if (!r.success) { callback(); return; }
        _modoCupoEmpresa = r.modo;
        if (r.modo === 'marca') {
            $('#grupo_new_cupo_global, #grupo_edit_cupo_global').hide();
            $('#grupo_new_cupo_marca, #grupo_edit_cupo_marca').show();
            var valoresActuales = {};
            (r.por_marca || []).forEach(function (m) { if (m.monto_actual > 0) valoresActuales[m.mar_id] = m.monto_actual; });
            var target = perId ? '#edit_cupo_marca_inputs' : '#new_cupo_marca_inputs';
            renderCupoMarcaInputs(target, r.por_marca, valoresActuales);
        } else {
            $('#grupo_new_cupo_global, #grupo_edit_cupo_global').show();
            $('#grupo_new_cupo_marca, #grupo_edit_cupo_marca').hide();
            $('#new_per_cupo, #edit_per_cupo').data('cupo-max', r.cupo).attr('max', r.cupo);
        }
        callback(r);
    });
}
```

(Esto reemplaza la llamada de Step 4; usar esta versión final.) En `editarEmpleado`, llamar `cargarModoCupoYActualizarUI(p.per_id, function () { $('#modal_editar_emp').modal('show'); });` en vez de mostrar el modal directamente.

- [ ] **Step 6: JS — incluir el desglose al guardar**

En el `data` del `$.post` de `#btn_guardar_emp` (crear) y `#btn_guardar_edicion` (editar), agregar:

```javascript
cupo_por_marca: JSON.stringify(leerCupoMarcaInputs('#new_cupo_marca_inputs'))
```
y, respectivamente:
```javascript
cupo_por_marca: JSON.stringify(leerCupoMarcaInputs('#edit_cupo_marca_inputs'))
```

- [ ] **Step 7: JS — resumen con desglose**

En la función que carga `action: 'resumen'` y rellena `#res_asignado`/`#res_consumido`/`#res_disponible`, agregar al final del callback de éxito:

```javascript
if (r.data.modo === 'marca' && r.data.por_marca && r.data.por_marca.length) {
    var filas = '';
    r.data.por_marca.forEach(function (m) {
        filas += '<tr><td>' + m.mar_descripcion + '</td>'
            + '<td class="text-right">$' + parseFloat(m.asignado).toFixed(2) + '</td>'
            + '<td class="text-right text-danger">$' + parseFloat(m.consumido).toFixed(2) + '</td>'
            + '<td class="text-right text-success">$' + parseFloat(m.disponible).toFixed(2) + '</td></tr>';
    });
    $('#tbody_resumen_marca').html(filas);
    $('#row_resumen_marca').show();
} else {
    $('#row_resumen_marca').hide();
}
```

- [ ] **Step 8: Verificación manual end-to-end (escenarios del cliente)**

1. Login como usuario de "Empresa Demo S.A." (modo `marca`, Pizza Hut máx. $100, Fridays sin definir aún).
2. Tarjetas de resumen: debe aparecer la tabla "Desglose por marca" con Pizza Hut mostrando lo asignado/consumido/disponible real.
3. "Nuevo Empleado" → debe verse un input por marca (no un campo único). Crear "Diego Escenario B" con $50 en Pizza Hut y $30 en Fridays (si Fridays no tiene máximo definido en el convenio, debe rechazar con el mensaje de tope — volver a Task 4 y asignarle máximo a Fridays primero, ej. $50, y reintentar).
4. Editar ese mismo empleado, subir Pizza Hut a $60 → guardar → reabrir el modal de edición → debe mostrar $60 precargado.
5. Confirmar en BD:
```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM personal_cupo_marca pcm JOIN personal p ON pcm.per_id=p.per_id WHERE p.per_documento='<cedula-usada>';"
```

- [ ] **Step 9: Commit**

```bash
git add pages/portal_empresa/view.php ajax/portal_empresa/portal_empresa.php
git commit -m "CU-01: frontend Portal Empresa — modales de empleado y resumen con cupo por marca"
```

---

## Task 10: Backend — Clientes (admin): editar cupo por marca de un empleado

**Files:**
- Modify: `ajax/clientes/clientes.php:1-2` (ya incluido el helper en Task 3, sin cambios aquí)
- Modify: `ajax/clientes/clientes.php:161-242` (case `personal_editar`)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar (líneas 161-242, desde `case 'personal_editar':` hasta su `break;`):

```php
    // ── CL-E: EDITAR EMPLEADO (desde la ficha del cliente, Super Admin) ────────
    case 'personal_editar':
        header('Content-Type: application/json');
        $per_id    = (int)($_POST['per_id']    ?? 0);
        $cli_id    = (int)($_POST['cli_id']    ?? 0);
        $nombre    = trim($_POST['per_nombre']    ?? '');
        $documento = trim($_POST['per_documento'] ?? '');
        $correo    = trim($_POST['per_correo']    ?? '');
        $cupo      = (float)($_POST['per_cupo_asignado'] ?? 0);

        if (!$per_id || !$cli_id || !$nombre || !$documento || $cupo <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT per_id, per_nombre, per_documento, per_correo, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE per_id = ? AND cli_id = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $per_id, $cli_id);
        $stmt->execute();
        $emp_check = $stmt->get_result()->fetch_assoc();
        if (!$emp_check) { echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']); break; }

        // CL-G: la cédula no puede pertenecer a otro empleado (de esta empresa
        // u otra) — mismo control que ya existía al crear un empleado nuevo
        // desde Portal Empresa, que faltaba aquí al editar.
        $chkCed = $mysqli->prepare("SELECT per_id FROM personal WHERE per_documento = ? AND per_id != ? LIMIT 1");
        $chkCed->bind_param('si', $documento, $per_id);
        $chkCed->execute();
        if ($chkCed->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe otro empleado registrado con esa cédula']);
            break;
        }

        $empresa = $mysqli->prepare("SELECT cli_valor_beneficio FROM cliente WHERE cli_id = ?");
        $empresa->bind_param('i', $cli_id);
        $empresa->execute();
        $cupo_max = (float)($empresa->get_result()->fetch_assoc()['cli_valor_beneficio'] ?? 0);
        if ($cupo_max > 0 && $cupo > $cupo_max) {
            echo json_encode(['success' => false, 'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($cupo_max, 2) . ')']);
            break;
        }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $cambios = [];
        if ($emp_check['per_nombre'] !== $nombre)       $cambios[] = ['campo' => 'per_nombre',    'label' => 'Nombre', 'anterior' => $emp_check['per_nombre'],    'nuevo' => $nombre];
        if ($emp_check['per_documento'] !== $documento) $cambios[] = ['campo' => 'per_documento', 'label' => 'Cédula', 'anterior' => $emp_check['per_documento'], 'nuevo' => $documento];
        if ($emp_check['per_correo'] !== $correo)       $cambios[] = ['campo' => 'per_correo',    'label' => 'Correo', 'anterior' => $emp_check['per_correo'],    'nuevo' => $correo];
        $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
        if (abs($cupo_anterior - $cupo) > 0.001) {
            $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
            $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo, 'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
        }

        // Ajustar cupo disponible proporcionalmente si cambió el cupo asignado
        $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
        if (abs($cupo_anterior - $cupo) > 0.001) {
            $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
            $cupo_disponible_nuevo = max(0, $cupo - $consumido);
        }

        $upd = $mysqli->prepare(
            "UPDATE personal SET per_nombre=?, per_documento=?, per_correo=?, per_cupo_asignado=?, per_cupo_disponible=?
             WHERE per_id=? AND cli_id=?"
        );
        $correoParam = $correo !== '' ? $correo : null;
        $upd->bind_param('sssddii', $nombre, $documento, $correoParam, $cupo, $cupo_disponible_nuevo, $per_id, $cli_id);
        if (!$upd->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . $mysqli->error]); break; }

        foreach ($cambios as $c) {
            $tra = $mysqli->prepare(
                "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $anterior = $c['anterior'] ?? '';
            $nuevo    = $c['nuevo'] ?? '';
            $tra->bind_param('iissss', $per_id, $id_user_sesion, $c['campo'], $c['label'], $anterior, $nuevo);
            $tra->execute();
        }

        echo json_encode(['success' => true, 'cambios' => count($cambios)]);
        break;
```

por (misma lógica que Task 7 pero adaptada a este endpoint, que no valida cédula igual y usa bind_param en vez de escape manual):

```php
    // ── CL-E: EDITAR EMPLEADO (desde la ficha del cliente, Super Admin) ────────
    case 'personal_editar':
        header('Content-Type: application/json');
        $per_id    = (int)($_POST['per_id']    ?? 0);
        $cli_id    = (int)($_POST['cli_id']    ?? 0);
        $nombre    = trim($_POST['per_nombre']    ?? '');
        $documento = trim($_POST['per_documento'] ?? '');
        $correo    = trim($_POST['per_correo']    ?? '');
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$per_id || !$cli_id || !$nombre || !$documento) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT per_id, per_nombre, per_documento, per_correo, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE per_id = ? AND cli_id = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $per_id, $cli_id);
        $stmt->execute();
        $emp_check = $stmt->get_result()->fetch_assoc();
        if (!$emp_check) { echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']); break; }

        $chkCed = $mysqli->prepare("SELECT per_id FROM personal WHERE per_documento = ? AND per_id != ? LIMIT 1");
        $chkCed->bind_param('si', $documento, $per_id);
        $chkCed->execute();
        if ($chkCed->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe otro empleado registrado con esa cédula']);
            break;
        }

        $cupo = 0;
        $cupoPorMarca = array();
        if ($modo['modo'] === 'marca') {
            $cupoPorMarca = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            if (!is_array($cupoPorMarca)) { $cupoPorMarca = array(); }
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto <= 0) { continue; }
                $tope = isset($maximos[(int)$mar_id]) ? $maximos[(int)$mar_id] : 0;
                if ($tope > 0 && $monto > $tope) {
                    echo json_encode(['success' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')']);
                    exit;
                }
            }
        } else {
            $cupo = (float)($_POST['per_cupo_asignado'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            if ($modo['valor_global'] > 0 && $cupo > $modo['valor_global']) {
                echo json_encode(['success' => false, 'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($modo['valor_global'], 2) . ')']);
                break;
            }
        }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $cambios = [];
        if ($emp_check['per_nombre'] !== $nombre)       $cambios[] = ['campo' => 'per_nombre',    'label' => 'Nombre', 'anterior' => $emp_check['per_nombre'],    'nuevo' => $nombre];
        if ($emp_check['per_documento'] !== $documento) $cambios[] = ['campo' => 'per_documento', 'label' => 'Cédula', 'anterior' => $emp_check['per_documento'], 'nuevo' => $documento];
        if ($emp_check['per_correo'] !== $correo)       $cambios[] = ['campo' => 'per_correo',    'label' => 'Correo', 'anterior' => $emp_check['per_correo'],    'nuevo' => $correo];

        $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
        if ($modo['modo'] === 'global') {
            $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
            if (abs($cupo_anterior - $cupo) > 0.001) {
                $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
                $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo, 'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
                $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
                $cupo_disponible_nuevo = max(0, $cupo - $consumido);
            }
        }

        $upd = $mysqli->prepare(
            "UPDATE personal SET per_nombre=?, per_documento=?, per_correo=?"
            . ($modo['modo'] === 'global' ? ", per_cupo_asignado=?, per_cupo_disponible=?" : '')
            . " WHERE per_id=? AND cli_id=?"
        );
        $correoParam = $correo !== '' ? $correo : null;
        if ($modo['modo'] === 'global') {
            $upd->bind_param('sssddii', $nombre, $documento, $correoParam, $cupo, $cupo_disponible_nuevo, $per_id, $cli_id);
        } else {
            $upd->bind_param('sssii', $nombre, $documento, $correoParam, $per_id, $cli_id);
        }
        if (!$upd->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . $mysqli->error]); break; }

        if ($modo['modo'] === 'marca') {
            $marcasCatalogo = cupoMarcasActivas($mysqli);
            $nombresPorId = array();
            foreach ($marcasCatalogo as $m) { $nombresPorId[$m['mar_id']] = $m['mar_descripcion']; }
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $mar_id = (int)$mar_id;
                $monto  = (float)$monto;
                if ($monto <= 0) { continue; }
                $antes = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id);
                if (abs($antes['asignado'] - $monto) > 0.001) {
                    $labelMarca = isset($nombresPorId[$mar_id]) ? $nombresPorId[$mar_id] : ('marca #' . $mar_id);
                    $cambios[] = [
                        'campo' => 'per_cupo_marca_' . $mar_id,
                        'label' => 'Cupo en ' . $labelMarca,
                        'anterior' => '$' . number_format($antes['asignado'], 2),
                        'nuevo'    => '$' . number_format($monto, 2),
                    ];
                }
                cupoUpsertEmpleadoMarca($mysqli, $per_id, $mar_id, $monto);
            }
        }

        foreach ($cambios as $c) {
            $tra = $mysqli->prepare(
                "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $anterior = $c['anterior'] ?? '';
            $nuevo    = $c['nuevo'] ?? '';
            $tra->bind_param('iissss', $per_id, $id_user_sesion, $c['campo'], $c['label'], $anterior, $nuevo);
            $tra->execute();
        }

        echo json_encode(['success' => true, 'cambios' => count($cambios)]);
        break;
```

- [ ] **Step 2: Verificación**

Repetir la verificación de Task 7 pero editando desde `ajax/clientes/clientes.php?action=personal_editar` (con `cli_id` explícito en el POST, como espera este endpoint).

- [ ] **Step 3: Commit**

```bash
git add ajax/clientes/clientes.php
git commit -m "CU-01: backend Clientes — personal_editar con cupo por marca"
```

---

## Task 11: Frontend — Clientes (admin): modal Editar Empleado con cupo por marca

**Files:**
- Modify: `pages/clientes/view.php:505-508` (campo cupo en `modalEmpleado`)
- Modify: `pages/clientes/view.php` (JS: `editarEmpleado`, guardado)

- [ ] **Step 1: HTML**

Reemplazar (líneas 505-508):

```html
                <div class="form-group">
                    <label>Cupo asignado ($)</label>
                    <input type="number" class="form-control" id="emp_cupo" min="0.01" step="0.01">
                </div>
```

por:

```html
                <div class="form-group" id="grupo_emp_cupo_global">
                    <label>Cupo asignado ($)</label>
                    <input type="number" class="form-control" id="emp_cupo" min="0.01" step="0.01">
                </div>
                <div class="form-group" id="grupo_emp_cupo_marca" style="display:none;">
                    <label class="mb-1">Cupo asignado por marca ($)</label>
                    <div id="emp_cupo_marca_inputs" class="row"></div>
                </div>
```

- [ ] **Step 2: JS — cargar modo al abrir el modal**

Reemplazar la función `editarEmpleado` existente:

```javascript
function editarEmpleado(per_id) {
    var p = _personalData[per_id];
    if (!p) return;
    $('#alerta_empleado').html('');
    $('#emp_per_id').val(p.per_id);
    $('#emp_cli_id').val(_cliId);
    $('#emp_nombre').val(p.per_nombre);
    $('#emp_documento').val(p.per_documento || '');
    $('#emp_correo').val(p.per_correo || '');
    $('#emp_cupo').val(p.per_cupo_asignado || '');
    $('#modalEmpleado').modal('show');
}
```

por:

```javascript
function editarEmpleado(per_id) {
    var p = _personalData[per_id];
    if (!p) return;
    $('#alerta_empleado').html('');
    $('#emp_per_id').val(p.per_id);
    $('#emp_cli_id').val(_cliId);
    $('#emp_nombre').val(p.per_nombre);
    $('#emp_documento').val(p.per_documento || '');
    $('#emp_correo').val(p.per_correo || '');
    $('#emp_cupo').val(p.per_cupo_asignado || '');

    $.getJSON('ajax/clientes/clientes.php', { action: 'cupo_convenio_cliente', cli_id: _cliId, per_id: per_id }, function (r) {
        if (r.success && r.modo === 'marca') {
            $('#grupo_emp_cupo_global').hide();
            $('#grupo_emp_cupo_marca').show();
            var valoresActuales = {};
            (r.por_marca || []).forEach(function (m) { if (m.monto_actual > 0) valoresActuales[m.mar_id] = m.monto_actual; });
            renderCupoPorMarcaInputsGenerico('#emp_cupo_marca_inputs', r.por_marca, valoresActuales);
        } else {
            $('#grupo_emp_cupo_global').show();
            $('#grupo_emp_cupo_marca').hide();
        }
        $('#modalEmpleado').modal('show');
    });
}

// Variante genérica del render usado en el modal Cliente (Task 4), reutilizable
// aquí porque ambos modales viven en el mismo archivo pages/clientes/view.php.
function renderCupoPorMarcaInputsGenerico(containerSelector, porMarca, valoresActuales) {
    valoresActuales = valoresActuales || {};
    var html = '';
    (porMarca || []).forEach(function (m) {
        var valor = valoresActuales[m.mar_id] || '';
        html += '<div class="col-md-6 mb-2">'
            + '<label class="small mb-1">' + htmlEsc(m.mar_descripcion) + ' <span class="text-muted">(máx. $' + parseFloat(m.monto_max).toFixed(2) + ')</span></label>'
            + '<div class="input-group input-group-sm">'
            + '<div class="input-group-prepend"><span class="input-group-text">$</span></div>'
            + '<input type="number" class="form-control cupo-marca-input-generico" data-mar-id="' + m.mar_id + '" min="0" step="0.01" value="' + valor + '" placeholder="0.00">'
            + '</div></div>';
    });
    $(containerSelector).html(html);
}
```

- [ ] **Step 3: Backend — nuevo endpoint auxiliar `cupo_convenio_cliente`**

**Files:** Modify: `ajax/clientes/clientes.php` (agregar case nuevo, junto a `personal_trazabilidad_list`)

```php
    // ── Cupo del convenio + valores actuales de un empleado, para el modal de edición ──
    case 'cupo_convenio_cliente':
        header('Content-Type: application/json');
        $cli_id_consulta = (int)($_GET['cli_id'] ?? 0);
        $per_id_consulta = (int)($_GET['per_id'] ?? 0);
        $modo = cupoObtenerModo($mysqli, $cli_id_consulta);
        if ($modo['modo'] !== 'marca') {
            echo json_encode(['success' => true, 'modo' => 'global']);
            break;
        }
        $marcas = cupoMarcasActivas($mysqli);
        $maximos = cupoMaximosPorMarca($mysqli, $cli_id_consulta);
        $actuales = $per_id_consulta ? cupoEmpleadoPorMarca($mysqli, $per_id_consulta) : array();
        $porMarca = array();
        foreach ($marcas as $m) {
            $porMarca[] = array(
                'mar_id'          => $m['mar_id'],
                'mar_descripcion' => $m['mar_descripcion'],
                'monto_max'       => isset($maximos[$m['mar_id']]) ? $maximos[$m['mar_id']] : 0.0,
                'monto_actual'    => isset($actuales[$m['mar_id']]) ? $actuales[$m['mar_id']]['asignado'] : 0.0,
            );
        }
        echo json_encode(['success' => true, 'modo' => 'marca', 'por_marca' => $porMarca]);
        break;
```

- [ ] **Step 4: JS — incluir el desglose al guardar**

En el manejador de `#btn_guardar_empleado`, agregar al `data` del `$.post`:

```javascript
cupo_por_marca: JSON.stringify((function () {
    var out = {};
    $('.cupo-marca-input-generico').each(function () {
        var val = parseFloat($(this).val());
        if (val > 0) out[$(this).data('mar-id')] = val;
    });
    return out;
})())
```

- [ ] **Step 5: Verificación manual**

Editar desde **Clientes** → ficha de "Empresa Demo S.A." → tab Personal → editar el empleado de prueba: debe mostrar los inputs por marca precargados con los valores actuales, guardar un cambio y confirmarlo en BD (mismo query que Task 7).

- [ ] **Step 6: Commit**

```bash
git add pages/clientes/view.php ajax/clientes/clientes.php
git commit -m "CU-01: frontend Clientes — modal Editar Empleado con cupo por marca"
```

---

## Task 12: Backend — Carga Masiva de Personal con cupo por marca

**Files:**
- Modify: `ajax/clientes/clientes.php:300-471` (case `personal_carga_masiva`)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar el case completo (líneas 304-471) por una versión que bifurca por modo. Estructura: se agrega lectura de `$modo` al inicio, y dentro de cada acción (`anadir`, `actualizar_cupo`) se bifurca entre la lógica actual (global) y la nueva (marca). `bloquear` no cambia.

```php
    // ── CL-I: CARGA MASIVA DE PERSONAL ─────────────────────────────────────────
    // Filas ya vienen parseadas en JSON desde el navegador (SheetJS leyó el
    // Excel/CSV). En modo global: columna A cédula, B nombre, C cupo. En modo
    // marca: columna A cédula, B nombre, y una columna de cupo por cada marca
    // (fila['cupos_marca'] = {mar_id: monto}). Siempre para el cliente ya
    // elegido en la ficha — el archivo no trae empresa por fila.
    case 'personal_carga_masiva':
        header('Content-Type: application/json');
        $cli_id       = (int)($_POST['cli_id'] ?? 0);
        $accion       = trim($_POST['accion'] ?? '');
        $filas        = json_decode($_POST['filas'] ?? '[]', true);
        $soloPreview  = !empty($_POST['solo_preview']);

        if (!$cli_id || !in_array($accion, ['anadir', 'actualizar_cupo', 'bloquear']) || !is_array($filas) || !count($filas)) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $modo = cupoObtenerModo($mysqli, $cli_id);
        $cupo_max = $modo['valor_global'];
        $maximosPorMarca = $modo['modo'] === 'marca' ? cupoMaximosPorMarca($mysqli, $cli_id) : array();
        $marcasCatalogo = $modo['modo'] === 'marca' ? cupoMarcasActivas($mysqli) : array();
        $nombresMarcaPorId = array();
        foreach ($marcasCatalogo as $m) { $nombresMarcaPorId[$m['mar_id']] = $m['mar_descripcion']; }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $agregados = 0; $actualizados = 0; $bloqueados = 0; $omitidos = []; $detalle = [];

        foreach ($filas as $fila) {
            $cedula = trim((string)($fila['cedula'] ?? ''));
            $nombre = trim((string)($fila['nombre'] ?? ''));
            $cuposMarca = ($modo['modo'] === 'marca' && isset($fila['cupos_marca']) && is_array($fila['cupos_marca'])) ? $fila['cupos_marca'] : array();
            $cupo   = isset($fila['cupo']) ? (float)$fila['cupo'] : 0;

            if ($cedula === '') {
                $omitidos[] = ['cedula' => '(vacía)', 'motivo' => 'Fila sin cédula'];
                $detalle[]  = ['cedula' => '(vacía)', 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Fila sin cédula — se omite', 'aplica' => false];
                continue;
            }

            if ($accion === 'anadir') {
                if ($modo['modo'] === 'marca') {
                    $algunaMarcaValida = false;
                    $excedeTope = null;
                    foreach ($cuposMarca as $mar_id => $monto) {
                        $monto = (float)$monto;
                        if ($monto <= 0) continue;
                        $algunaMarcaValida = true;
                        $tope = isset($maximosPorMarca[(int)$mar_id]) ? $maximosPorMarca[(int)$mar_id] : 0;
                        if ($tope > 0 && $monto > $tope) { $excedeTope = isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id); break; }
                    }
                    if ($nombre === '' || !$algunaMarcaValida) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Nombre o cupo inválido'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Nombre o cupo inválido — se omite', 'aplica' => false];
                        continue;
                    }
                    if ($excedeTope !== null) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la marca ' . $excedeTope];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Cupo excede el máximo de la marca ' . $excedeTope . ' — se omite', 'aplica' => false];
                        continue;
                    }
                } else {
                    if ($nombre === '' || $cupo <= 0) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Nombre o cupo inválido'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Nombre o cupo inválido — se omite', 'aplica' => false];
                        continue;
                    }
                    if ($cupo_max > 0 && $cupo > $cupo_max) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ')'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ') — se omite', 'aplica' => false];
                        continue;
                    }
                }

                $chk = $mysqli->prepare("SELECT per_id, per_estado FROM personal WHERE per_documento = ? LIMIT 1");
                $chk->bind_param('s', $cedula);
                $chk->execute();
                $existente = $chk->get_result()->fetch_assoc();
                if ($existente) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ya existe (en esta u otra empresa)'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $existente['per_estado'], 'resultado' => 'Ya existe (en esta u otra empresa) — no se hará nada', 'aplica' => false];
                    continue;
                }

                if ($soloPreview) {
                    if ($modo['modo'] === 'marca') {
                        $resumenMarcas = array();
                        foreach ($cuposMarca as $mar_id => $monto) {
                            $monto = (float)$monto;
                            if ($monto > 0) { $resumenMarcas[] = (isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id)) . ' $' . number_format($monto, 2); }
                        }
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Se creará como empleado nuevo — ' . implode(', ', $resumenMarcas), 'aplica' => true];
                    } else {
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Se creará como empleado nuevo, cupo $' . number_format($cupo, 2), 'aplica' => true];
                    }
                    continue;
                }

                $num_tarjeta = str_pad((string)mt_rand(1000, 9999), 4, '0') . str_pad((string)mt_rand(1000, 9999), 4, '0')
                             . str_pad((string)mt_rand(1000, 9999), 4, '0') . str_pad((string)mt_rand(1000, 9999), 4, '0');
                $cupoInsert = $modo['modo'] === 'marca' ? 0 : $cupo;
                $ins = $mysqli->prepare(
                    "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible)
                     VALUES (?, ?, ?, ?, 'activo', ?, ?)"
                );
                $ins->bind_param('sssidd', $nombre, $cedula, $num_tarjeta, $cli_id, $cupoInsert, $cupoInsert);
                if ($ins->execute()) {
                    $nuevo_id = $mysqli->insert_id;
                    if ($modo['modo'] === 'marca') {
                        foreach ($cuposMarca as $mar_id => $monto) {
                            $monto = (float)$monto;
                            if ($monto > 0) cupoUpsertEmpleadoMarca($mysqli, $nuevo_id, $mar_id, $monto);
                        }
                        $cupoTxt = 'Alta por carga masiva (por marca)';
                    } else {
                        $cupoTxt = '$' . number_format($cupo, 2);
                    }
                    $tra = $mysqli->prepare(
                        "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                         VALUES (?, ?, 'alta_masiva', 'Alta por carga masiva', '', ?)"
                    );
                    $tra->bind_param('iis', $nuevo_id, $id_user_sesion, $cupoTxt);
                    $tra->execute();
                    $agregados++;
                } else {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Error al guardar'];
                }

            } elseif ($accion === 'actualizar_cupo') {
                $find = $mysqli->prepare("SELECT per_id, per_estado, per_cupo_asignado, per_cupo_disponible FROM personal WHERE per_documento = ? AND cli_id = ? LIMIT 1");
                $find->bind_param('si', $cedula, $cli_id);
                $find->execute();
                $emp = $find->get_result()->fetch_assoc();
                if (!$emp) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'No encontrada en este cliente'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'No encontrada en este cliente — se omite', 'aplica' => false];
                    continue;
                }

                if ($modo['modo'] === 'marca') {
                    // Actualización parcial: solo se tocan las marcas presentes
                    // con valor > 0 en el archivo; una marca ausente o vacía
                    // deja el cupo existente sin tocar (regla confirmada con cliente).
                    $marcasAAplicar = array();
                    $excedeTope = null;
                    foreach ($cuposMarca as $mar_id => $monto) {
                        $monto = (float)$monto;
                        if ($monto <= 0) continue;
                        $tope = isset($maximosPorMarca[(int)$mar_id]) ? $maximosPorMarca[(int)$mar_id] : 0;
                        if ($tope > 0 && $monto > $tope) { $excedeTope = isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id); break; }
                        $marcasAAplicar[(int)$mar_id] = $monto;
                    }
                    if ($excedeTope !== null) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la marca ' . $excedeTope];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Cupo excede el máximo de la marca ' . $excedeTope . ' — se omite', 'aplica' => false];
                        continue;
                    }
                    if (!count($marcasAAplicar)) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ninguna marca con cupo en el archivo'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Ninguna marca con cupo en el archivo — se omite', 'aplica' => false];
                        continue;
                    }

                    if ($soloPreview) {
                        $resumenMarcas = array();
                        foreach ($marcasAAplicar as $mar_id => $monto) {
                            $resumenMarcas[] = (isset($nombresMarcaPorId[$mar_id]) ? $nombresMarcaPorId[$mar_id] : ('marca #' . $mar_id)) . ' → $' . number_format($monto, 2);
                        }
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se actualizará: ' . implode(', ', $resumenMarcas), 'aplica' => true];
                        continue;
                    }

                    foreach ($marcasAAplicar as $mar_id => $monto) {
                        $antes = cupoEmpleadoEnMarca($mysqli, $emp['per_id'], $mar_id);
                        cupoUpsertEmpleadoMarca($mysqli, $emp['per_id'], $mar_id, $monto);
                        $labelMarca = isset($nombresMarcaPorId[$mar_id]) ? $nombresMarcaPorId[$mar_id] : ('marca #' . $mar_id);
                        $tra = $mysqli->prepare(
                            "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                             VALUES (?, ?, ?, 'Cupo actualizado por carga masiva', ?, ?)"
                        );
                        $campoTra = 'per_cupo_marca_' . $mar_id;
                        $antTxt = '$' . number_format($antes['asignado'], 2);
                        $nvoTxt = '$' . number_format($monto, 2);
                        $tra->bind_param('iisss', $emp['per_id'], $id_user_sesion, $campoTra, $antTxt, $nvoTxt);
                        $tra->execute();
                    }
                    $actualizados++;
                } else {
                    if ($cupo <= 0) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo inválido'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Cupo inválido — se omite', 'aplica' => false];
                        continue;
                    }
                    if ($cupo_max > 0 && $cupo > $cupo_max) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ')'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ') — se omite', 'aplica' => false];
                        continue;
                    }

                    $cupo_anterior   = (float)$emp['per_cupo_asignado'];
                    $consumido       = $cupo_anterior - (float)$emp['per_cupo_disponible'];
                    $cupo_disp_nuevo = max(0, $cupo - $consumido);

                    if ($soloPreview) {
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se actualizará el cupo de $' . number_format($cupo_anterior, 2) . ' a $' . number_format($cupo, 2), 'aplica' => true];
                        continue;
                    }

                    $upd = $mysqli->prepare("UPDATE personal SET per_cupo_asignado = ?, per_cupo_disponible = ? WHERE per_id = ?");
                    $upd->bind_param('ddi', $cupo, $cupo_disp_nuevo, $emp['per_id']);
                    $upd->execute();

                    $antTxt = '$' . number_format($cupo_anterior, 2);
                    $nvoTxt = '$' . number_format($cupo, 2);
                    $tra = $mysqli->prepare(
                        "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                         VALUES (?, ?, 'per_cupo_asignado', 'Cupo actualizado por carga masiva', ?, ?)"
                    );
                    $tra->bind_param('iiss', $emp['per_id'], $id_user_sesion, $antTxt, $nvoTxt);
                    $tra->execute();
                    $actualizados++;
                }

            } elseif ($accion === 'bloquear') {
                $find = $mysqli->prepare("SELECT per_id, per_estado FROM personal WHERE per_documento = ? AND cli_id = ? LIMIT 1");
                $find->bind_param('si', $cedula, $cli_id);
                $find->execute();
                $emp = $find->get_result()->fetch_assoc();
                if (!$emp) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'No encontrada en este cliente'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'No encontrada en este cliente — se omite', 'aplica' => false];
                    continue;
                }
                if ($emp['per_estado'] === 'bloqueado') {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ya estaba bloqueada'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'bloqueado', 'resultado' => 'Esta persona ya se encuentra bloqueada — no se hará nada', 'aplica' => false];
                    continue;
                }

                if ($soloPreview) {
                    $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se bloqueará', 'aplica' => true];
                    continue;
                }

                $upd = $mysqli->prepare("UPDATE personal SET per_estado = 'bloqueado' WHERE per_id = ?");
                $upd->bind_param('i', $emp['per_id']);
                $upd->execute();

                $tra = $mysqli->prepare(
                    "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                     VALUES (?, ?, 'per_estado', 'Bloqueo por carga masiva', ?, 'bloqueado')"
                );
                $tra->bind_param('iis', $emp['per_id'], $id_user_sesion, $emp['per_estado']);
                $tra->execute();
                $bloqueados++;
            }
        }

        if ($soloPreview) {
            $aplicaran = 0;
            foreach ($detalle as $d) { if ($d['aplica']) $aplicaran++; }
            echo json_encode(['success' => true, 'preview' => true, 'detalle' => $detalle, 'total' => count($detalle), 'aplicaran' => $aplicaran]);
            break;
        }

        echo json_encode(['success' => true, 'resultados' => [
            'agregados' => $agregados, 'actualizados' => $actualizados, 'bloqueados' => $bloqueados, 'omitidos' => $omitidos
        ]]);
        break;
```

- [ ] **Step 2: Verificación con curl (preview, modo marca)**

```bash
curl -s -X POST "http://localhost/SGC_ARGOS26/ajax/clientes/clientes.php?action=personal_carga_masiva" \
  -H "Cookie: PHPSESSID=<pegar-aqui>" \
  --data-urlencode "cli_id=13" \
  --data-urlencode "accion=anadir" \
  --data-urlencode "solo_preview=1" \
  --data-urlencode 'filas=[{"cedula":"0912345000","nombre":"Prueba CSV","cupos_marca":{"<mar_id_pizza>":25,"<mar_id_fridays>":15}}]'
```
Expected: `"preview":true`, `"detalle"` con `"aplica":true` y `"resultado"` mencionando ambas marcas con sus montos.

- [ ] **Step 3: Commit**

```bash
git add ajax/clientes/clientes.php
git commit -m "CU-01: backend Carga Masiva — soporte de cupo por marca (Añadir / Actualizar cupo)"
```

---

## Task 13: Frontend — Carga Masiva: plantilla y lectura de columnas por marca

**Files:**
- Modify: `pages/clientes/view.php:632-698` (pantalla 1 del modal, texto de ayuda)
- Modify: `pages/clientes/view.php` (JS: `abrirModalCargaMasiva`, `descargarPlantillaCargaMasiva`, lectura del archivo)

- [ ] **Step 1: Actualizar el texto de ayuda y cargar el modo al abrir el modal**

Reemplazar `abrirModalCargaMasiva`:

```javascript
function abrirModalCargaMasiva() {
    $('#cm_cliente_nombre').text(_cliData ? _cliData.cli_descripcion : '');
    $('.cm-tile').removeClass('sel');
    $('.cm-tile[data-accion="anadir"]').addClass('sel');
    $('#cm_accion').val('anadir');
    $('#cm_archivo').val('');
    $('#cm_file_chip').removeClass('show');
    $('#cm_file_name').text('');
    $('#alerta_carga_masiva').html('');
    _cmFilasPendientes = null;
    _cmAccionPendiente = null;
    cmGoScreen(1);
    $('#modalCargaMasiva').modal('show');
}
```

por:

```javascript
var _cmModoCupo = 'global';
var _cmMarcasCatalogo = [];

function abrirModalCargaMasiva() {
    $('#cm_cliente_nombre').text(_cliData ? _cliData.cli_descripcion : '');
    $('.cm-tile').removeClass('sel');
    $('.cm-tile[data-accion="anadir"]').addClass('sel');
    $('#cm_accion').val('anadir');
    $('#cm_archivo').val('');
    $('#cm_file_chip').removeClass('show');
    $('#cm_file_name').text('');
    $('#alerta_carga_masiva').html('');
    _cmFilasPendientes = null;
    _cmAccionPendiente = null;
    cmGoScreen(1);

    $.getJSON('ajax/clientes/clientes.php', { action: 'cupo_convenio_cliente', cli_id: _cliId }, function (r) {
        _cmModoCupo = (r.success && r.modo === 'marca') ? 'marca' : 'global';
        _cmMarcasCatalogo = (r.success && r.por_marca) ? r.por_marca : [];
        var columnas = _cmModoCupo === 'marca'
            ? _cmMarcasCatalogo.map(function (m) { return 'Cupo ' + m.mar_descripcion; }).join(' · ')
            : 'Cupo';
        $('.cm-help-row .txt').html('<b>Columna A</b> cédula · <b>B</b> nombre completo · <b>' + (_cmModoCupo === 'marca' ? columnas : 'C</b> cupo') + '</b> (solo para Añadir / Actualizar). El encabezado es opcional, si lo incluyes se detecta y se omite solo.');
        $('#modalCargaMasiva').modal('show');
    });
}
```

- [ ] **Step 2: Plantilla dinámica**

Reemplazar `descargarPlantillaCargaMasiva`:

```javascript
function descargarPlantillaCargaMasiva() {
    var datos = [
        ['Cédula', 'Nombre completo', 'Cupo'],
        ['0102030405', 'Juan Pérez Ejemplo', 50],
        ['0607080910', 'María Gómez Ejemplo', 30],
        ['1112131415', 'Carlos Torres Ejemplo', 100]
    ];
    var ws = XLSX.utils.aoa_to_sheet(datos);
    ws['!cols'] = [{ wch: 14 }, { wch: 28 }, { wch: 10 }];
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Personal');
    XLSX.writeFile(wb, 'plantilla_carga_masiva_personal.xlsx');
}
```

por:

```javascript
function descargarPlantillaCargaMasiva() {
    var encabezados, filaEjemplo1, filaEjemplo2, filaEjemplo3, anchos;
    if (_cmModoCupo === 'marca' && _cmMarcasCatalogo.length) {
        encabezados = ['Cédula', 'Nombre completo'].concat(_cmMarcasCatalogo.map(function (m) { return 'Cupo ' + m.mar_descripcion; }));
        filaEjemplo1 = ['0102030405', 'Juan Pérez Ejemplo'].concat(_cmMarcasCatalogo.map(function (_, i) { return i === 0 ? 50 : ''; }));
        filaEjemplo2 = ['0607080910', 'María Gómez Ejemplo'].concat(_cmMarcasCatalogo.map(function (_, i) { return i === 1 ? 30 : ''; }));
        filaEjemplo3 = ['1112131415', 'Carlos Torres Ejemplo'].concat(_cmMarcasCatalogo.map(function () { return ''; }));
        anchos = [{ wch: 14 }, { wch: 28 }].concat(_cmMarcasCatalogo.map(function () { return { wch: 14 }; }));
    } else {
        encabezados = ['Cédula', 'Nombre completo', 'Cupo'];
        filaEjemplo1 = ['0102030405', 'Juan Pérez Ejemplo', 50];
        filaEjemplo2 = ['0607080910', 'María Gómez Ejemplo', 30];
        filaEjemplo3 = ['1112131415', 'Carlos Torres Ejemplo', 100];
        anchos = [{ wch: 14 }, { wch: 28 }, { wch: 10 }];
    }
    var datos = [encabezados, filaEjemplo1, filaEjemplo2, filaEjemplo3];
    var ws = XLSX.utils.aoa_to_sheet(datos);
    ws['!cols'] = anchos;
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Personal');
    XLSX.writeFile(wb, 'plantilla_carga_masiva_personal.xlsx');
}
```

- [ ] **Step 3: Lectura del archivo — columnas de marca por posición**

Reemplazar el cuerpo de `reader.onload` dentro de `$('#btn_procesar_carga_masiva').on('click', ...)`:

```javascript
    reader.onload = function (e) {
        var wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
        var ws = wb.Sheets[wb.SheetNames[0]];
        var rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: true, defval: '' });

        var filas = [];
        rows.forEach(function (r, idx) {
            var cedula = (r[0] !== undefined && r[0] !== null) ? String(r[0]).trim() : '';
            var nombre = (r[1] !== undefined && r[1] !== null) ? String(r[1]).trim() : '';
            if (idx === 0 && cedula && !/\d/.test(cedula)) return;
            if (cedula && /^\d+$/.test(cedula) && cedula.length < 10) {
                cedula = cedula.padStart(10, '0');
            }
            if (!cedula) return;

            if (_cmModoCupo === 'marca') {
                var cupos_marca = {};
                _cmMarcasCatalogo.forEach(function (m, i) {
                    var col = 2 + i; // columna C en adelante, una por marca, en el mismo orden que la plantilla
                    var val = (r[col] !== undefined && r[col] !== null && r[col] !== '') ? parseFloat(r[col]) : null;
                    if (val !== null && !isNaN(val)) cupos_marca[m.mar_id] = val;
                });
                filas.push({ cedula: cedula, nombre: nombre, cupos_marca: cupos_marca });
            } else {
                var cupo = (r[2] !== undefined && r[2] !== null && r[2] !== '') ? parseFloat(r[2]) : null;
                filas.push({ cedula: cedula, nombre: nombre, cupo: cupo });
            }
        });

        if (!filas.length) {
            $('#alerta_carga_masiva').html('<div class="alert alert-warning mb-0">No se encontraron filas con cédula en el archivo.</div>');
            return;
        }

        _cmFilasPendientes = filas;
        _cmAccionPendiente = accion;
        $('#alerta_carga_masiva').html('<div class="text-center p-2"><span class="spinner-border spinner-border-sm"></span> Analizando archivo...</div>');

        $.post('ajax/clientes/clientes.php?action=personal_carga_masiva', {
            cli_id: _cliId, accion: accion, filas: JSON.stringify(filas), solo_preview: 1
        }, function (res) {
            if (!res.success) {
                $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">' + (res.mensaje || 'Error al analizar el archivo') + '</div>');
                return;
            }
            renderPreviewCargaMasiva(res);
        }, 'json').fail(function () {
            $('#alerta_carga_masiva').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
        });
    };
```

- [ ] **Step 4: Confirmar al aplicar (usar `_cmFilasPendientes`/`_cmAccionPendiente` tal como ya hace `btn_confirmar_carga_masiva` — sin cambios, ya envían `filas` con la forma correcta según el modo).**

Revisar que el manejador de `#btn_confirmar_carga_masiva` (que hace el POST final sin `solo_preview`) siga usando `JSON.stringify(_cmFilasPendientes)` — no requiere cambios, ya que `_cmFilasPendientes` ya trae `cupos_marca` cuando aplica.

- [ ] **Step 5: Verificación manual end-to-end**

1. Con "Empresa Demo S.A." en modo `marca`, abrir **Clientes** → ficha → Personal → "Carga Masiva".
2. Confirmar que el texto de ayuda menciona "Cupo Pizza Hut · Cupo Happy · ..." en vez de una sola columna "Cupo".
3. Descargar la plantilla → abrir el Excel → confirmar que tiene una columna por marca.
4. Llenar una fila nueva con cédula, nombre, $20 en Pizza Hut, $10 en Fridays; subir el archivo con acción "Añadir empleados".
5. En la vista previa, confirmar que el resultado menciona ambas marcas y montos.
6. Confirmar y aplicar → verificar en BD:
```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT p.per_nombre, pcm.* FROM personal p JOIN personal_cupo_marca pcm ON p.per_id=pcm.per_id WHERE p.per_documento='<cedula-usada>';"
```

- [ ] **Step 6: Commit**

```bash
git add pages/clientes/view.php
git commit -m "CU-01: frontend Carga Masiva — plantilla y lectura de columnas por marca"
```

---

## Task 14: Backend — POS: `buscar` con cupo de la marca del local activo

**Files:**
- Modify: `ajax/pos/pos.php:1-6` (require del helper)
- Modify: `ajax/pos/pos.php:88-114` (rama cédula/tarjeta de `case 'buscar'`)

- [ ] **Step 1: Incluir el helper**

Después de `require_once '../../helpers/session_helpers.php';` (línea 5):

```php
require_once '../../helpers/session_helpers.php';
require_once '../../helpers/cupo_marca_helpers.php';
```

- [ ] **Step 2: Reemplazar la rama de búsqueda por cédula/tarjeta**

Reemplazar (líneas 88-114):

```php
        if (preg_match('/^\d+$/', $input)) {
            $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                             p.per_cupo_asignado, p.per_cupo_disponible,
                             c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                      FROM personal p
                      JOIN cliente c ON p.cli_id = c.cli_id
                      WHERE p.per_documento = '$input' OR p.per_numero_tarjeta = '$input'
                      LIMIT 1";
            $result = mysqli_query($mysqli, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $data = mysqli_fetch_assoc($result);
                if ($data['per_estado'] === 'suspendido') {
                    echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    exit;
                }
                if ($data['per_estado'] === 'bloqueado') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra bloqueada. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }
                if ($data['per_estado'] === 'inactivo') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra inactiva. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }
                echo json_encode(['success' => true, 'tipo' => 'empleado', 'data' => $data]);
                break;
            }
        }
```

por:

```php
        if (preg_match('/^\d+$/', $input)) {
            $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                             p.per_cupo_asignado, p.per_cupo_disponible,
                             c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                      FROM personal p
                      JOIN cliente c ON p.cli_id = c.cli_id
                      WHERE p.per_documento = '$input' OR p.per_numero_tarjeta = '$input'
                      LIMIT 1";
            $result = mysqli_query($mysqli, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $data = mysqli_fetch_assoc($result);
                if ($data['per_estado'] === 'suspendido') {
                    echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    exit;
                }
                if ($data['per_estado'] === 'bloqueado') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra bloqueada. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }
                if ($data['per_estado'] === 'inactivo') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra inactiva. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }

                $modo = cupoObtenerModo($mysqli, $data['cli_id']);
                $data['cli_modo_cupo'] = $modo['modo'];
                if ($modo['modo'] === 'marca') {
                    $loc_id_actual = resolverLocId($mysqli);
                    $mar_id_actual = $loc_id_actual ? cupoMarcaDeLocal($mysqli, $loc_id_actual) : null;
                    if ($mar_id_actual === null) {
                        $data['per_cupo_asignado']   = 0;
                        $data['per_cupo_disponible'] = 0;
                        $data['marca_no_resuelta']   = true;
                    } else {
                        $cupoMarca = cupoEmpleadoEnMarca($mysqli, $data['per_id'], $mar_id_actual);
                        $data['per_cupo_asignado']   = $cupoMarca['asignado'];
                        $data['per_cupo_disponible'] = $cupoMarca['disponible'];
                    }
                }

                echo json_encode(['success' => true, 'tipo' => 'empleado', 'data' => $data]);
                break;
            }
        }
```

- [ ] **Step 2: Verificación con curl**

Requiere sesión de un cajero con `$_SESSION['loc_id']` fijo en un local de la marca correspondiente:

```bash
curl -s "http://localhost/SGC_ARGOS26/ajax/pos/pos.php?action=buscar&cedula=<cedula-de-prueba>" -H "Cookie: PHPSESSID=<pegar-aqui>"
```
Expected: `"cli_modo_cupo":"marca"`, `"per_cupo_disponible"` mostrando el disponible **de la marca del local del cajero**, no el total entre marcas.

- [ ] **Step 3: Commit**

```bash
git add ajax/pos/pos.php
git commit -m "CU-01: backend POS — buscar muestra cupo de la marca del local activo"
```

---

## Task 15: Backend — POS: `registrar` valida y descuenta por marca

**Files:**
- Modify: `ajax/pos/pos.php:252-289` (validación de cupo en `case 'registrar'`)
- Modify: `ajax/pos/pos.php:345-346` (descuento de cupo)

- [ ] **Step 1: Reemplazar la validación**

Reemplazar (líneas 261-289, desde el chequeo `if ($per_id === 0 ...` hasta el fin de la validación de cupo):

```php
        if ($per_id === 0 || $monto_convenio <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar empleado y cupo
        $qEmp = "SELECT per_nombre, per_estado, per_cupo_disponible FROM personal WHERE per_id = $per_id";
        $rEmp = mysqli_query($mysqli, $qEmp);

        if (!$rEmp || mysqli_num_rows($rEmp) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $emp = mysqli_fetch_assoc($rEmp);

        if ($emp['per_estado'] === 'suspendido') {
            echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida.']);
            exit;
        }
        if ($emp['per_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no activo']);
            break;
        }

        if ($monto_convenio > (float)$emp['per_cupo_disponible']) {
            echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible ($' . number_format($emp['per_cupo_disponible'], 2) . ')']);
            break;
        }
```

por:

```php
        if ($per_id === 0 || $monto_convenio <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar empleado y cupo
        $qEmp = "SELECT per_nombre, per_estado, per_cupo_disponible, cli_id FROM personal WHERE per_id = $per_id";
        $rEmp = mysqli_query($mysqli, $qEmp);

        if (!$rEmp || mysqli_num_rows($rEmp) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $emp = mysqli_fetch_assoc($rEmp);

        if ($emp['per_estado'] === 'suspendido') {
            echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida.']);
            exit;
        }
        if ($emp['per_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no activo']);
            break;
        }

        $modoCupoEmp = cupoObtenerModo($mysqli, $emp['cli_id']);
        $mar_id_venta = null;
        if ($modoCupoEmp['modo'] === 'marca') {
            $mar_id_venta = $loc_id ? cupoMarcaDeLocal($mysqli, $loc_id) : null;
            if ($mar_id_venta === null) {
                echo json_encode(['success' => false, 'mensaje' => 'No se pudo determinar la marca del local para validar el cupo. Contacte a soporte.']);
                break;
            }
            $cupoMarcaEmp = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id_venta);
            if ($monto_convenio > $cupoMarcaEmp['disponible']) {
                echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible en esta marca ($' . number_format($cupoMarcaEmp['disponible'], 2) . ')']);
                break;
            }
        } else {
            if ($monto_convenio > (float)$emp['per_cupo_disponible']) {
                echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible ($' . number_format($emp['per_cupo_disponible'], 2) . ')']);
                break;
            }
        }
```

- [ ] **Step 2: Reemplazar el descuento**

Reemplazar (línea 345-346):

```php
        // Descontar cupo del empleado
        mysqli_query($mysqli, "UPDATE personal SET per_cupo_disponible = per_cupo_disponible - $monto_convenio WHERE per_id = $per_id");
```

por:

```php
        // Descontar cupo del empleado (por marca si el convenio está en ese modo)
        if ($modoCupoEmp['modo'] === 'marca') {
            cupoDescontarEmpleadoMarca($mysqli, $per_id, $mar_id_venta, $monto_convenio);
        } else {
            mysqli_query($mysqli, "UPDATE personal SET per_cupo_disponible = per_cupo_disponible - $monto_convenio WHERE per_id = $per_id");
        }
```

- [ ] **Step 3: Verificación end-to-end (los dos escenarios del cliente)**

**Escenario Global** ("Diego recibe $400 y los gasta como quiera"): con un empleado de un convenio en modo `global`, registrar dos ventas en locales de marcas distintas y confirmar que ambas descuentan del mismo `per_cupo_disponible`.

**Escenario Por marca** ("$50 en Pizza Hut, $30 en otra marca"): con el empleado de prueba de Tasks 6-13 (convenio en modo `marca`), registrar una venta de $20 en un local de Pizza Hut:

```bash
curl -s -X POST "http://localhost/SGC_ARGOS26/ajax/pos/pos.php" \
  -H "Cookie: PHPSESSID=<cookie-de-un-cajero-con-loc_id-en-Pizza-Hut>" \
  --data-urlencode "action=registrar" \
  --data-urlencode "per_id=<per_id-de-prueba>" \
  --data-urlencode "monto_convenio=20"
```
Expected: `{"success":true,"con_id":...}`. Luego:
```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM personal_cupo_marca WHERE per_id=<per_id-de-prueba>;"
```
El disponible de Pizza Hut debe haber bajado en 20; el de Fridays debe seguir intacto (confirma que no se prestan cupos entre marcas, la regla clave del audio del cliente).

Repetir intentando pagar $999 (más que el disponible en Pizza Hut) → debe rechazar con "El monto supera el cupo disponible en esta marca".

- [ ] **Step 4: Commit**

```bash
git add ajax/pos/pos.php
git commit -m "CU-01: backend POS — registrar valida y descuenta cupo por marca"
```

---

## Task 16: Backend — POS: `anular_venta` devuelve el cupo a la marca correcta

**Files:**
- Modify: `ajax/pos/pos.php:492-525` (devolución de cupo en `case 'anular_venta'`)

- [ ] **Step 1: Reemplazar el bloque de devolución de cupo**

Reemplazar (líneas 492-525, desde el `$stmt = $mysqli->prepare(` del SELECT del consumo hasta el cierre del bloque de devolución de cupo de convenio):

```php
        $stmt = $mysqli->prepare(
            "SELECT con_id, con_fecha, con_estado, per_id, con_monto_convenio,
                    con_giftcard_codigo, con_monto_giftcard
             FROM consumo WHERE con_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $con = $stmt->get_result()->fetch_assoc();

        if (!$con) { echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada']); break; }
        if ($con['con_fecha'] !== date('Y-m-d')) {
            echo json_encode(['success' => false, 'mensaje' => 'Solo se pueden anular ventas del mismo día']);
            break;
        }
        if ($con['con_estado'] === 'anulado') {
            echo json_encode(['success' => false, 'mensaje' => 'Esta venta ya estaba anulada']);
            break;
        }

        $mysqli->begin_transaction();
        try {
            $upd = $mysqli->prepare("UPDATE consumo SET con_estado = 'anulado' WHERE con_id = ?");
            $upd->bind_param('i', $con_id);
            if (!$upd->execute()) throw new Exception('Error al anular la venta');

            // Devolver cupo de convenio consumido
            $montoConvenio = (float)$con['con_monto_convenio'];
            if ($montoConvenio > 0 && $con['per_id']) {
                $updCupo = $mysqli->prepare(
                    "UPDATE personal SET per_cupo_disponible = LEAST(per_cupo_asignado, per_cupo_disponible + ?) WHERE per_id = ?"
                );
                $updCupo->bind_param('di', $montoConvenio, $con['per_id']);
                if (!$updCupo->execute()) throw new Exception('Error al devolver el cupo');
            }
```

por:

```php
        $stmt = $mysqli->prepare(
            "SELECT con_id, con_fecha, con_estado, per_id, con_monto_convenio,
                    con_giftcard_codigo, con_monto_giftcard, loc_id
             FROM consumo WHERE con_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $con = $stmt->get_result()->fetch_assoc();

        if (!$con) { echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada']); break; }
        if ($con['con_fecha'] !== date('Y-m-d')) {
            echo json_encode(['success' => false, 'mensaje' => 'Solo se pueden anular ventas del mismo día']);
            break;
        }
        if ($con['con_estado'] === 'anulado') {
            echo json_encode(['success' => false, 'mensaje' => 'Esta venta ya estaba anulada']);
            break;
        }

        $mysqli->begin_transaction();
        try {
            $upd = $mysqli->prepare("UPDATE consumo SET con_estado = 'anulado' WHERE con_id = ?");
            $upd->bind_param('i', $con_id);
            if (!$upd->execute()) throw new Exception('Error al anular la venta');

            // Devolver cupo de convenio consumido (por marca si el convenio está en ese modo)
            $montoConvenio = (float)$con['con_monto_convenio'];
            if ($montoConvenio > 0 && $con['per_id']) {
                $perCli = $mysqli->prepare("SELECT cli_id FROM personal WHERE per_id = ?");
                $perCli->bind_param('i', $con['per_id']);
                $perCli->execute();
                $cliDeEmpleado = $perCli->get_result()->fetch_assoc();
                $modoAnulacion = $cliDeEmpleado ? cupoObtenerModo($mysqli, $cliDeEmpleado['cli_id']) : array('modo' => 'global');

                if ($modoAnulacion['modo'] === 'marca') {
                    $marDeVenta = $con['loc_id'] ? cupoMarcaDeLocal($mysqli, $con['loc_id']) : null;
                    if ($marDeVenta === null) throw new Exception('No se pudo determinar la marca de la venta original para devolver el cupo');
                    cupoDevolverEmpleadoMarca($mysqli, $con['per_id'], $marDeVenta, $montoConvenio);
                } else {
                    $updCupo = $mysqli->prepare(
                        "UPDATE personal SET per_cupo_disponible = LEAST(per_cupo_asignado, per_cupo_disponible + ?) WHERE per_id = ?"
                    );
                    $updCupo->bind_param('di', $montoConvenio, $con['per_id']);
                    if (!$updCupo->execute()) throw new Exception('Error al devolver el cupo');
                }
            }
```

- [ ] **Step 2: Verificación**

Anular la venta registrada en Task 15 (Escenario Por marca):

```bash
curl -s -X POST "http://localhost/SGC_ARGOS26/ajax/pos/pos.php" \
  -H "Cookie: PHPSESSID=<misma-cookie-del-cajero>" \
  --data-urlencode "action=anular_venta" \
  --data-urlencode "con_id=<con_id-devuelto-en-Task-15>" \
  --data-urlencode "motivo=Prueba de reversión CU-01"
```
Expected: `{"success":true,...}`. Verificar en BD que el disponible de Pizza Hut volvió al valor previo a la venta (`/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "SELECT * FROM personal_cupo_marca WHERE per_id=<per_id-de-prueba>;"`).

- [ ] **Step 3: Commit**

```bash
git add ajax/pos/pos.php
git commit -m "CU-01: backend POS — anular_venta devuelve cupo a la marca correcta"
```

---

## Task 17: Verificación end-to-end de los dos escenarios del cliente + limpieza de datos de prueba

**Files:** ninguno (solo verificación manual + limpieza de datos creados durante las pruebas)

- [ ] **Step 1: Escenario A — Global ("Diego gasta $400 donde quiera")**

1. Crear/usar un convenio en modo `global` con `cli_valor_beneficio = 400`.
2. Crear un empleado con cupo $400.
3. Vender $250 en un local de una marca, luego $150 en un local de otra marca.
4. Confirmar que el segundo pago es aceptado (quedaba $150 disponible) y que tras ambas ventas `per_cupo_disponible = 0`.

- [ ] **Step 2: Escenario B — Por marca ("$50 en Pizza Hut, $30 en otra marca")**

1. Usar "Empresa Demo S.A." en modo `marca` con Pizza Hut máx. $100 y Fridays máx. $50 (Task 4).
2. Crear un empleado con $50 en Pizza Hut y $30 en Fridays (Task 6/9).
3. Vender $50 en un local Pizza Hut → debe aceptar y dejar Pizza Hut en $0 disponible.
4. Intentar vender $1 más en Pizza Hut → debe rechazar ("supera el cupo disponible en esta marca").
5. Vender $30 en un local Fridays → debe aceptar usando el cupo de Fridays, sin verse afectado por que Pizza Hut esté en $0 (confirma que los cupos no se prestan entre marcas).
6. Intentar vender en un local de una tercera marca sin cupo asignado (ej. KFC) → debe rechazar con cupo $0.
7. Anular la venta de Fridays → el disponible de Fridays debe volver a $30, Pizza Hut sigue en $0.

- [ ] **Step 3: Confirmar que los convenios existentes (modo `global` por defecto) siguen funcionando exactamente igual**

Repetir una venta con cualquier empleado de un convenio que **no** se haya tocado durante este plan (ej. cli_id 1-10 del seed original) y confirmar que el flujo es idéntico al de antes del cambio (sin mensajes ni columnas nuevas visibles).

- [ ] **Step 4: Limpiar datos de prueba creados durante la implementación**

```bash
/c/xampp/mysql/bin/mysql.exe -u root sgipro_sgc_argos -e "
DELETE FROM personal_cupo_marca WHERE per_id IN (SELECT per_id FROM personal WHERE per_documento IN ('9999999999','0999999911','0912345000'));
DELETE FROM personal_trazabilidad WHERE per_id IN (SELECT per_id FROM personal WHERE per_documento IN ('9999999999','0999999911','0912345000'));
DELETE FROM consumo WHERE per_id IN (SELECT per_id FROM personal WHERE per_documento IN ('9999999999','0999999911','0912345000'));
DELETE FROM personal WHERE per_documento IN ('9999999999','0999999911','0912345000');
"
```
(Ajustar la lista de cédulas de prueba según las que se hayan usado realmente en cada Task.)

- [ ] **Step 5: Bump de VERSION antes del push (regla de `CLAUDE.md`)**

Abrir `VERSION`, incrementar el número menor (ej. `1.4` → `1.5`), incluirlo en el commit final:

```bash
git add VERSION
git commit -m "CU-01: bump VERSION antes de push a producción"
```

---

## Fuera de alcance (recordatorio, ver spec)

- No se toca `convenio/view.php` / `ajax/convenio/convenio.php` (código muerto, sin ruta).
- No se toca el árbol `shared/` (copia vieja sin usar).
- No se migran convenios existentes a modo `marca` automáticamente.
- No se permite mezclar ambos modos en un mismo convenio.
- No se construye carga masiva de *clientes/convenios* — solo existía y se extiende la de *personal*.
