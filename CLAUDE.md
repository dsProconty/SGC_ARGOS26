# SGC ARGOS — Notas para Claude Code

## Regla obligatoria: incrementar VERSION en cada push a producción

El archivo `VERSION` en la raíz contiene un número (`1.1`, `1.2`, ...) que se
muestra como una marca de agua pequeña en la esquina inferior izquierda de
`index.php` (login) y `main.php` (app), para que el usuario pueda confirmar
a simple vista que un despliegue llegó a producción sin tener que probar
ninguna funcionalidad.

**Antes de cada `git push` a una rama de despliegue** (`claude/session-*`,
`nuevas-funcionalidades-v2`, `feature/nuevas-funcionalidades`), incrementa
el número menor en `VERSION` (`1.1` → `1.2` → `1.3` ...) e inclúyelo en el
mismo commit que el resto de los cambios. No se salta nunca, incluso para
cambios triviales — es la única forma de que la marca de versión sirva como
señal confiable de que el deploy ocurrió.

## Recordatorio obligatorio: al pasar a producción real

Mientras el proyecto esté en preproducción, el envío real de correos de
Estado de Cuenta queda bloqueado por el interruptor `configuracion.cfg_clave
= 'modo_preproduccion'` (ver `services/estado_cuenta_service.php` →
`ec_modo_preproduccion()`), y el cron de HostPapa
(`cron/enviar_estados_cuenta.php`, cPanel > Cron Jobs) puede estar
deshabilitado.

**Cuando el usuario confirme que ya van a operar con datos y correos reales
de producción (no más pruebas/preproducción), SIEMPRE recordarle, sin que
tenga que pedirlo:**
1. Volver a habilitar el cron en cPanel si lo habían deshabilitado.
2. `UPDATE configuracion SET cfg_valor='0' WHERE cfg_clave='modo_preproduccion';`
   en la base real — mientras siga en `1`, ningún camino de envío (botón
   manual, cron, piggyback en `content.php`) manda un correo real, sin
   importar qué tan bien configurado esté el SMTP.

## Migración desde el sistema viejo (Joomla/Argos) — estado y plan

Se está migrando datos reales desde un dump viejo (Joomla + extensión
"Argos", ~150MB, tablas `gvony_ads_*`) hacia el esquema nuevo, por
prioridades, depurando duplicados y basura en cada paso — no es un dump
directo. Bases de trabajo locales (XAMPP, no versionadas): `sgc_argos_viejo`
(dump viejo importado tal cual, para consultarlo) y `sgc_argos_prioridad1`
(espejo de lo ya migrado a producción, para probar scripts antes de
correrlos ahí). Los `.sql` generados para cada tanda viven en
`_dump_viejo/` (no versionado — son para correr manualmente por phpMyAdmin
contra la base real de HostPapa, no para pushear).

**Prioridad 1 — YA HECHO Y EN PRODUCCIÓN:**
- `marca`, `local`, `cliente` (187 convenios `published=1` de la base
  vieja), `personal` migrados y deduplicados (la base vieja tenía masiva
  duplicación por re-cargas de nómina sin control de duplicados — ver
  historial de esta conversación para el detalle del hallazgo).
- Decisión del cliente: no se maneja más un estado "inactivo" visible —
  solo `activo` y `bloqueado`. Los antiguos `personal` inactivos sin
  historial de consumo viejo se eliminaron definitivamente; los que sí
  tenían historial (1.876 personas, ~$8.201 en consumos viejos) se
  conservan con `per_estado = 'archivado'` — invisibles en toda la app
  (Personal, POS, Portal Empresa, Reportes) pero presentes en la BD para
  no romper la suma de futuros estados de cuenta históricos por convenio.
  Ver filtros `AND per_estado != 'archivado'` en `ajax/clientes/clientes.php`,
  `ajax/pos/pos.php`, `ajax/portal_empresa/portal_empresa.php`,
  `pages/reportes/excel.php`.
- Tabs Activos/Bloqueados con contador en el tab Personal de la ficha del
  cliente, filtrando en el navegador sobre los datos ya cargados.

**Prioridad 2 — HECHA Y VERIFICADA EN PRODUCCIÓN (9 de septiembre de 2026):**
- Se migró **todo** el rango 2016-2026 sin recortar (decisión del cliente,
  el volumen resultante es chico, ver depuración abajo).
