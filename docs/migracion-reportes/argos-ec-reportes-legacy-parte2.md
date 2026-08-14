# ARGOS-EC.COM — Documentación de Reportes (Parte 2)
Módulo REPORTES — continuación de la documentación legacy.

> **ESTADO: casi completo.** Se cortó de nuevo al pegar en el chat (cuarta
> vez), esta vez casi al final, a mitad de la nota de negocio de
> "Transacciones DataFast" (punto 11). Falta:
> - Terminar la nota de negocio de "Transacciones DataFast" (se cortó en
>   `"Incor...`", probablemente "Incorrecta"/"Incompleta" como estado de
>   transacción fallida).
> - **Reporte GifCards** (punto 12 de la lista, no llegó nada).

---

## 1. Cobranza Pendiente por Mes

**URL formulario:** `https://argos-ec.com/index.php/reportes/cobranza-pendiente-por-mes`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptpendingcollectionmonth&view=result&dateend=2026-08-13&companyid=&mark=1&localid=&cityid=&company_local_id=`

**Filtros:**
- MARCA (obligatorio) — select: FRIDAYS, PIZZA HUT
- COMPANIA — select dependiente de marca: ADELNORTE, COSTAHUT, SODETUR, -TODAS-
- CIUDAD — select: CUENCA, IBARRA, MANTA, GUAYAQUIL, QUITO, -TODAS-
- LOCAL — select dependiente de ciudad, -TODOS-
- CLIENTE — select con listado completo de empresas/personas clientes (150+ opciones), -TODOS-
- FECHA CORTE (obligatorio) — date picker

**Columnas (en orden):** AÑO | MES | VALOR

**Datos de ejemplo (anonimizados; marca FRIDAYS, todos los clientes, corte 2026-08-13):**

| AÑO | MES | VALOR |
|---|---|---|
| 2024 | 12 | 1053.43 |
| 2025 | 1 | 285.90 |
| 2025 | 6 | 300.00 |
| 2025 | 12 | 870.89 |
| 2026 | 8 | 625.10 |

**Totales:** fila `TOTAL` al final con la suma de todos los períodos (ejemplo obtenido: 56,499.09).

**Exportación a Excel:** Sí (botón "Exportar").

**Notas de negocio:**
- Es un reporte histórico acumulado por año-mes (no limitado al año en curso), útil para ver evolución de cartera pendiente.
- Requiere seleccionar MARCA y FECHA CORTE; si se deja algún combo obligatorio vacío, el formulario no envía la búsqueda (validación silenciosa en cliente).
- El filtro CLIENTE reutiliza el mismo catálogo de empresas/personas que otros reportes de cobranza.

---

## 2. Reporte Pendiente Empresas

**URL formulario:** `https://argos-ec.com/index.php/reportes/reporte-pendiente-empresas`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptaccountstatuscompany&view=result&companyid=&year=2025`

**Filtros:**
- CLIENTE — select, -TODOS- o empresa específica
- AÑO — select (2018–2026)

**Columnas (en orden):** EMPRESA | [MES]-1 … [MES]-12 (una columna por cada mes del año seleccionado, formato "AAAA-M") | TOTAL

**Datos de ejemplo (anonimizados; año 2025, todos los clientes):**

| EMPRESA | 2025-1 | 2025-2 | … | 2025-11 | 2025-12 | TOTAL |
|---|---|---|---|---|---|---|
| Empresa Demo Corp S.A. | 7332.38 | 6080.61 | … | 9088.99 | 7508.97 | 103309.37 |
| Publicidad Ejemplo S.C.C. | 0.00 | 19.99 | … | 41.16 | 78.98 | 366.26 |
| Asociación Gremial Ficticia GYE | 0.00 | 0.00 | … | 535.40 | 502.29 | 1602.97 |

**Totales:** fila final `TOTAL POR COBRAR` con la suma general (ejemplo: 107,855.33).

**Exportación a Excel:** Sí (botón "Exportar").

