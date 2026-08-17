-- ═══════════════════════════════════════════════════════════════════════════
-- API externa de Gift Cards
--
-- Permite que un cliente/empresa cree solicitudes de lotes desde su propio
-- sistema, sin entrar al portal web. Las solicitudes entran como PENDING igual
-- que las del modal "Crear Nuevos Lotes", así que siguen pasando por
-- "Aprobaciones Pendientes" — la API no aprueba nada.
--
-- Se ejecuta de una sola vez, en el orden en que está escrito.
-- ═══════════════════════════════════════════════════════════════════════════


-- ── Credenciales ───────────────────────────────────────────────────────────
-- Por qué una tabla propia y no reutilizar usuario/password: el login del
-- portal es por sesión y MD5; una integración máquina-a-máquina necesita un
-- secreto largo, hasheado, revocable y con un cliente fijo asociado. El cli_id
-- sale SIEMPRE de la credencial, nunca de la petición: así un cliente externo
-- no puede pedir lotes a nombre de otro.
--
-- La credencial NO guarda un usuario: todas comparten el usuario de servicio
-- que se crea al final de este archivo. Ver allí el porqué.

CREATE TABLE `api_credencial` (
  `api_id`         INT          NOT NULL AUTO_INCREMENT,
  `api_key`        VARCHAR(64)  NOT NULL COMMENT 'Identificador público, va en el token',
  `api_secret`     VARCHAR(255) NOT NULL COMMENT 'Hash del secreto (password_hash). NUNCA en claro',
  `cli_id`         INT          NOT NULL COMMENT 'FK cliente: acota todo lo que la credencial puede ver/crear',
  `api_estado`     VARCHAR(20)  NOT NULL DEFAULT 'activo' COMMENT 'activo | revocado',
  `api_creado`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `api_ultimo_uso` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`api_id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_api_cliente` (`cli_id`),
  CONSTRAINT `fk_api_cliente` FOREIGN KEY (`cli_id`) REFERENCES `cliente` (`cli_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Credenciales de la API externa de Gift Cards';


-- ── Auditoría de peticiones ────────────────────────────────────────────────
-- Sirve para dos cosas: dejar rastro (los códigos de Gift Card son dinero al
-- portador, hay que poder responder "quién descargó qué y cuándo") y sostener
-- el rate limiting, que cuenta las filas de los últimos N segundos. Por eso el
-- índice es (api_id, ape_fecha) y no solo la fecha.

CREATE TABLE `api_peticion` (
  `ape_id`          BIGINT       NOT NULL AUTO_INCREMENT,
  `api_id`          INT          DEFAULT NULL COMMENT 'NULL si la credencial no se pudo resolver (token inválido)',
  `ape_metodo`      VARCHAR(10)  NOT NULL,
  `ape_ruta`        VARCHAR(255) NOT NULL,
  `ape_estado_http` SMALLINT     NOT NULL,
  `ape_ip`          VARCHAR(45)  DEFAULT NULL,
  `ape_fecha`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ape_id`),
  KEY `idx_ape_api_fecha` (`api_id`, `ape_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auditoría de llamadas a la API externa';


-- ── Usuario de servicio ────────────────────────────────────────────────────
-- giftcard_solicitud.id_user es NOT NULL con FK a usuario, y las seis
-- consultas de la cola de aprobaciones hacen JOIN usuario. Una solicitud sin
-- usuario válido se insertaría, pero desaparecería de la cola sin error: no
-- habría forma de aprobarla. Así que la API necesita un usuario.
--
-- Uno solo para toda la API, no uno por cliente: la columna "solicitante" de
-- la cola es informativa, mientras que el cliente real se resuelve por
-- s.cli_id (la API siempre lo rellena), así que un usuario por empresa solo
-- ensuciaría la tabla usuario sin aportar nada que no se sepa ya.
--
-- No se reutiliza un Super Admin a propósito: es quien APRUEBA. Usarlo como
-- solicitante haría que el historial dijera "admin pidió, admin aprobó" sobre
-- lotes que en realidad pidió un cliente externo.
--
-- password no es un hash: login-check.php compara contra un MD5 (32 caracteres
-- hex), así que este valor no puede coincidir nunca. Se deja legible para que
-- quien mire la fila entienda al instante que no es una cuenta de acceso.
-- status 'bloqueado' lo mantiene además fuera del selector de cambio de
-- contraseña, que filtra por status='activo'.
-- cli_id NULL porque la credencial aporta el cliente en cada petición.

-- Las dos sentencias siguientes son re-ejecutables y NO dependen de que se
-- lancen en la misma sesión. Es deliberado: con LAST_INSERT_ID() bastaba con
-- ejecutarlas por separado en phpMyAdmin para que el enlace saliera mal, y el
-- fallo no se vería hasta el primer POST /lotes de un cliente.
--
-- name_user sin tildes a proposito: la tabla usuario es utf8mb3 y segun como
-- se ejecute la migracion (phpMyAdmin, cliente mysql) los bytes UTF-8 pueden
-- guardarse como latin1 y verse "IntegraciÃ³n API" en pantalla. En ASCII puro
-- no puede pasar.
INSERT INTO `usuario` (`username`, `password`, `name_user`, `permisos_acceso`, `status`, `cli_id`, `per_id`)
SELECT 'api_sistema', '*SIN-LOGIN*', 'Integracion API', 'api', 'bloqueado', NULL, NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `usuario` WHERE `username` = 'api_sistema');

-- El id se resuelve por username y no con LAST_INSERT_ID(). Buscar por nombre
-- es ambiguo en esta base (username no es único: hay dos 'admin'), pero aquí
-- se resuelve UNA sola vez y el resultado queda fijado en configuracion, que
-- sí tiene PRIMARY KEY: el runtime nunca vuelve a mirar el username. MIN()
-- garantiza un resultado determinista aunque existieran duplicados.
INSERT INTO `configuracion` (`cfg_clave`, `cfg_valor`, `cfg_descripcion`)
SELECT 'api_id_user', MIN(`id_user`),
       'Usuario de servicio con el que la API externa registra las solicitudes'
FROM `usuario` WHERE `username` = 'api_sistema'
ON DUPLICATE KEY UPDATE `cfg_valor` = VALUES(`cfg_valor`);