- Histórico de consumos/autorizaciones (`gvony_ads_company_card_authorization`,
  125.730 filas viejas) → tabla `consumo`: **95.212 filas, $2.330.871,99**.
  Depuración real (no es igual a la de `personal`): solo 26 duplicados
  exactos; `cancel_state` NO significa "anulado" (significa "ya pasó por
  un cierre mensual viejo", se migra igual); las anulaciones reales
  (`reverse_tran=1`, monto negativo) se migran ambos lados del par, se
  cancelan solos al sumar. `per_id` resuelto por cédula en 2 pasadas
  (tarjeta actual, y para el resto, historial completo de tarjetas
  reemitidas en `card_detail`) — **importante**: en el script final el
  `per_id` de cada fila se resuelve con una subconsulta por `per_documento`
  en el momento de importar, nunca como número fijo — el auto_increment de
  `personal` avanza distinto en cada entorno (local vs. producción real
  con actividad propia), un `per_id` fijo calculado en local rompe en
  producción. Mismo cuidado aplica a cualquier futuro script que necesite
  referenciar filas por su id autogenerado en otra base.
- Al armar esta migración se descubrió que el chequeo original de
  Prioridad 1 (¿quién archivar vs. eliminar?) solo miraba la tarjeta MÁS
  RECIENTE de cada persona, no su historial completo — **5.919 personas
  se habían eliminado por error** (tenían consumo real en una tarjeta
  anterior reemitida). Se corrigieron reinsertándolas como `archivado`.
  Total archivados: **7.795** (1.876 originales + 5.919 recuperados).
- Gift cards emitidas en el sistema viejo (`gvony_ads_giftcard`) →
  `codigo_gift_card`: **30.621 códigos** (excluidos 156 placeholders
  basura tipo `----||||`/`0` y 6 duplicados). Estado (`cgc_estado`)
  calculado por saldo real, no por el campo viejo `consumo` (no era
  confiable). 52 lotes sintéticos (agrupados por marca+fecha) atribuidos a
  una cuenta dedicada `sistemamigracion` ("Sistema Migración"), creada
  bloqueada — la tabla vieja no tiene ningún campo de responsable/usuario.
  `activo`=4.840 ($148.968,13), `vencido`=13.269 ($122.440,38),
  `consumido`=12.512 ($0).
  **CORRECCIÓN (14-oct-2026): lo anterior de "no hay lote/cliente" era falso.** El viejo sí guarda
  el lote real: `gvony_ads_giftcard.consumercard_id` → `gvony_ads_consumercard` (fecha, factura,
  cantidad, valor) → `gvony_ads_consumercard_beneficiary` (cliente: nombre y RUC). 16.725 de los 30.621
  códigos migrados tienen lote (389 lotes, 47 clientes; ej. SODETUR, JULIECOM, Diners Club); los otros
  13.896 (4.722 de ellos activos, venta al público de Pizza Hut/Fridays) nunca tuvieron lote ni cliente.
  `_dump_viejo/14_reorganizar_lotes_giftcard.sql` crea los 389 lotes reales con su cliente (por RUC si
  coincide con UN solo cliente, si no por nombre, si no lo crea tipo Gift Card), mueve ahí sus códigos y
  deja los lotes sintéticos a nombre de los clientes comodín "VENTA AL PÚBLICO — PIZZA HUT / — FRIDAYS"
  (para poder buscarlos en la pantalla de lotes). Solo INSERT/UPDATE, repetible, no toca saldos ni estados.
  Ensayado en `sgc_ens_gc` (copia local): 389 lotes, 16.725 + 13.896 códigos, 4.840 activos / $148.968,13
  iguales que antes. Nota: el nombre de los beneficiarios viejos está en latin1 (generar con `set_charset('utf8mb4')`).
- Scripts en `_dump_viejo/`: `04_correccion_archivados_faltantes.sql`,
  `05_migrar_giftcards.sql`, `06_migrar_consumos.sql` (todos ya corridos).
- Cierres de cuenta históricos (`gvony_ads_company_account_close`) → NO
  migrados, no calzan 1:1 con el `estado_cuenta` nuevo, sin definir con
  el cliente si migran igual como referencia.

**Correcciones post-migración (24 de septiembre de 2026), detectadas por el
sponsor de Argos al notar personal real de convenios activos escondido:**
- El chequeo original de Prioridad 1 que determinaba `activo`/`bloqueado`
  en la migración tenía más de un problema, además del ya documentado
  arriba (5.919 eliminados por error). Casos reales encontrados:
  - **747 personas** cuya última acción real en el sistema viejo
    (`gvony_ads_company_card_block`, `type_reg='B'`) era "Bloqueado", no
    "inactivo" — quedaron `archivado` (invisibles) en vez de `bloqueado`
    (visibles). Corregido con `_dump_viejo/07_correccion_bloqueados_mal_archivados.sql`.
  - **216 personas** con una transacción real en el sistema viejo durante
    2025 o 2026 (hasta 6 meses antes del corte de migración) que igual
    quedaron `archivado` sin razón identificable en ningún campo del dump
    viejo (tarjeta publicada, empresa activa, sin bloqueo). Corregidas a
    `activo` con `_dump_viejo/08_correccion_archivados_con_actividad_reciente.sql`.
    Caso disparador: convenio ARGOS PUBLICIDAD S.C.C. (`cli_id=99`), donde
    los 14 empleados habían quedado `archivado`/sin revisar.
  - **482 casos** con última acción "R" (observación siempre "CIERRE") se
    evaluaron y se dejaron como `archivado` — parece un cierre real de
    tarjeta/convenio, no un error de clasificación.
  - **43 casos** con última acción "A" (reactivado) siguen `archivado` sin
    revisar — pendiente, es la anomalía más sospechosa que queda sin tocar.
  - El resto de los archivados sin actividad desde 2023 o antes (~6.831)
    se dejó como está — no se hizo bulk-reclasificación, ver tab
    "Archivados" abajo.
  - Se detectaron además **23 convenios con el 100% de su personal
    archivado** (0 visibles). De esos, 20 (~1.507 personas) no tienen
    actividad desde 2023 o antes — probablemente cierres reales. 3
    convenios (ARCACONTINENTAL, SINDICATO HOSPITAL EUGENIO ESPEJO,
    GARLANDS — 44 personas) sí tienen compras reales en 2024, un poco por
    debajo del corte de 2025 usado arriba. **Decisión explícita del
    cliente: NO reactivarlos.** Criterio: prefiere que alguien archivado
    se acerque a comprar y no pueda (se resuelve al momento reactivándolo
    manualmente) a reactivar de más y que la persona consuma sin que haya
    forma de cobrarle al convenio después. Ante esta duda (activar de más
    vs. dejar archivado de más), el default del cliente es **quedarse
    archivado** — aplicar el mismo criterio si aparecen casos similares.
- **Nueva pestaña "Archivados"** en el tab Personal de la ficha del
  cliente (`pages/clientes/view.php`, junto a Activos/Bloqueados), con
  botón "Reactivar" por persona (pasa a `activo`, usa el mismo endpoint
  `personal_cambiar_estado` y el mismo modal de confirmación que
  Bloquear/Activar). Decisión explícita del cliente: en vez de seguir
  hacienda scripts SQL para cada corte de recencia, cualquier archivado
  ahora se puede revisar y reactivar manualmente desde la UI si se
  confirma que sigue siendo una persona real y activa. El endpoint
  `personal_list` dejó de excluir `archivado` (antes lo excluía); el
  contador de la pestaña "Personal" y el contador "N emp." del listado de
  convenios (`ajax/clientes/clientes.php`, `total_personal`) siguen
  contando solo activo+bloqueado, no archivado.

**Descartado / pendiente de confirmar con el cliente, no urgente:**
`gvony_ads_discount_card` (sin actividad desde 2021), `credit_businesscard`,
`clients_order`/`product*` (e-commerce viejo, sin equivalente en el
esquema nuevo), `gvony_ads_users` (data de prueba, no real).
Falta también resolver: `cli_tipo_cartera` no tiene de dónde migrarse.
(El RUC ya está resuelto: ver "Tanda del 12 de octubre" más abajo.)

## Corte de datos: 2026-09-09 — todo lo migrado es real, todo lo posterior es prueba

El **9 de septiembre de 2026** se dio por completa y verificada la migración
del sistema viejo (Prioridad 1 + Prioridad 2, ver sección de abajo): 187
convenios, ~25.582 `personal` (activo + archivado), 95.212 `consumo`
históricos, 30.621 `codigo_gift_card`. Esos son datos **reales** del
cliente y no se deben tocar, mezclar con pruebas, ni usar como base para
generar más datos ficticios.

**A partir de esa fecha, cualquier registro nuevo que se cree en la base
real (`cliente`, `personal`, `consumo`, `giftcard_solicitud`, `usuario`,
etc.) mientras el sistema siga en preproducción debe ser obviamente
**dummy/de prueba** — nombres, cédulas, montos y convenios que no
correspondan a personas o empresas reales, y que se puedan identificar a
simple vista como ficticios (ej. "EMPRESA DE PRUEBA QA", cédulas tipo
`0000000001`).** No crear registros de prueba que parezcan reales o que
puedan confundirse con datos migrados.

**Por qué:** cuando se pase a producción real, va a hacer falta poder
diferenciar sin ambigüedad "esto vino de la migración" de "esto se creó
mientras probábamos" — para poder limpiar todo lo segundo antes del
lanzamiento real sin arriesgar borrar datos migrados por error. Si Claude
crea datos de prueba en la base real como parte de una tarea (cuentas de
prueba desechables, empleados de prueba para QA, etc.), debe dejarlos
marcados de forma obviamente ficticia por este mismo motivo, no solo
borrarlos al final de la sesión — si algo queda sin borrar por error, tiene
que notarse igual que es basura de prueba.

## Prioridad 3 — Usuarios (cajeros/supervisores por local), en curso

Reunión del sponsor (25 de septiembre de 2026, Elbany) reveló más pendientes
además de personal/consumos, ya resueltos por separado en este mismo
archivo:

- **Login soporta dos formatos de contraseña** (`login-check.php`): las
  cuentas nuevas siguen en MD5 sin sal (comportamiento de siempre); las
  migradas del sistema viejo guardan su hash bcrypt original (`$2y$`/`$2a$`/
  `$2b$`) tal cual, sin resetear nada — se detecta el formato por el
  prefijo del hash guardado y se valida con `password_verify()` en ese
  caso. Así cada persona migrada entra con la misma clave de siempre. Los
  usuarios reales del sistema viejo NO están en `gvony_ads_users` (esa
  tiene 1 fila de prueba) — están en `gvony_users`, la tabla nativa de
  Joomla extendida con columnas propias (`local_id`, `documentNumber`,
  etc.). `gvony_users.local_id` mapea 1:1 exacto a `local.loc_id` ya
  migrado (verificado), a diferencia de `personal.per_id` que no es
  portable.
- `_dump_viejo/09_migrar_usuarios_cajeros.sql`: 1.387 cuentas de
  cajero/supervisor por local (`local_id` presente en el dump viejo),
  preservando el hash de contraseña tal cual y el estado `block` (1.239
  activo / 148 bloqueado) — sin filtrar por recencia de acceso, a
  diferencia de `personal`: una cuenta de login dormida no tiene el mismo
  riesgo (cobranza) que un empleado oculto, así que no hace falta
  inventar un criterio de actividad aquí. Todas nacen con
  `permisos_acceso='Cajero'`; el sistema viejo no distingue cajero de
  supervisor en un campo propio (está mezclado en el nombre de la
  cuenta) — se decidió no adivinar por texto, Elbany asciende a los
  supervisores puntuales desde Perfiles y Permisos después.
- **134 cuentas de staff interno de Argos y "control de ventas" por
  marca** (sin `local_id`) quedaron fuera de ese script a propósito —
  entregadas en `_dump_viejo/09b_usuarios_staff_para_revisar.csv` para
  que Diego/Elbany decidan cuáles migrar y con qué rol.
- Tabla `usuario` en Usuarios ahora separa **Marca** de **Asignación**
  (antes venían combinadas en una sola celda) — `ajax/users/users.php`.
- `usuario` nunca tuvo columna de cédula/documento — nadie podía buscar a
  un cajero por cédula (caso real: MERCY ALAY, doc `0922100698`, migrada
  como `maalay01` pero invisible al buscar por su cédula). Se agregó
  `usuario.documento` (`migrations/bloque20_documento_usuario.sql`) y se
  rellenó para 1.154 de las 1.387 cuentas migradas con
  `_dump_viejo/10_backfill_documento_cajeros.sql` (las otras 233 no
  traían cédula en el sistema viejo, quedan `NULL`). La columna
  "Documento" ya es visible en la tabla y el buscador de DataTables la
  encuentra solo, sin cambios adicionales de JS.

- **"Reporte Pendiente Empresas" nunca va a mostrar meses anteriores al
  actual, y no es un bug ni un bloqueo de preproducción** (Diego lo
  explicó mal en vivo durante la reunión). Ese reporte lee de `cartera`,
  que `services/load_data.php` (`case 'carga_cartera'`) reescribe en
  cada login como un snapshot rotativo de deuda por antigüedad (30/60/90/
  90+ días) con `car_fecha_ingreso` siempre fechado "hoy" — nunca se
  acumula histórico ahí. Se agregó un reporte nuevo, **"Consumo Mensual
  por Empresa"** (`tipo=consumo mensual empresas`, en `pages/reportes/
  excel.php`, `pages/reportes/view.php` y `shared/sidebar.php`), que sí
  lee directo de `consumo` y por lo tanto sí muestra los meses viejos
  reales. Cualquier pedido futuro de "por qué este reporte no muestra
  meses anteriores" revisar primero si lee de `cartera` o de `consumo`.

**Otros dos hallazgos de la misma reunión, ya corregidos:**
- `ajax/gestiones/gestiones.php` interpolaba `$_GET`/`$_POST` sin escapar
  en varios `case` (inyección SQL real) — corregido casteando a `(int)`
  los IDs y con `mysqli_real_escape_string()` los strings, sin cambiar el
  comportamiento.
- El reporte "Ventas por Locales (Liquidación)" no calculaba la comisión
  de Argos (el sistema viejo sí). Se agregó `marca.mar_comision` (default
  12.5%, "Vaco y Vaca" en 10% — `migrations/bloque19_comision_por_marca.sql`)
  y las filas de Comisión/IVA Comisión/Total Factura a la Marca en
  `pages/reportes/excel.php` (usa el % de IVA configurado del sistema, no
  uno fijo).

**`ADD COLUMN IF NOT EXISTS` no es válido en el MySQL/MariaDB de
producción (error 1064) — nunca usarlo.** Ya se había descubierto una vez
en julio (commits `e1319a6`/`a6a4ba4`, `usuario.session_version` y
`pago.pag_estado`) y volvió a aparecer en `ajax/gestiones/gestiones.php` y
`ajax/users/users.php` (7 sitios) porque la migración "perezosa" ahí
copiaba el mismo patrón. Para cualquier columna que deba crearse sola en
el primer request que la necesite, usar
`agregarColumnaSiNoExiste($mysqli, $tabla, $columna, $definicionSql)` de
`helpers/db_helpers.php` — chequea `information_schema` en vez de
depender de la sintaxis `IF NOT EXISTS`. Importante: en local (PHP más
nuevo) mysqli lanza excepción ante un "Duplicate column name", mientras
que en producción (PHP < 7.1) `mysqli_report` está OFF y la falla queda
silenciosa — un ALTER TABLE repetido sin este helper puede pasar
desapercibido en producción pero tumbar la página entera en local.

**Dos gotchas de QA (Nico, 29 de septiembre de 2026):**
- `pages/reportes/excel.php` — todo literal con tilde/ñ que se imprima en un
  reporte debe ir envuelto en `utf8_decode('...')` (el resto del archivo ya
  lo hace); sin eso Excel muestra `COMISIÃ“N`. Las filas de comisión de
  "Ventas por Locales (Liquidación)" se habían escrito sin él.
- `main.php` tiene un `MutationObserver` que borra `.modal-backdrop`
  "huérfanos". Su espera era 50ms, menos que la animación de Bootstrap 4
  (inserta el backdrop primero, marca el modal `.show` ~150-300ms después),
  así que borraba backdrops legítimos y los popups quedaban sin fondo oscuro
  ni `body.modal-open` (caso visible: Editar en Perfiles y Permisos). Ahora
  espera 500ms. Si vuelve a pasar en otro modal, sospechar de este observer
  antes que del modal.

**Impresión de vouchers (12 de octubre de 2026):** los locales usan dos
impresoras de ticket, Epson **TM-T88VII** (térmica, 80 mm) y Epson
**TM-U220** (matricial de impacto, 76 mm, ~63 mm imprimibles, solo
blanco y negro). Toda la impresión vive en `js/voucher_print.js`
(`voucherImprimir()`), compartida por el POS (`pages/pos/view.php`) y la
reimpresión de `pages/pos/historial.php` (antes tenía su propia versión
vieja, sin tamaño de página). Es **un solo layout de 60 mm** que entra en
las dos impresoras, sin fijar tamaño de `@page` (usa el papel del driver);
el modal del voucher NO lleva selector de impresora (Diego lo descartó).
Validado solo en pantalla: se renderizó el voucher real con datos de peor
caso (empresa/empleado de 70+ caracteres) en iframes de 63.5 y 72 mm sin
desbordes. Las etiquetas (primera columna) no deben partirse
(`td:first-child { white-space: nowrap }`) y las rayas de firma están
acortadas para no pasarse de ~35 columnas. Pendiente: prueba física en
ambas impresoras.

**Tanda del 12 de octubre de 2026:**
- **RUC en `cliente`** (`cliente.cli_ruc VARCHAR(20)`,
  `migrations/bloque22_ruc_cliente.sql`). `cli_id` coincide 1:1 con
  `gvony_ads_company.id` (187/187 por id y nombre). `_dump_viejo/11_backfill_ruc_clientes.sql`
  migra solo valores limpios: **169** (162 RUC de 13 dígitos + 7 cédulas de
  10), tras quitar espacios y un `_` sobrante. **13 quedaron fuera** y están
  en `_dump_viejo/11b_ruc_para_revisar.csv` (`-`, `DELLTEX`, `111111111111111111`,
  `1234567890009`, `1111111111113`, `..._old`, y valores de 12 y 14 dígitos);
  5 convenios no tenían RUC en el sistema viejo. Hay RUC repetidos a propósito
  (las 4 empresas Argos comparten `1792932297001`). El campo se edita en
  Clientes (modal + ficha) y el servidor exige 13 dígitos (o 10 si es
  cédula), solo números.
  **Decisión (12-oct-2026, Diego/Elbany): esos 18 convenios se quedan con
  `cli_ruc` vacío, no se persigue el dato.** Elbany: sin RUC = convenios muy
  viejos/inactivos en el sistema viejo, sin trabajo vigente. (Dato: 11 tienen
  `company_state=0` y última compra 2017-2023; 7 siguen en `company_state=1`
  pero sin compras desde 2018-2023, entre ellos ADEM con 443 activos.) Si el
  RUC falta, se carga a mano desde Editar cliente cuando algún convenio lo pida.
- **Formulario de usuarios** ahora captura `usuario.documento`
  (`pages/user/form_user.php` + `proses.php`, alta y edición). Editar sin
  escribir contraseña NO toca el hash (importante: las cuentas migradas
  tienen bcrypt).
- **Textos rotos en Perfiles** (`n├│mina`, `m├│dulo`, `d├¡a`): estaban
  guardados así en la base real (ya venían en el export del 3-sep-2026 y en
  `database/setup.sql`), no era un problema de pantalla. UTF-8 leído como
  CP437/CP850 y guardado de nuevo. `migrations/bloque21_fix_perfil_textos.sql`
  lo repara (ASCII puro con `UNHEX`, idempotente); `database/setup.sql` ya
  está corregido para que una reinstalación no lo reintroduzca. Los datos
  migrados (nombres con ñ/tildes) están bien en UTF-8; lo que se ve como `�`
  en la consola de Windows es solo cómo la consola dibuja los bytes.
- **Voucher**: el contenido del ticket se arma en un único lugar,
  `voucherHtml()` de `js/voucher_print.js`, usado por el POS y por la
  reimpresión de `pages/pos/historial.php`. Antes el Historial tenía una
  copia vieja sin IVA/combo/gift card que mostraba "null" en ventas con gift
  card.

**La base de producción es MySQL 8.x (no MariaDB)** — lo confirma el aviso
`#3719 'utf8' is currently an alias for UTF8MB3` que phpMyAdmin mostró al
correr `bloque21`. Por eso `ADD COLUMN IF NOT EXISTS` no existe ahí (es de
MariaDB). En scripts SQL usar `utf8mb4` (no `utf8`) en `CONVERT(... USING ...)`
para no llenar la pantalla de avisos. El local sí es MariaDB 10.4, así que
algo que corre en local puede fallar en producción por sintaxis.

**Cupo por marca "igual al sistema viejo" (13 de octubre de 2026) — PILOTO en curso.**
Hallazgo del sponsor (Elbany): en el sistema viejo cada persona tiene UNA TARJETA POR
MARCA (`gvony_ads_company_card_detail` → `gvony_ads_company_card.mark`), cada una con su
propio número, cupo (`card_value`), estado (`card_state`: 1 = ACTIVA, 0 = BLOQUEADA,
verificado contra la pantalla vieja) y saldo. La migración había colapsado eso en un solo
cupo global (los 187 convenios quedaron `cli_modo_cupo=global`, `personal_cupo_marca`
vacía). En la base vieja: 138 de 170 convenios con tarjetas son multi-marca y ~37.400
personas tienen tarjeta en más de una marca. **El sistema viejo NO tiene campo "global o por
marca" por convenio** — todo era por tarjeta —, así que copiar tarjeta por tarjeta reproduce
el comportamiento sin que nadie decida el modo; el modo `marca` ya cubre convenios "mixtos"
(montos distintos por persona), no hace falta un tercer modo. (`_dump_viejo/convenios_modo_cupo.xlsx`
clasifica los convenios A/B/C/D solo como ayuda: es inferencia, no dato del sistema viejo.)
- `migrations/bloque23_cupo_marca_estado_tarjeta.sql`: `personal_cupo_marca.pcm_estado`
  (activo|bloqueado) y `pcm_tarjeta`. El código las crea solo si faltan
  (`cupoAsegurarColumnasEstado()` en `helpers/cupo_marca_helpers.php`).
- POS (`ajax/pos/pos.php`, `buscar` y `registrar`): una marca con `pcm_estado=bloqueado`
  no deja cobrar (la persona puede estar activa en otra marca).
- Clientes > Personal: si el convenio es modo `marca`, las columnas de cupo muestran "por marca /
  Ver detalle" y hay un botón que abre el modal "Cupo por marca" (marca, tarjeta enmascarada,
  estado, cupo, saldo — `personal_cupo_marca` endpoint `personal_cupo_marca`).