**Notas de negocio:**
- Lista todas las empresas/clientes del catálogo aunque su saldo sea 0.00 en todos los meses (no filtra registros vacíos).
- Sirve como vista consolidada anual de cartera pendiente por empresa, complementaria a "Cobranza Pendiente por Mes" (que es histórica multianual) y "Cobranza Pendiente por Empresa".

---

## 3. Ventas por Locales (Liquidación)

**URL formulario:** `https://argos-ec.com/index.php/reportes/ventas-por-locales`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptargossaleslocal&view=result&mark=1&year=2025&month=12`

**Filtros:**
- MARCA (obligatorio) — select: FRIDAYS, PIZZA HUT, VACO Y VACA
- AÑO — select (2016–2026)
- MES — select ENERO…DICIEMBRE

**Columnas (en orden):**
- Detalle por local: LOCAL | VALOR
- Resumen: TOTAL VENTA | SUB TOTAL VENTA | IVA | COMISION | IVA COMISION | TOTAL FACT

**Datos de ejemplo (anonimizados; marca FRIDAYS, diciembre 2025):**

Detalle ventas Business Card (desde 2025-12-01 hasta 2025-12-31):

| LOCAL | VALOR |
|---|---|
| C.C. Centro Norte | 1,081.13 |
| C.C. Sur Plaza | 100.00 |
| C.C. Vía Principal | 510.37 |

Resumen:

| TOTAL VENTA | SUB TOTAL VENTA | IVA | COMISION | IVA COMISION | TOTAL FACT |
|---|---|---|---|---|---|
| 1,691.50 | 1,470.87 | 220.63 | 183.86 | 27.58 | 211.44 |

**Totales:** fila `TOTAL` = TOTAL FACT (ejemplo: 211.44).

**Exportación a Excel:** No (no se encontró botón de exportación en esta vista).

**Notas de negocio:**
- El título interno de la página ("Reporte Cobranzas") no coincide con el nombre del menú ("Ventas por Locales (Liquidación)"); es un reporte de liquidación mensual de comisiones por marca, no un reporte genérico de cobranza.
- El período queda fijado por AÑO+MES seleccionados (siempre mes completo).

---

## 4. Reporte Recargas

**URL formulario:** `https://argos-ec.com/index.php/reportes/reporte-recargas`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptcardreload&view=result&tarjeta=8543500015302810&companyid=162`

**Filtros:**
- Empresa (obligatorio) — select con catálogo reducido (~13 empresas, mayormente agencias de viaje/turismo y algunas corporativas)
- Tarjeta (obligatorio) — select dependiente de Empresa, poblado vía AJAX solo si la empresa tiene tarjetas con recargas registradas

**Columnas (en orden):** FECHA | HORA | OBSERVACION | VALOR

**Datos de ejemplo (anonimizados; empresa "Agencia Demo S.A.", tarjeta seleccionada):**

| FECHA | HORA | OBSERVACION | VALOR |
|---|---|---|---|
| 2022-02-10 | 14:13:00 | . | 400.00 |
| 2022-03-05 | 18:37:01 | Recarga 05.03 | 200.00 |
| 2022-04-14 | 16:35:43 | RECARGA 14.04 | 100.00 |
| 2022-06-08 | 09:40:53 | recarga 07.06 | 100.00 |

**Totales:** fila `TOTAL` con la suma de recargas de esa tarjeta (ejemplo: 5,933.55).

**Exportación a Excel:** No.

**Notas de negocio:**
- El combo "Empresa" solo lista un subconjunto específico de clientes (aparentemente los habilitados para el esquema de "recarga" prepago de tarjetas, distinto del catálogo general de clientes usado en otros reportes).
- De varias empresas probadas, la mayoría no tenía tarjetas con historial de recarga disponible; el reporte solo devuelve datos si existe al menos una recarga registrada para la tarjeta elegida.
- Es un detalle transaccional por tarjeta individual, no un agregado por empresa/período.

---

## 5. Ventas Ciudades

**URL formulario:** `https://argos-ec.com/index.php/reportes/ventas-ciudades`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptsalescity&view=result&mark=1&datestart=2025-01-01&dateend=2025-12-31`

**Filtros:**
- MARCA (obligatorio) — select: FRIDAYS, PIZZA HUT
- FECHA DESDE (obligatorio) — date picker
- FECHA HASTA (obligatorio) — date picker

**Columnas (en orden, repetidas por cada ciudad):** N° | EMPRESA | VALOR BRUTO | VALOR PONDERADO NETO (SIN IVA)

**Datos de ejemplo (anonimizados; marca FRIDAYS, 2025-01-01 a 2025-12-31):**

Ciudad: GUAYAQUIL

| N° | EMPRESA | VALOR BRUTO | VALOR PONDERADO NETO (SIN IVA) |
|---|---|---|---|
| 1 | Asociación Financiera Demo GYE | 459.85 | 410.58 |
| 2 | Asociación Bancaria Ficticia GYE | 103.86 | 92.73 |

`TOTAL GUAYAQUIL`: 563.71 / 503.31

Ciudad: QUITO (17 empresas en el ejemplo real; se muestran 3)

| N° | EMPRESA | VALOR BRUTO | VALOR PONDERADO NETO (SIN IVA) |
|---|---|---|---|
| 1 | Fundación Empleados Demo | 355.97 | 317.83 |
| 2 | Corporativo Empresarial Ejemplo | 7761.19 | 6929.63 |
| 3 | Publicidad Ejemplo S.C.C. | 246.68 | 220.25 |

`TOTAL QUITO`: 18892.44 / 16868.25

**Totales:** subtotal por cada ciudad (`TOTAL <CIUDAD>`); ciudades sin ventas muestran 0.00/0.00. No hay un gran total consolidado de todas las ciudades.

**Exportación a Excel:** No.

**Notas de negocio:**
- Agrupa automáticamente por las 14 ciudades del catálogo (Ambato, Babahoyo, Cuenca, Guayaquil, Ibarra, La Libertad, Latacunga, Los Ríos, Machala, Manta, Quito, Riobamba, Santo Domingo, etc.), mostrando todas aunque tengan 0.00.
- Incluye un gráfico de barras (Chart.js o similar) con el detalle de la ciudad con más registros (Quito en el ejemplo), embebido debajo de las tablas.

---

## 6. GiftPoint (Reporte Consumo GiftPoint)

**URL:** `https://argos-ec.com/index.php/reportes/giftpoint` (sin filtros, carga el listado completo)
**URL detalle de transacción:** `https://argos-ec.com/index.php/component/statuscard/?view=cardtransactions&tarjeta=<numero_tarjeta>`

**Filtros:** Ninguno. El listado se despliega completo (miles de registros) sin paginación ni buscador visible.

**Columnas (en orden):** FECHA REGISTRO | HORA REGISTRO | TARJETA | VALOR | CONSUMO | SALDO | (enlace) Detalle Transacciones

**Datos de ejemplo (anonimizados):**

| FECHA REGISTRO | HORA REGISTRO | TARJETA | VALOR | CONSUMO | SALDO |
|---|---|---|---|---|---|
| 2020-12-24 | 11:54:31 | 8543500099990001 | 15.00 | 15.00 | 0.00 |
| 2021-04-19 | 19:23:49 | 8543500099990002 | 20.00 | 12.00 | 8.00 |
| 2021-07-09 | 12:46:53 | 8543500099990003 | 96.00 | 96.00 | 0.00 |

Al hacer clic en "Detalle Transacciones" se abre la vista **Transacciones Tarjeta** con las columnas: FECHA | HORA | LOCAL | ATENDIDO POR | TARJETA (enmascarada, ej. `XXXXXXXXXX0001`) | DOCUMENTO | NOMBRES | AUTORIZACION | VALOR.

Ejemplo anonimizado de ese detalle:

| FECHA | HORA | LOCAL | ATENDIDO POR | TARJETA | DOCUMENTO | NOMBRES | AUTORIZACION | VALOR |
|---|---|---|---|---|---|---|---|---|
| 2020-12-29 | 16:19:40 | C.C. Centro Norte | Ana Empleada Demo | XXXXXXXXXX0001 | 0100000001 | Cliente Ejemplo Uno | 334050 | 15.00 |