- Datos: `_dump_viejo/12_piloto_cupo_marca_argos.sql` (ARGOS PUBLICIDAD, `cli_id=99`; 298
  tarjetas, 161 aplican a las 14 personas migradas), generado con un script que toma la tarjeta
  MÁS RECIENTE (mayor `id`) por persona+marca, resuelve la persona por `per_documento` y la marca
  por nombre (no por id), pone el tope del convenio por marca = mayor cupo de esa marca, y deja
  **saldo = cupo** (opción acordada: el consumo nuevo se descuenta desde cero; pendiente
  confirmar con el sponsor si el cupo se renueva por corte). No toca `per_estado`. Idempotente.
  Ensayado en el espejo y en una copia de la BD de la app: coincide 1 a 1 con la captura de Elbany
  (14 marcas, solo Pizza Hut ACTIVA, cupos 120/50/50/30/100...). Falta: correrlo en producción
  y, si Elbany valida, repetirlo para el resto de convenios multi-marca.

**Bloqueos en cuatro niveles (13 de octubre de 2026).** El POS (`ajax/pos/pos.php`, `buscar` y
`registrar`) solo deja comprar si TODOS están activos, y cada nivel es independiente y reversible
(`migrations/bloque24_bloqueos_convenio_marca.sql`; el código también crea las columnas solo,
`cupoAsegurarColumnasEstado()` en `helpers/cupo_marca_helpers.php`):
- **Convenio** — `cliente.cli_estado`. Botón "Estado y marcas" en la ficha del cliente
  (`pages/clientes/view.php`, modal `modalEstadoConvenio`). NO toca el estado de ninguna persona.
- **Convenio y marca** — `cliente_cupo_marca.ccm_estado`. Mismo modal, lista de marcas con casillas.
  `cupoGuardarMaximosPorMarca()` conserva el estado al editar topes (borra y reinserta filas).
- **Persona** — `personal.per_estado` (ya existía, candado de la fila).
- **Persona y marca** — `personal_cupo_marca.pcm_estado`. Modal "Cupo por marca" con casillas
  (bloquear/activar seleccionadas; la casilla maestra = todas).
- Los dos niveles de convenio exigen el permiso granular **`clientes.bloquear`** (catálogo en
  `pages/perfiles/view.php`; Super Admin siempre pasa); los de persona no piden permiso extra (igual
  que el candado existente). Auditoría: `personal_trazabilidad` (persona) y la tabla nueva
  `cliente_trazabilidad` (convenio y marcas del convenio), visible como "Historial reciente" en el modal.

**Cupo por marca para el resto de convenios (13 de octubre de 2026) — HECHO en producción.**
Mismo método del piloto (ARGOS PUBLICIDAD, ya en producción y validado por Elbany), aplicado a los
convenios multi-marca restantes: `_dump_viejo/13_cupo_marca_resto_parte{1..5}_de_5.sql` (generados por
un script PHP desde `sgc_argos_viejo`; tablas de trabajo `tmp_personal_migrado` y `tmp_tarjeta_ult`
en esa base). **124 convenios, 268.578 filas persona×marca**, repartidas en 5 partes (~2-3 MB, un
convenio nunca se parte entre archivos); cada parte pasa sus convenios a `cli_modo_cupo='marca'` al
final. Saldo = cupo; no tocan `per_estado`, cupos globales ni consumos; usan `INSERT IGNORE` (no pisan
filas existentes, se pueden repetir). Los convenios de una sola marca (todos inactivos) se quedan
`global`. Excluido `cli_id=99` (piloto, tiene estados y consumos propios). Ensayado en el espejo:
las 5 partes cargan exactamente las filas esperadas y la comparación contra el viejo da **0
diferencias** en cupo, estado y tarjeta; repetir una parte no duplica. Rollback:
`13z_rollback_modo_global.sql` (vuelve los convenios a global, las filas quedan sin efecto).
**Estado: YA CORRIDO EN PRODUCCIÓN (13-oct-2026)**: las 5 partes + el parche 13e; la comprobación por convenio
(`13d`) dio 0 diferencias, 268.739 filas, 125 convenios en modo marca. **Decisión de Diego: las 128 personas activas
cuyas tarjetas viejas están TODAS bloqueadas se dejan así** (`13b_activos_con_todas_las_tarjetas_bloqueadas.csv`):
quedan bloqueadas en todas las marcas, igual que en el viejo; si alguna debe comprar, se activa desde el modal
"Cupo por marca" de Clientes > Personal.
Para revisar con Elbany (ya resuelto arriba): `13b_activos_con_todas_las_tarjetas_bloqueadas.csv` (128 personas activas
cuyas tarjetas viejas están TODAS bloqueadas — quedarán bloqueadas en todas las marcas, igual que en
el viejo) y `13c_tarjetas_con_marca_inexistente.csv` (6 tarjetas de ARGOS EMPRESARIAL con
`mark_id=0`, sin marca: no se migran). El convenio 45 (UNILEVER, 751 personas) tiene todos los
cupos en 0 en el viejo y ya en el global: no cambia nada y no recibe topes.