**Totales:** No presenta totales ni subtotales en ninguna de las dos vistas.

**Exportación a Excel:** No.

**Notas de negocio:**
- Corresponde al programa "GiftPoint" (cliente corporativo de Pizza Hut que administra tarjetas de consumo tipo certificado); el listado principal es un histórico completo de todas las tarjetas emitidas bajo ese esquema, sin poder acotar por fecha ni empresa desde la UI.
- El detalle por tarjeta expone datos personales del portador (nombre y número de documento/cédula) y del empleado que atendió la transacción; conviene restringir su acceso en la migración.

---

## 7. Tarjetas Virtuales

**URL:** `https://argos-ec.com/index.php/reportes/tarjetas-virtuales`

**Filtros:**
- FECHA REGISTRO — date picker
- EMPRESA — select, -Todas- o empresa específica
- DOCUMENTO — texto libre (cédula/RUC)
- CANJEADA — select, -Todos- / Sí / No
- Botones: Filtrar, Limpiar Filtros, Exportar

**Columnas (en orden):** FECHA REGISTRO | EMPRESA | DOCUMENTO | NOMBRES | TARJETA (enlace "Ver Tarjeta")

**Datos de ejemplo (anonimizados; vista sin filtros, Registros: 4277):**

| FECHA REGISTRO | EMPRESA | DOCUMENTO | NOMBRES | TARJETA |
|---|---|---|---|---|
| 2021-07-01 | Corporativo Empresarial Ejemplo | 1700000001 | (sin nombre) | Ver Tarjeta |
| 2021-07-01 | Corporativo Empresarial Ejemplo | 1700000002 | PRODUBANCO (código interno) | Ver Tarjeta |
| 2021-07-01 | Publicidad Ejemplo S.C.C. | 0100000003 | Cliente Ejemplo Dos | Ver Tarjeta |
| 2021-07-01 | Asociación Financiera Demo GYE | 0900000004 | García Torres, Ana Lucía | Ver Tarjeta |

El enlace "Ver Tarjeta" abre un modal con la imagen/mockup de la tarjeta virtual, mostrando: marca (ej. PIZZA HUT CARD), texto "GIFT CERTIFICATE CORPORATIVO", número de tarjeta, RUC de la empresa emisora, descripción del beneficio (ej. "1 pizza mediana..."), código y fecha de caducidad.

**Totales:** Se muestra un contador `Registros: N` (ejemplo: 4277); no hay sumatorias de valores. Pagina de a ~10 registros con navegación numerada.

**Exportación a Excel:** Sí (botón "Exportar").

**Notas de negocio:**
- Expone documento de identidad y nombre del beneficiario de cada tarjeta virtual emitida; es el reporte maestro de emisión de tarjetas virtuales corporativas/regalo.
- El filtro CANJEADA permite distinguir tarjetas ya redimidas de las pendientes, útil para conciliación de campañas.

---

## 8. Comisión Mensual Empresas