**Gotcha: espacios al final en cédulas + MySQL 8 (13 de octubre de 2026).** Algunas cédulas en el
viejo y en `personal.per_documento` traen un espacio al final (`'1793079628001 '`). MariaDB local (colación
PAD SPACE) los ignora al comparar; el MySQL 8 de producción NO. Al cargar el cupo por marca en producción
(partes `13_cupo_marca_resto_*`) 4 filas persona×marca no entraron por eso (convenios 53, 58 y 116); se
cargaron con `_dump_viejo/13e_parche_cedulas_con_espacios.sql`, que compara con `TRIM()` de los dos lados.
**Todo script nuevo que cruce personas por cédula entre local y producción debe usar `TRIM()`**, y una
verificación "ensayado en local = 0 diferencias" no basta: comparar contados contra producción
(`13d_comprobar_diferencias_por_convenio.sql` lo hace por convenio y solo muestra las diferencias).

**Cobranza / cartera (13 de octubre de 2026).**
- **Se eliminó la carga desde el webservice del sistema viejo** (`ibrisystemas.com`, ya no existe). Antes cada
  login pasaba por `services/load_data.php?action=carga_data`, que consultaba ese servicio y podía crear
  clientes, marcas, locales, personas y consumos (probable origen de las marcas basura 17/18/19 en producción).
  Ahora `login-check.php` redirige directo según el rol (`operador` → Gestiones cartera 30, `cajero` → POS,
  `empresa_cliente` → Portal Empresa, el resto → Dashboard; comparación sin distinguir mayúsculas, antes un
  `Cajero` migrado caía en el Dashboard) y `services/load_data.php` quedó solo con `carga_cartera`.
- **`cartera` se alimenta con `services/cartera_service.php` (`cartera_actualizar()`)**, que dispara el cron
  `cron/actualizar_cartera.php` (cPanel > Cron Jobs, `30 5 * * *`, solo CLI) o, a mano, un Super Admin con sesión en
  `services/load_data.php?action=carga_cartera` (antes era una URL pública sin control de sesión que nadie llamaba).
  Suma `consumo` por convenio en ventanas de 30/60/90 días según `cli_dia_corte` (inicio inclusivo, fin exclusivo) y
  +90 (todo lo anterior al inicio de la ventana de 90; `car_fecha_fin` = ese inicio). **Es seguro correrla a diario**:
  misma ventana -> solo refresca el monto y NO toca el estado de gestión; ventana distinta (ciclo de corte nuevo) ->
  vuelve a `sin_gestion`; sin consumos en la ventana no toca nada. Antes cada corrida devolvía todo a `sin_gestion`, el
  UPDATE del +90 guardaba otra fecha (la lista no cuadraba con adentro) y el SELECT del 60 llevaba un `)` suelto.
- **Bug "deuda en $0 adentro, $400 afuera"**: la lista muestra `cartera.cli_valor_pagar` (total de la ventana);
  adentro `action=total` le resta los pagos, y restaba TAMBIÉN los rechazados (al rechazar, la cartera vuelve a
  Pendiente pero el pago rechazado seguía restando → deuda 0 y no dejaba registrar el pago de reemplazo). Ahora
  `ajax/gestiones/gestiones.php` ignora `pag_estado='rechazado'` en `total` y en las columnas de pagado/saldo de
  la lista; pendientes y confirmados sí restan. Un `cli_dia_corte` vacío o no numérico se toma como 1
  (antes reventaba en PHP 8 local).

**Ventas anuladas ya no se cobran (14 de octubre de 2026).** El POS anula una venta SOLO cambiando
`consumo.con_estado` a `'anulado'` (no crea fila negativa; solo las anulaciones migradas del sistema viejo son pares
con monto negativo que se cancelan al sumar), y ninguna suma de dinero filtraba el estado, así que las anuladas
(y las `rechazado`) se facturaban en el estado de cuenta, la cartera, Cobranza, los reportes y el dashboard. Ahora
todas esas consultas llevan `con.con_estado NOT IN ('anulado','rechazado')`: `services/estado_cuenta_service.php`
(5), `services/cartera_service.php`, `ajax/gestiones/gestiones.php` (`total` y `consumos`), `ajax/dashboard/estadisticas.php`
(13), `ajax/dashboard/ventas.php` (4) y `pages/reportes/excel.php` (17). Probado con una venta anulada y una
rechazada: cartera, deuda de adentro, detalle de consumos y estado de cuenta suman solo la válida. **Toda consulta
NUEVA que sume `consumo` debe llevar el mismo filtro.** Las listas que muestran el estado (pestaña Consumos de
Clientes, Portal Empresa, Historial del POS) siguen mostrando las anuladas con su etiqueta, a propósito.
**Botón manual:** Configuración > tarjeta "Cobranza — Cartera" > "Actualizar cartera ahora" (solo Super Admin;
`ajax/configuracion/configuracion.php?action=actualizar_cartera`, POST) para cuando el cron no corra o haya una emergencia. Usa la
misma rutina y un candado `GET_LOCK('sgc_cartera_actualizar')` para que cron y botón no corran a la vez.
La cartera guarda `configuracion.cartera_ultima_corrida` (fecha/hora y origen `cron`|`manual`) y Gestiones la muestra
bajo el título: así se comprueba que el cron de cPanel (`cron/actualizar_cartera.php`) realmente corre.

**Ventas diferidas atadas a una marca (14 de octubre de 2026).** Pedido de Elbany: el estado de cuenta debe
mostrar el valor diferido dentro de la franquicia donde se compró. `venta_diferida.mar_id` (`migrations/bloque25_venta_diferida_marca.sql`;
la app crea la columna sola y las ventas que ya existían quedan en ARGOS HOME). **Toda venta diferida lleva marca; por
defecto ARGOS HOME** (resuelta por nombre, `vdMarcaPorDefecto()` en `helpers/venta_diferida_helpers.php`; los ids no son
portables) y el formulario de Ventas Diferidas trae un selector para elegir otra. Listados (módulo y pestaña del cliente)
muestran la columna Marca. En el estado de cuenta (`services/estado_cuenta_service.php`, `ec_obtener_detalle`) cada cuota
aparece como línea "MARCA — Venta diferida" y se suma en SU columna del cuadro "Resumen por Beneficiario y Marca", así el
cuadro cuadra con el Total venta (antes las diferidas entraban al total pero no al cuadro y no tenían marca). Las ventas
diferidas se registran a mano en su módulo; el POS no las crea. Pendiente/no hecho: incluirlas en los reportes de
liquidación por marca (`pages/reportes/excel.php`), que hoy solo suman consumos.

**Pasada de pruebas completa (14 de octubre de 2026) — hallazgos y reglas nuevas.** Se probó en local cada módulo
con usuarios de prueba por rol (curl + navegador). Lo que salió y quedó corregido:
- **Seguridad: los endpoints ajax se abrían sin sesión o sin chequear el módulo.** `ajax/users`, `ajax/dashboard/*`,
  `ajax/gestiones` y `pages/reportes/excel.php` respondían a cualquiera sin iniciar sesión (lista de usuarios, reasignar
  local —cerraba la sesión de la persona—, cobranza, 29 reportes); `ajax/clientes`, `combos`, `estado_cuenta` y
  `venta_diferida` solo pedían tener sesión (un cajero o una empresa cliente podía crear clientes). Ahora **todo endpoint
  ajax nuevo debe empezar con `exigirAccesoAjax($mysqli, 'modulo')`** (`helpers/session_helpers.php`; acepta un módulo o
  una lista, Super Admin siempre pasa; `tieneModulo()` aplica la misma regla de `content.php`). `ajax/clientes` abre
  `list`/`get` también a quien tenga Gift Card (selector de cliente del lote), excepto al cliente externo.
- **Alta/edición de usuarios (`pages/user/proses.php`) no validaba nada**: un cajero podía crear un Super Admin, se
  permitían usuarios repetidos y nombres que luego no podían entrar. Ahora exige el módulo `usuarios`, solo un Super
  Admin crea/edita cuentas Super Admin, y el usuario es único y de letras/números/`_`/`.`/`-`.
- **Login**: `ctype_alnum` dejaba fuera a **20 cuentas migradas activas** con ñ, espacio o `_` en el usuario (ej.
  `Mcedeño`, `j___sod`). Ahora acepta letras Unicode, números y `_ . -`, y compara con `TRIM(username)`.
- **Cambiar contraseña** comparaba solo contra MD5: las cuentas migradas (bcrypt) nunca podían cambiar su clave.
- **Dashboard**: el filtro de anulados dejó `from.con_estado` en 15 consultas de `ajax/dashboard/ventas.php` y
  `estadisticas.php` (error fatal, los gráficos por marca no cargaban en producción desde la 7.37).
- Gestiones aceptaba pagos en cero o negativos (solo lo validaba el navegador); Configuración aceptaba cualquier valor
  (IVA "abc" o 150) y dejaba pisar las marcas internas; Perfiles permitía dos perfiles con el mismo nombre; en Clientes,
  la persona "suspendida" desde el Portal Empresa no aparecía en ninguna pestaña.
- `.htaccess` ahora bloquea por web `*.sql`, `*.log`, `error_log`, `*.md`, `database/`, `migrations/`, `docs/`, `tools/`,
  `vendor/`, `cron/`. **`database/setup.sql` (seed con `admin`/`supervisor`/`operador1` = md5 de `admin123`) era
  descargable**: confirmar que esas cuentas en producción tengan otra clave o estén bloqueadas.
- **Qué se le cobra a un convenio (decisión del cliente, 15-oct-2026): SOLO lo que salió del cupo de su empleado**
  (`consumo.con_monto_convenio`), no el total de la venta. Una venta mixta (parte con cupo, parte con gift card o pago
  externo) ya trae las otras partes pagadas por otro medio. `helpers/cobro_helpers.php` (`cobroConvenioSql()` =
  `COALESCE(con_monto_convenio, con_valor_total)` y `cobroFraccionSql()`) lo aplica en cartera
  (`services/cartera_service.php`), estado de cuenta (total, detalle, cuadro por marca y saldo acumulado; el detalle muestra
  neto, IVA y total repartidos en esa proporción y `con_valor_venta` trae el total vendido) y Gestiones (`total` y
  `consumos`), y en los reportes por empresa (Cliente + Consumos, Consumos del mes, Consumo Mensual por Empresa). Las
  ventas migradas traen `con_monto_convenio` = total, y las filas viejas con NULL se toman completas. **Toda suma NUEVA que
  represente lo que debe una empresa debe usar el helper, no `con_valor_total`.** Siguen sumando el total vendido (volumen
  por marca/local, no cobranza): dashboard, Ventas por Locales, Total Ventas, Ranking, Liquidación, Comisión Mensual.
- Pendiente menor: la comisión por marca (`marca.mar_comision`) no se puede editar desde ninguna pantalla. Rechazar una
  solicitud de gift card no exige motivo.

**Compatibilidad PHP 7.0 ↔ 8.4 (23 de octubre de 2026).** Producción corre **PHP 7.0** (confirmado en cPanel de HostPapa;
extensiones activas: mysqli, mysqlnd, mbstring, gd, curl, openssl, fileinfo, zip, json, iconv) y las vulnerabilidades
"PHP2Shell" (CVE-2026-109618/109619) no tienen parche oficial para 7.0; el objetivo es migrar a **8.4.27** (corregida,
publicada 21-oct-2026). El código ahora corre igual en las dos versiones (batería completa probada con PHP 7.0.33, 8.1.6 y
8.4.27 portables: 0 avisos, 0 fatales; 53 endpoints, 58 reportes, 17 flujos de escritura, PDF, cron). Reglas:
- **Todo archivo que cargue `config/database.php` (que NO va en git: es propio de cada servidor) debe cargar justo
  después `helpers/db_compat.php`.** Fija `mysqli_report(MYSQLI_REPORT_OFF)` (desde PHP 8.1 mysqli lanza excepciones; el
  código está escrito para "la consulta falla y devuelve false") y define `dbFila($mysqli, $sql)`. **No usar
  `mysqli_fetch_assoc(mysqli_query(...))`** (TypeError en PHP 8 si la consulta falla): usar `dbFila()`.
- **No usar `utf8_decode()`/`utf8_encode()`** (obsoletas desde 8.2, se eliminan en 9): usar `textoLatin1()` / `textoUtf8()`
  de `helpers/texto_helpers.php` (mbstring, con respaldo a las antiguas; null → '').
- **Claves**: `helpers/auth_helpers.php` (`clavesCandidatas()` + `claveCoincide()`) las usan `login-check.php` y
  `pages/password/proses.php`. Desde PHP 8.1 `htmlspecialchars()` convierte también `'` (antes no): el login ya no altera
  la clave antes de compararla, prueba la clave tal cual, la forma antigua (con banderas fijas ENT_COMPAT) y la escapada
  del alta antigua; las claves nuevas se guardan como `md5(trim($clave))`. Un Supervisor ya no puede resetear la clave de
  un Super Admin.
- Antes de cambiar la versión del hosting: probar una copia en un subdominio con PHP 8.4 (la selección por dominio está
  en cPanel; por carpeta no). Otros sistemas del mismo servidor: revisar `mcrypt`, `wddx`, `xmlrpc`, `imap` e `ionCube`
  (activos en 7.0, no existen o cambian en 8.4). Dejar `display_errors` en OFF en producción.
- **POS como Super Admin (modo pruebas, sin local en la sesión)**: el local se elige en el selector de la pantalla y el cupo
  de un convenio POR MARCA depende de ese local. La búsqueda (GET) ahora envía `loc_id` y `resolverLocId()` lo lee de
  GET o POST (antes solo POST: el cupo salía en $0 con "Error de sistema" aunque la persona tuviera cupo); si todavía no
  hay local elegido se pide elegirlo, y al cambiar de local se recalcula. Un cajero real siempre tiene `loc_id` en la sesión.