**URL formulario:** `https://argos-ec.com/index.php/reportes/comision-mensual-empresas`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rpttotalaccountstatusmonth&view=result&year=2025&month=12&mark=1`

**Filtros:**
- MARCA (obligatorio) — select: FRIDAYS, PIZZA HUT
- AÑO — select (2016–2026)
- MES — select ENERO…DICIEMBRE

**Columnas (en orden):** EMPRESA | % COMISION EMPRESA | VENTA NETA | IVA | TOTAL VENTA | COMISION EMPRESA | % COMISION ARGOS | COMISION ARGOS

**Datos de ejemplo (anonimizados; marca FRIDAYS, diciembre 2025):**

| EMPRESA | % COMISION EMPRESA | VENTA NETA | IVA | TOTAL VENTA | COMISION EMPRESA | % COMISION ARGOS | COMISION ARGOS |
|---|---|---|---|---|---|---|---|
| Fundación Empleados Demo | 5.00% | 40.13 | 4.81 | 44.94 | 2.01 | 12.5% | 5.02 |
| Corporativo Empresarial Ejemplo | 0.00% | 748.79 | 89.85 | 838.64 | 0.00 | 20% | 149.76 |
| Asociación Nacional Demo | 2.00% | 301.47 | 36.18 | 337.65 | 6.03 | 12.5% | 37.68 |

**Totales:** fila `TOTAL` con suma de VENTA NETA, IVA, TOTAL VENTA, COMISION EMPRESA y COMISION ARGOS (ejemplo: 1510.27 / 36.18 / 1691.50 / 25.84 / 247.10).

**Exportación a Excel:** No.

**Notas de negocio:**
- El título interno de la página es "Estado de Cuenta" (no coincide con el nombre de menú), igual que en "Ventas por Locales (Liquidación)".
- Muestra dos comisiones simultáneas por transacción: la que la empresa cliente cobra a sus propios empleados/afiliados (% Comisión Empresa) y la que Argos cobra a la marca (% Comisión Argos); son conceptos distintos y no deben sumarse entre sí.

---

## 9. Reporte Landing

**URL formulario:** `https://argos-ec.com/index.php/reportes/reporte-landing`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptlandingdata&view=result&datestart=2020-01-01&dateend=2026-12-31&city=`

**Filtros:**
- CIUDAD — select con 18 ciudades, -TODAS-
- FECHA DESDE — date picker
- FECHA HASTA — date picker

**Columnas (en orden):** N° | EMPRESA | DOCUMENTO | NOMBRES | EMAIL | CIUDAD | CELULAR | FECHA NACIMIENTO

**Datos de ejemplo (ANONIMIZADOS — el reporte real expone cédula, celular y fecha de nacimiento de personas reales; valores aquí son ficticios manteniendo formato):**

| N° | EMPRESA | DOCUMENTO | NOMBRES | EMAIL | CIUDAD | CELULAR | FECHA NACIMIENTO |
|---|---|---|---|---|---|---|---|
| 1 | Asociación Docentes Demo | 0100000101 | Pérez Andrade Carlos Manuel | cperez1@ejemplo.com | Cuenca | 0991000101 | 1957-04-13 |
| 2 | Asociación Docentes Demo | 0100000102 | Torres Vega Juan Manuel | jtorres2@ejemplo.com | Cuenca | 0991000102 | 1965-07-04 |
| 3 | Asociación Docentes Demo | 0100000103 | Guillén Ortiz Fanny Eulalia | fguillen3@ejemplo.com | Cuenca | 0991000103 | 2021-12-14 |
| 4 | Empresa Demo Textil C.A. | 0100000104 | Cabrera Aguilar Diego Esteban | dcabrera4@ejemplo.com | Cuenca | 0991000104 | 1978-10-12 |

**Totales:** No presenta totales ni subtotales; es un listado tabular plano.

**Exportación a Excel:** Sí (botón "Exportar").

**Notas de negocio:**
- Registra las capturas de un formulario de tipo "landing page" de captación (probablemente para vincular empleados de empresas convenio al programa de tarjetas/beneficios), incluyendo datos personales sensibles (cédula, celular personal y fecha de nacimiento).
- **Dato sensible:** a diferencia de otros reportes que enmascaran el número de tarjeta, este reporte no enmascara ningún campo; se recomienda restringir fuertemente el acceso a este reporte en cualquier migración y evaluar si la fecha de nacimiento y el celular deben mostrarse en pantalla o solo estar disponibles en la exportación con control de acceso adicional.

---

## 10. Registro Pagos Gift

**URL formulario:** `https://argos-ec.com/index.php/reportes/registro-pagos-gift`
**URL resultado:** `https://argos-ec.com/index.php/reportes/index.php?option=com_rptclosegiftbc&view=result&year=2025&month=12&companyid=6`

**Filtros:**
- CLIENTE (obligatorio) — select con catálogo extenso de empresas/personas (300+ opciones)
- AÑO — select (2016–2026)
- MES — select ENERO…DICIEMBRE