- **Lecturas de `$_GET`/`$_POST`**: las ~46 que se leían sin proteger ya llevan `?? ''` (o `?? 0` con cast a int); una
  llamada sin parámetros ya no genera avisos en PHP 7.0 ni 8.4. **Todo código nuevo debe leerlos con `??`.** De paso se
  endurecieron el id de `pages/user/proses.php?act=on|off` (iba dentro de un UPDATE sin castear), `pages/gestiones/form.php`
  y los títulos con `$_GET['module']` (XSS reflejado).

**Auditoría de librerías y limpieza (23 de octubre de 2026, v7.47).** Se revisaron todas las dependencias y se actualizó lo seguro:
- **Claves con bcrypt**: `hashClave()` (`helpers/auth_helpers.php`, `password_hash` + `trim`) la usan el alta/edición de usuarios
  (`pages/user/proses.php`, la clave ahora es obligatoria al crear) y el cambio/reset (`pages/password/proses.php`). `login-check.php`
  **re-hashea solo**: si entra una cuenta con MD5 antiguo y la clave es correcta, la guarda en bcrypt (UPDATE preparado, no bloquea
  el login si falla). El login sigue aceptando MD5 y bcrypt. Probado en PHP 7.0 y 8.4 (clave con comilla simple, signo de pesos, barra invertida y comilla doble incluida).
- **Escape de HTML**: `js/html_escape.js` (cargado en `main.php`) define `esc()` (texto/atributos) y `escJs()` (valor dentro de
  `onclick="fn('...')"`: escapa como JS y luego como HTML). En PHP, `h()` de `helpers/texto_helpers.php`. **Todo texto que venga de
  la base (nombres, descripciones, correos, `res.mensaje`) y se arme como HTML debe pasar por esc()/h().** Probado creando un cliente
  con `<img onerror>` en el nombre: no se ejecuta en ninguna pantalla.
- **Librerías JS** (paquetes oficiales de npm con integridad verificada): jQuery 3.7.1, Bootstrap JS 4.6.2 (solo el bundle JS; el CSS
  del tema `assets/css/vendor/bootstrap.css` NO se tocó), DataTables 1.13.11 (+ bs4), Select2 4.0.13. Se confirmó antes que no hay
  etiquetas auto-cerradas (`<div/>`, cambio de jQuery 3.5) ni tooltips con HTML (sanitizador de Bootstrap 4.3.1+). El idioma de
  DataTables ya no se baja de un CDN: `assets/vendor/datatables.net/i18n/es-ES.json`. Se borraron las librerías sin uso
  (`assets/js/components`, ckeditor, aos, c3/morris/chartist-init, etc.) y la copia vieja `shared/` (quedan solo `sidebar.php` y
  `top-menu.php`). El error de consola `chartist ... querySelectorAll` en toda página ya existía (scripts de tarjetas de la plantilla
  que buscan contenedores que no hay): no afecta, limpiarlo sería la consolidación de gráficos (ECharts/d3/Chartist/Flot), pendiente.
- **Composer** (`composer.json` fija `config.platform.php = 7.0.33`): PHPMailer **7.1.1** (acepta PHP >= 5.5; solo cambia para quien
  hereda la clase), `econea/nusoap` eliminado (no se usaba). **TCPDF se queda en 6.7.8 a propósito: desde 6.8.0 exige PHP >= 7.1** y
  producción está en 7.0. Tiene 4 avisos (SVG, validación de certificados, mensajes de error sin escapar, comparación incorrecta;
  todos corregidos en 6.8.0) que no aplican: el sistema solo hace `writeHTML()` de HTML propio, sin SVG ni firma digital; están
  ignorados en `composer.json > config > audit`. **Al subir producción a PHP 8.x, actualizar `tecnickcom/tcpdf` a 6.11.x** (quitar
  `platform` o subirlo, `composer update tecnickcom/tcpdf`, probar el PDF del estado de cuenta con tildes). `vendor/` va en git.

**Cierre de locales (27 de octubre de 2026, v7.50).** Pedido de Elbany: unas dos veces cada tres meses hay que dar de baja
locales (sobre todo de Pizza Hut, que cierra y abre locales). **Un local nunca se elimina** (`consumo`,
`consumo_movimiento_local` y `usuario` lo referencian): se inactiva con `local.loc_activo = 0`, que ya existía.
- Al inactivar se exigen **motivo** (mín. 3 caracteres) y **fecha de cierre** (válida y no futura): columnas
  `local.loc_motivo_cierre` / `loc_fecha_cierre` y tabla `local_trazabilidad` (quién, cuándo, acción: inactivado /
  reactivado / motivo editado). `migrations/bloque26_local_cierre.sql`; el código las crea solo (`localesAsegurarEsquema()`
  en `helpers/locales_helpers.php`). Reactivar limpia motivo y fecha (quedan en el historial).
- Locales (solo Super Admin, igual que antes): botón Inactivar/Reactivar por fila, casillas + "Inactivar/Reactivar
  seleccionadas" (acción en lote con un solo motivo y fecha), buscador y filtro Todas/Activas/Inactivas por marca; el modal avisa
  cuántos usuarios activos tiene asignados el local. Endpoints `cambiar_estado_locales` y `resumen_cierre` en
  `ajax/locales/locales.php`. No se creó un permiso granular: todo el endpoint ya era solo Super Admin.
- **El POS no vende desde un local inactivo**: `ajax/pos/pos.php` rechaza `buscar`, `buscar_combo`, `registrar`,
  `registrar_giftcard` y `registrar_combo_contado` con "Su local está inactivo. Contacte al administrador." (`localSesionInactivo()`
  lee el estado en cada petición, no de la sesión) y la pantalla muestra un aviso. Historial, voucher y anulación NO se bloquean.
  Los usuarios del local cerrado siguen pudiendo entrar y NO se tocan: hay que reasignarlos a otro local desde Usuarios (la lista
  marca "local inactivo"). Mover una venta a un local inactivo se rechaza.
- Los locales inactivos siguen en el filtro de Reportes (marcados "(inactivo)") para poder consultar sus ventas pasadas; en cambio
  no salen al asignar usuarios ni en el selector del Super Admin en el POS.
- Lección: la tabla de sucursales de Locales imprimía nombre y dirección sin escapar (se pasó en la limpieza de la 7.47); ahora usa `esc()`.

**Cobranza de Gift Card: "Total cartera $0" adentro y $125 afuera (27 de octubre de 2026, v7.51).** Las carteras de Gift Card
(`cartera.car_tipo = 'GC'`, una por lote, `cartera.lgc_id`) se cobran por el VALOR DEL LOTE (`cli_valor_pagar` = cantidad x
cupo, fijado al aprobar/crear el lote), no por consumos de empleados: esas empresas ni tienen personal. `action=total` de
`ajax/gestiones/gestiones.php` sumaba `consumo` igual y daba $0, así que la lista decía $125 y el formulario "No se puede pagar un
monto mayor a la deuda". Ahora para `GC` devuelve `cli_valor_pagar` menos los pagos no rechazados, y `action=consumos` muestra el
resumen del lote (sin los códigos: son dinero canjeable). `action=cliente` devuelve `car_tipo`; al guardar, el formulario vuelve a la
lista Gift Card y no a Cartera 30. Las fechas inicio/fin de una cartera GC son el período de facturación del lote (ambas iguales).
**Toda lógica nueva de cobranza que calcule "deuda de una cartera" debe contemplar `GC`.**
Comprobado en el mismo cambio (27-oct-2026): para las carteras 30/60/90/+90 la lista (`cartera.cli_valor_pagar`) y el formulario
(`action=total`) usan la misma fórmula y la misma ventana: 42 carteras de prueba contra el endpoint real, 0 diferencias. La única
diferencia posible es de TIEMPO: la lista es una foto que solo se refresca cuando corre el cron (05:30) o el botón manual, y el
formulario recalcula en vivo; si se vende o anula algo después de la última corrida, adentro puede salir distinto hasta el
siguiente refresco (le pasó a una cartera de prueba: lista $27, adentro $26). Pendiente de confirmar con Elbany, no cambiado:
las ventanas terminan en `dia_corte - 1` con fin EXCLUSIVO (`con_fecha < fin`), así que el día anterior al día de corte no entra
en 30/60/90 (solo cae en +90 al cabo de 3 meses) — es el comportamiento original del sistema.