**Columnas (en orden):**
- Encabezado: NOMBRE CLIENTE | PERIODO | ESTADO DE PAGO
- Detalle: FECHA | TARJETA | DOCUMENTO | NOMBRES | VALOR | ESTADO PAGO
- Total: TOTAL A PAGAR
- Formulario de acción: VALOR PENDIENTE | FECHA DE PAGO | OBSERVACION, con botones "Cancelación Total" y "Cerrar Estado de Cuenta"

**Datos de ejemplo (anonimizados; cliente "Cliente Corporativo Demo", diciembre 2025):**

Encabezado: NOMBRE CLIENTE: Cliente Corporativo Demo | PERIODO: DESDE 2025-12-01 HASTA 2025-12-31 | ESTADO DE PAGO: Pendiente

| FECHA | TARJETA | DOCUMENTO | NOMBRES | VALOR | ESTADO PAGO |
|---|---|---|---|---|---|
| 2025-12-01 | 8543500099990101 | 0100000201 | Cliente Corporativo Demo | 20.00 | Pendiente |
| 2025-12-02 | 8543500099990102 | 0100000201 | Cliente Corporativo Demo | 40.00 | Pendiente |
| 2025-12-31 | 8543500099990199 | 0100000201 | Cliente Corporativo Demo | 35.00 | Pendiente |

**Totales:** fila `TOTAL A PAGAR` con la suma de todos los valores del período (ejemplo: 4,162.04).

**Exportación a Excel:** Sí (botón "Exportar").

**Notas de negocio:**
- No es un reporte de solo lectura: incluye un formulario para registrar pagos parciales (VALOR PENDIENTE + FECHA DE PAGO + OBSERVACION) o cerrar totalmente el estado de cuenta del período ("Cancelación Total" / "Cerrar Estado de Cuenta"), es decir, funciona también como pantalla de conciliación/liquidación de cobros de Gift.
- El campo NOMBRES en el detalle puede corresponder al nombre de la empresa/cuenta madre (ej. "GIFTPOINT") en vez de una persona natural, dependiendo del tipo de cliente seleccionado.

---

## 11. Transacciones DataFast

**URL:** `https://argos-ec.com/index.php/reportes/transacciones-datafast` (sin filtros, listado completo)
**URL voucher:** `https://argos-ec.com/index.php/reportes/transacciones-datafast?view=print&tmpl=component&id=<id>`

**Filtros:** Ninguno.

**Columnas (en orden):** Fecha | Hora | Nombres | Valor | Estado | Autorización | Tarjeta (enmascarada, ej. `XXXX XXXX XXXX 1234`) | (enlace) Imprimir Voucher

**Datos de ejemplo (anonimizados):**

| Fecha | Hora | Nombres | Valor | Estado | Autorización | Tarjeta |
|---|---|---|---|---|---|---|
| 2021-08-18 | 11:01:59 | Juan Ejemplo Ibarra | 1.12 | Correcta | 237253 | XXXX XXXX XXXX 3624 |
| 2022-05-03 | 14:26:24 | Alisson Demo Yaguana | 300.00 | Correcta | 364571 | XXXX XXXX XXXX 4712 |
| 2023-04-19 | 11:11:54 | María Demo Endara | 224.00 | Correcta | 403390 | XXXX XXXX XXXX 6269 |

El enlace "Imprimir Voucher" abre un modal con: Fecha, Hora, Tarjeta (enmascarada), Autorización, Cliente (nombre), Documento (cédula), y una tabla Producto | Cantidad | Precio | Iva | SubTotal, más Total (USD) y botón "Imprimir".

Ejemplo anonimizado del voucher:

| Campo | Valor |
|---|---|
| Cliente | María Demo Endara |
| Documento | 1700000301 |
| Producto | Gift Corporativo Ejemplo |
| Cantidad | 1 |
| Precio | 200.00 |
| Iva | 24.00 |
| Total (USD) | 224.00 |

**Totales:** No presenta totales ni subtotales en el listado.