## Infraestructura del repo (para no repetir investigación)

- **Rama de producción real**: `feature/nuevas-funcionalidades`. El servidor
  en HostPapa corre `_pull.php` (webhook) que hace
  `git fetch origin && git reset --hard origin/feature/nuevas-funcionalidades`
  — o sea, todo lo que se pushea a esa rama llega a producción en el próximo
  pull, sin revisión intermedia. `nuevas-funcionalidades-v2` existió como
  rama separada pero ya está unificada (0 commits de diferencia) — no usarla,
  es historia, no una rama activa distinta.
- **Módulo admin real es "Clientes"** (`pages/clientes/view.php` +
  `ajax/clientes/clientes.php`), NO "Convenios" (`convenio/view.php` /
  `ajax/convenio/convenio.php` existen pero no están enlazados en ningún
  menú ni ruta de `content.php` — es código muerto, no tocar salvo que se
  vuelva a enlazar deliberadamente).
- El árbol `shared/` en la raíz es una copia vieja sin usar — `content.php`
  incluye siempre desde `pages/`, nunca desde `shared/pages/`. Ignorar
  `shared/` al buscar o editar código real.
- **Producción corre PHP anterior a 7.1** (ver comentario en `env.php`).
  Código PHP nuevo debe evitar: type hints escalares/de retorno en firmas de
  función, `list()` corto con claves, `str_contains`/`str_starts_with`/
  `str_ends_with`, arrow functions, `match`, argumentos con nombre, `?->`.
  El operador `??` sí es seguro (usado en todo el código existente).
- **No hay framework de tests** (sin PHPUnit, sin carpeta `tests/`). La
  verificación se hace con la CLI de `mysql` contra la BD local
  (`sgipro_sgc_argos`, XAMPP, usuario `root` sin password), scripts PHP CLI
  desechables, y QA manual en `http://localhost/SGC_ARGOS26/`. Nunca usar
  contraseñas/sesiones de usuarios reales para probar — crear cuentas de
  prueba desechables (`usuario` con contraseña propia) y borrarlas al final.

## Feature reciente: Cupos globales y diferenciados por marca (CU-01)

Implementada y desplegada a producción entre las versiones **5.7 → 6.2**
(agosto 2026). Un convenio (`cliente` con `cli_tipo_beneficio = 'Cupo'`)
ahora puede elegir entre dos modos, guardado en `cliente.cli_modo_cupo`:

- **`global`** (default, comportamiento de siempre): un solo cupo
  (`cliente.cli_valor_beneficio`) que el empleado gasta libremente entre
  todas las marcas del sistema (Pizza Hut, Fridays, etc. — tabla `marca`).
- **`marca`**: el cupo se reparte en montos independientes por marca, sin
  fungibilidad entre ellas. El convenio define un tope máximo por marca en
  `cliente_cupo_marca` (`cli_id`, `mar_id`, `ccm_monto_max`); cada empleado
  tiene su propio cupo por marca en `personal_cupo_marca` (`per_id`, `mar_id`,
  `pcm_asignado`, `pcm_disponible`). Ausencia de fila = cupo 0 en esa marca
  (se rechaza el pago, no se trata como "ilimitado" — este fue un bug real
  que se repitió varias veces durante la implementación y terminó
  extraído a un helper compartido, ver abajo).

**Dónde vive la lógica:**
- `helpers/cupo_marca_helpers.php` — toda la lógica de lectura/escritura de
  cupo por marca (`cupoObtenerModo`, `cupoMaximosPorMarca`,
  `cupoMarcasActivas`, `cupoMarcaDeLocal`, `cupoEmpleadoEnMarca`,
  `cupoEmpleadoPorMarca`, `cupoUpsertEmpleadoMarca`,
  `cupoDescontarEmpleadoMarca`, `cupoDevolverEmpleadoMarca`,
  `cupoGuardarMaximosPorMarca`, `cupoValidarPorMarca`). Cualquier lugar
  nuevo que necesite tocar cupo por marca debe reusar estas funciones, no
  reimplementar la validación (ya se repitió sin querer 3 veces antes de
  centralizarse en `cupoValidarPorMarca`).
- `js/cupo_marca_shared.js` — render/lectura de inputs de cupo por marca en
  el navegador, compartido entre `pages/clientes/view.php` y
  `pages/portal_empresa/view.php` (cada página mantiene sus propios nombres
  de función como wrappers finos sobre este archivo, para no tocar los
  puntos de llamada existentes).
- Backend tocado: `ajax/clientes/clientes.php` (crear/editar convenio,
  editar empleado, Carga Masiva), `ajax/portal_empresa/portal_empresa.php`
  (crear/editar empleado, resumen, nómina, detalle), `ajax/pos/pos.php`
  (buscar, registrar, anular_venta).
- Frontend tocado: `pages/clientes/view.php`, `pages/portal_empresa/view.php`,
  `pages/pos/view.php`.
- Migración: `migrations/bloque14_cupo_por_marca.sql`.

**Regla de negocio clave** (confirmada por el cliente en nota de voz): el
cupo de una marca **nunca** se presta ni cae a otra marca, ni al cupo
global. El POS (`ajax/pos/pos.php`) valida y descuenta contra la marca del
local donde ocurre la venta (resuelta desde `local.mar_id`), y al anular
una venta el cupo se devuelve a la marca **de la venta original**
(`consumo.loc_id`), nunca a la marca del local de quien anula.

**Documentación completa**: spec en
`docs/superpowers/specs/2026-08-11-cupos-globales-marca-design.md`, plan de
implementación (17 tareas + 1 refactor, cada una con revisión de spec y de
calidad de código) en
`docs/superpowers/plans/2026-08-11-cupos-globales-marca.md`.

**Follow-ups conocidos, no bloqueantes, sin resolver todavía:**
- `ajax/locales/locales.php` (usado para el catálogo de marcas) exige
  `esSuperAdmin()` en todo el archivo, pero el módulo Clientes se habilita
  por permiso granular (`$has('clientes')`), no solo Super Admin — si algún
  rol no-superadmin usa Clientes, el selector "Por marca" se vería sin
  inputs de marca. No confirmado como problema real en producción, solo
  como riesgo teórico.
- Pequeña duplicación de lógica PHP entre `cupo_convenio` (portal_empresa)
  y `cupo_convenio_cliente` (clientes) al construir el array `por_marca`
  — mismo patrón que ya se extrajo del lado de validación
  (`cupoValidarPorMarca`), pero esta parte de lectura no se consolidó.
  candidato natural si aparece un tercer consumidor.
  - `ajax/clientes/clientes.php`'s Carga Masiva hace una consulta
  `cupoMaximosPorMarca` por cada fila del archivo (no es N+1 por columna,
  solo por fila) — barato hoy, pero si algún día se suben archivos de
  cientos de filas convendría sacar la consulta fuera del loop.
  - Pequeñas inconsistencias de naming entre `ajax/pos/pos.php`'s
  `registrar` (`$modoCupoEmp`, `$mar_id_venta`) y `anular_venta`
  (`$modoAnulacion`, `$marDeVenta`) — cosmético, no funcional.