**Exportación a Excel:** No.

**Notas de negocio:**
- Registra tanto transacciones exitosas como fallidas/anuladas (estado "Correcta" vs. otros estados — el texto exacto del estado de fallo se cortó en el insumo recibido, pendiente de confirmar el literal exacto, ej. "Incorrecta"/"Incompleta"/"Rechazada").
- **PENDIENTE DE CONFIRMAR**: el resto de esta nota de negocio y si tiene alguna otra particularidad.

---

## 12. Reporte GifCards

**URL:** `https://argos-ec.com/index.php/reportes/reporte-gifcards`

**Filtros:**
- FECHA REGISTRO DESDE (fecha)
- FECHA REGISTRO HASTA (fecha)
- EMPRESA (combobox "- Todas -", lista de ~300 empresas/personas registradas como titulares de tarjetas — mismo catálogo extenso que otros reportes de gift, ver Anexo B en la parte 1)
- TARJETA (texto libre)
- Botones: Filtrar, Limpiar Filtros, Exportar

**Columnas (en orden):** FECHA REGISTRO | EMPRESA | MARCA | NOMBRES | TARJETA | VALOR | SALDO | ESTADO | FECHA CADUCIDAD | FECHA AUTORIZACION | LOCAL CONSUMO

**Datos de ejemplo (sin filtros aplicados, "Registros: 3"; números de tarjeta anonimizados):**

| FECHA REGISTRO | EMPRESA | MARCA | NOMBRES | TARJETA | VALOR | SALDO | ESTADO | FECHA CADUCIDAD | FECHA AUTORIZACION | LOCAL CONSUMO |
|---|---|---|---|---|---|---|---|---|---|---|
| 2026-08-13 | GIFTPOINT | PIZZA HUT | GIFTPOINT | 8543500019XXXXX | 15.00 | 0.00 | Activa | 2027-02-13 | 2026-08-13 | CEIBOS EXPRESS |
| 2026-08-13 | GIFTPOINT | PIZZA HUT | GIFTPOINT | 8543500019XXXXX | 5.99 | 5.99 | Activa | 2027-02-13 | (vacío) | (vacío) |
| 2026-08-13 | GIFTPOINT | PIZZA HUT | GIFTPOINT | 8543500019XXXXX | 15.00 | 0.00 | Activa | 2027-02-13 | 2026-08-13 | SOLANDA |

*(Nota: "GIFTPOINT" aquí es una cuenta interna/de prueba usada por Argos, no un cliente real.)*

**Totales/Subtotales:** No presenta fila de total; solo un contador "Registros: N" sobre la tabla.

**Notas de negocio:**
- Sin filtros aplicados, el listado muestra únicamente las tarjetas Gift Card vigentes/recientes (en este caso 3 registros del propio día), a diferencia de otros reportes que traen histórico completo por defecto.
- El campo SALDO permite ver si la tarjeta fue parcial o totalmente consumida (ej. tarjeta con VALOR 15.00 y SALDO 0.00 = consumida en su totalidad).
- FECHA AUTORIZACION y LOCAL CONSUMO quedan vacíos si la tarjeta aún no fue canjeada en un local.
- El filtro EMPRESA reutiliza el mismo catálogo extenso de clientes/personas que aparece en otros reportes del módulo Gift (fin de la nota — el mensaje original se cortó acá; resto no confirmado, pero no parece faltar información crítica).

---

## Pendiente residual

- **Transacciones DataFast** (punto 11): falta el literal exacto del/los
  estado(s) de transacción distintos a "Correcta" (el texto se cortó en
  "Incor..." — probablemente "Incorrecta"). No bloqueante para el spec: se
  puede tratar como `estado != 'Correcta'` genérico y confirmar el string
  exacto cuando se implemente ese reporte específico.

Con esto, **los 21 reportes del menú quedan documentados** (12 en este
archivo + 7 en `argos-ec-reportes-legacy.md`, más los 2 enlaces rotos sin
contenido propio). Insumo suficiente para pasar a diseño de migración.
