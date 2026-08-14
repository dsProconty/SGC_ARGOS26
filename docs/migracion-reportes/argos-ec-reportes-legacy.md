# Documentación de Reportes — Sistema ARGOS legacy (argos-ec.com)

> Insumo crudo levantado por Claude en Chrome navegando el sistema viejo
> (argos-ec.com) con sesión ya autenticada. Sirve como input para diseñar
> el spec de migración de reportes a SGC_ARGOS26 (`pages/reportes/`).
> Datos personales (tarjetas, cédulas, nombres de personas naturales) están
> anonimizados; nombres de empresas/clientes se mantienen reales.
>
> **ESTADO: INCOMPLETO.** El menú "REPORTES" tiene en realidad **21 entradas**,
> no 16-17 como se estimó al inicio por la captura de pantalla parcial. El
> recorrido completo confirmó estos adicionales: **Registro Pagos Gift**,
> **Transacciones DataFast**, **Reporte GifCards**, y **2 entradas rotas**
> que no llevan a ningún reporte propio (sin documentar, no aplica).
>
> El paste con el documento completo se cortó **dos veces seguidas en el
> mismo punto exacto** (a mitad del reporte "Cobranza Pendiente por Mes",
> justo después de `com_rptpendingcollectionmonth&view=`) — es un límite de
> longitud del mensaje, no un accidente. Pendiente de recibir, en mensajes
> más cortos/separados:
> 1. Cobranza Pendiente por Mes (terminar)
> 2. Reporte Pendiente Empresas
> 3. Ventas por Locales (Liquidación)
> 4. Reporte Recargas
> 5. Ventas Ciudades
> 6. Giftpoint
> 7. Tarjetas Virtuales
> 8. Comisión Mensual Empresas
> 9. Reporte Landing (datos muy sensibles: cédula, fecha de nacimiento y
>    teléfono personal de miles de clientes reales — anonimizado en el doc)
> 10. Registro Pagos Gift
> 11. Transacciones DataFast
> 12. Reporte GifCards
>
> **Nota de seguridad confirmada por quien levantó los datos**: varios
> reportes (Transacciones por Local, Reporte Landing, GiftPoint, Detalle de
> Tarjetas) exponen números de tarjeta completos, cédulas, y en "Reporte
> Landing" también fecha de nacimiento y teléfono personal de miles de
> clientes reales. Todo lo cargado acá está anonimizado manteniendo el
> formato (largo, tipo de carácter, estructura) — no se registraron datos
> reales de personas naturales en este documento.
>
> Se hizo clic en "Exportar" en 2 reportes (Transacciones por Local y
> Reporte Landing) para confirmar que el botón funciona; los archivos
> deberían haber caído en la carpeta de Descargas del navegador — falta
> verificar manualmente nombre de archivo y columnas exactas.

---

## Anexo A — Listado completo del select "CLIENTE" (compartido por varios reportes)

Ana Rodriguez, ADETUPS, ADRENASPORTS, AEBP, AECEC, AFEMPE, AHCORP ECUADOR, ALEXA TEJIDOS CIA. LTDA., ALPALAT S.A., ALTERNATIVE POWER, AMA AMERICA S.A, APTA, ARGOS CORTESIAS, ARGOS EMPRESARIAL, ARGOS PUBLICIDAD S.C.C., AROUND THE WORLD, ASO. CCE., ASO. EMPLEADOS BanEcuador Quito, ASO. EMPLEADOS PRESIDENCIA, ASOC. - HOTEL COLON UIO, ASOC. CONTRALORIA GENERAL DEL ESTADO, ASOC. CRUZ ROJA ECUATORIANA, ASOC. CRUZ ROJA GUAYAS, ASOC. DOCENTES ESPE, Asoc. Empl. y Trab. EMAPA I, ASOC. EMPLEADOS BANCO PICHINCA MANTA, ASOC. EMPLEADOS BANCO PICHINCHA IBARRA, Asoc. Empleados CFN, ASOC. EMPLEADOS CFN GUAYAQUIL, ASOC. EMPLEADOS DAC, ASOC. EMPLEADOS FUNCION ELECTORAL, ASOC. EMPLEADOS MUNICIPALES DE CUENCA, Asoc. Empleados Municipio de Ibarra, ASOC. EMPLEADOS PASTEURIZADORA QUITO S.A., ASOC. EMPLEADOS PRODUBANCO CUENCA, ASOC. EMPLEADOS PRODUBANCO IMBABURA, ASOC. EMPLEADOS PRONACA, ASOC. EMPLEADOS SUPER. CIAS., ASOC. EMPLEADOS SUPER. CIAS. QUITO, ASOC. EMPLEADOS TONI S.A., ASOC. EMPLEADOS UNILEVER, ASOC. EMPLEADOS UTN, Asoc. Funcionarios SRI, ASOC. MEDICOS BACA ORTIZ, ASOC. PROFESORES ESPOL, ASOC. PROFESORES UTN, Asoc. Servidores Públicos ESPE, Asoc. Servidores Públicos GPI, ASOC. TRABAJADORES DE F.V. AREA ANDINA S.A., ASOC. TRABAJADORES POLITECNICOS ESPOL, ASOC. UNIVERSIDAD CENTRAL ECUADOR, ASOCIACION DE BANECUADOR, ASOCIACION DE EMPLEADOS DEL BANCO PICHI GYE, ASOSC. DE EMPLEADOS DEL H. CONSEJO, ASOSEL, AUSTROPARTS CIA. LTDA., AUTOFENIX S.A., BERKANAFARMA S.A., BLENASTOR C.A, BRINCO-DELIVERY S.A.S., CAJA DE AHORROS - PRONACA, Cámara de Comercio Guayaquil, CAMBRIDGE S.A., CAMPO INDUSTRIAL S.A., CASA MOELLER MARTÍNEZ C.A, CENTRO INFANTIL ESTRELLITAS, CERRADURAS ECUATORIANAS, CHUBB SEGUROS ECUADOR S.A., CLARO, Club Social Cultural TV2, COBEÑA CHIPANTAXI JIMENA PILAR, COLEMUN S.A., COMERCIAL CARLOS ROLDAN CIA. LTDA., COMERCIAL HIDROBO, Comité Empresa C.A. EL UNIVERSO, CONFORTCAR, COOP PARA LA VIVIENDA ORDEN Y SEGURIDAD, Coop. Ahorro Crédito PUCE, COOP. ANDALUCIA, COOP. COOPROGRESO LTDA., COOP. DE AHORRO Y CREDITO EMPLEADOS BAYER S.A., COOP. TEXTIL 14 DE MARZO, COOPERATIVA BANCO PACIFICO, COSMOPOLITA B&B TRAVEL CIA. LTDA., COSTACRUCEROS S.A., Crisol Comercial S.A, DELLTEX, DESPEGAR, DIRECTV - A, DMCEC DESTINATION MANAGEM, DYPROMEDIC, ECUABEIBEN CIA. LTDA., ELECTROLUX, EMPRESA PUBLICA DE VIALIDAD IMBAVIAL EP., ENDLESS EXPEDITIONS, ESSITY, EUROFARMA S.A., FARMAENLACE CIA. LTDA., FEHIERRO CIA. LTDA, FELVENZA S.A., FIRST CLASS ECUADOR, FRESENIUS MEDICAL CARE, Fundación Visión Agropecuaria, GABRIELA CAICEDO, GARLANDS, GIFT COMMERCIAL S.A.S., GLOBALEXPERIENCETOURS, GLOBALSOLUTIONS CIA LTDA, GRUPO GENIO, HOSPITAL SAN JUAN DE DIOS, HOTELES DECAMERON ECUADOR SA, IDMACERO, IMETEL CÍA. LTDA., IMPORTADORA BDH S.A., IMPORTADORA ROLORTIZ CIA. LTDA., INBALNOR, INDIMA S.A, INDUAUTO S.A., INDUGLOB S.A, INDUSTRIAS FULL, INDUSUR S.A., INDUWAGEN S.A., INPROLAC S.A., JUNTA DE LA CRUZ ROJA IMBABURA, LA HOLANDESA (×2, valores distintos), LAKE & MOUNTAIN CIA. LTDA., LATINOMEDICAL SA, LETERAGO DEL ECUADOR S.A, METRORED, MIA, Miliana Castro Alvarez, MINERVA S.A., MOTRAC S.A., NDEVELOPER, NICOLE DAYANA GUILINDRO, NOSS MEDICAL CENTER S.A.S., NOVA CLINICA DEL VALLE, NOVA TRAVEL MILVIAJESCOM CIA. LTDA., NOVAECUADOR S.A., OLIBER RUEDA, ONE CLASS TRAVEL, OPTIMUS S.A., P&A TRAVEL AGENCIA DE VIAJES, PANIJÚ S.A., PANIJU2022, PAULAND PUBLICIDAD S.A, PLAYZONE ENDIFA, POLICIA NACIONAL, PREMIUM VACATION S.A., PREMIUM VACATIONS S.A ALBORADA, PRIMAX COMERCIAL DEL ECUADOR S.A., PROCONTYSOLUCIONES CIA.LTDA., PROYECTOS INTEGRALES DEL ECUADOR, QUALA ECUADOR S.A., QUIFATEX S.A., ROAD TRACK ECUADOR S.A., Rosana Villegas, ROYAL TRAVEL GROUP, RUSTOURS PACIFIC, SEGURIDAD MUNDITRACK CIA. LTDA., SEGUROS PICHINCHA (×2), SERVICIOS INTEGRALES EN ADUANAS Y T, SIATIADUANAS S.A., SIATILOGISTICS S.A., SIATITRANS SERVICIOS INTERNACIONALE, SINDICATO ARCA, SINDICATO ARCACONTINENTAL, SINDICATO TRAB. EUGENIO ESPEJO, SITIAGRUP, Sofía Durango, SOMOS ORLANDO S.A., SUBAHI S.A, TAFRA CORP, TERMINAL PORTUARIO DE MANTA, THELIVENOW ADVENTURES S.A, TOSCANA, TU VIVIENDA A ECUADOR, TURALEZA, UNICOL S.A, UNIDAD EDUCATIVA EMILE JAQUES DALCROZE, VIRUMEC S.A., VITAPRO, VITAURO, WATCH OUT, ZURICH SEGUROS.

---

## Estado de Cuenta para Cliente

- **URL formulario**: `/index.php/reportes/reporte-de-consumo`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptaccountstatus&view=result&year=AAAA&month=M&companyid=ID&mark=0|1|2`
- **Filtros**: CLIENTE (select, obligatorio de facto — sin él el reporte no tiene sentido; mismo listado que Anexo A), MARCA (select: `- TODAS -`, FRIDAYS, PIZZA HUT), AÑO (select 2016–2026), MES (select ENERO–DICIEMBRE, sin valor por defecto).
- **Columnas (panel superior, no es tabla)**: NOMBRE CLIENTE, COMISION, FECHA CIERRE, PERIODO (rango "DESDE"/"HASTA").
- **Columnas (tabla central)**: DOCUMENTO, NOMBRES, FRIDAYS, PIZZA HUT, VALOR TOTAL (única fila visible es "TOTAL").
- **Datos de ejemplo**: probé 3 combinaciones (ADETUPS/agosto-2026, PRIMAX/julio-2026, CLARO/diciembre-2024); las tres devolvieron 0 en todas las columnas — no había transacciones en esos períodos puntuales probados. La estructura de columnas sí quedó confirmada.
- **Totales/subtotales**: bloque inferior con VENTA NETA, IVA, TOTAL VENTA, COMISION, TOTAL A PAGAR.
- **Notas de negocio**: el período de facturación se calcula según el día de "FECHA CIERRE" configurado por cliente (ej. corte día 10 → período del 11 del mes anterior al 10 del mes seleccionado).
- **Excel descargado**: no — no se encontró botón de exportar/descargar en este reporte.

---

## Transaciones Local

- **URL formulario**: `/index.php/reportes/transaciones-local`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptaccountstatuslocal&view=result&datestart=YYYY-MM-DD&dateend=YYYY-MM-DD&companyid=&mark=N&localid=&cityid=&typecard=`
- **Filtros**: MARCA (select, **obligatorio** — sin seleccionar marca el buscador no envía nada; opciones: ARGOS HOME, BIOGENET, BRANGUS, ELECTROMEGA, FRIDAYS, LATITUD CERO, NOS MEDICAL CENTER, OPTICA SOLOVISIÓN, PICARO, PIZZA HUT, PROSALUDMEDIC, PULCINELLAS C.A., VACO Y VACA, VITAGEL), TIPO TARJETA (`- TODAS -`, BUSINESS CARD, GIFT CARD), CIUDAD (`- TODAS -` + 13 ciudades: AMBATO, BABAHOYO, CUENCA, GUAYAQUIL, IBARRA, LA LIBERTAD, LATACUNGA, LOS RIOS, MACHALA, MANTA, QUITO, RIOBAMBA, SANTO DOMINGO), LOCAL (select dependiente de CIUDAD, vacío hasta elegir ciudad), CLIENTE (mismo listado que Anexo A), FECHA DESDE / FECHA HASTA (texto libre formato `YYYY-MM-DD`, sin datepicker visible que se abriera al hacer clic).
- **Columnas (en orden)**: FECHA, HORA, LOCAL, CLIENTE, TARJETA, DOCUMENTO, NOMBRES, AUTORIZACION, VALOR CONSUMO.
- **Datos de ejemplo** (anonimizados — el reporte real trae cientos de filas por rango de fechas, con número de tarjeta completo y cédula real):

| FECHA | HORA | LOCAL | CLIENTE | TARJETA | DOCUMENTO | NOMBRES | AUTORIZACION | VALOR CONSUMO |
|---|---|---|---|---|---|---|---|---|
| 2026-07-14 | 12:21:17 | SAN MARINO EXPRESS | PANIJU2022 | 8543 50XX XXXX 0430 | 12XXXXXXX3 | Cliente Ejemplo 1 | 828747 | 17.99 |
| 2026-07-14 | 16:39:51 | PUNTILLA EXPRESS | ASOCIACION DE EMPLEADOS DEL BANCO PICHI GYE | 8543 50XX XXXX 2817 | 09XXXXXXX0 | Cliente Ejemplo 2 | 828775 | 7.99 |
| 2026-07-14 | 19:05:03 | TUMBACO | ASO. CCE. | 8543 50XX XXXX 3604 | 17XXXXXXX7 | Cliente Ejemplo 3 | 828803 | 14.98 |
| 2026-07-16 | 11:02:00 | MANTA - MALL PACIFICO | *(sin empresa asociada)* | 8543 70XX XXXX 9414 | *(vacío)* | *(vacío)* | 829034 | 10.00 |
| 2026-07-18 | 22:18:24 | COTOCOLLAO | SERVICIOS INTEGRALES EN ADUANAS Y T | 8543 50XX XXXX 9749 | 17XXXXXXX6 | Cliente Ejemplo 4 | 829972 | 24.99 |

- **Totales/subtotales**: no se observó una fila de totales; es un listado transaccional fila por fila (sin agregación al final, dentro del rango probado hay cientos de registros).
- **Notas de negocio**: existen transacciones sin CLIENTE/DOCUMENTO/NOMBRES asociados (recargas o consumos de gift card anónima, marca "8543 70XX..."). También aparecen valores negativos (reversos/anulaciones, ej. -20.99).
- **Excel descargado**: sí, el reporte tiene botón "Exportar" (se hizo clic, pero no se confirmó nombre de archivo ni columnas del Excel — verificar manualmente).

---

## Reporte Detalle de Tarjetas

- **URL**: `/index.php/reportes/reporte-detalle-de-tarjetas` (no tiene formulario de filtros: es un listado directo de todos los clientes/empresas).
- **Filtros**: ninguno.
- **Columnas (en orden)**: CLIENTE, CONTACTO, DIRECCION, EMAIL, EMAIL 2, TELEFONO, TELEFONO 2, COMISION %, DIA DE CORTE, + enlace "Detalle Tarjetas" por fila.
- **Datos de ejemplo** (datos de contacto empresarial, no de tarjetahabientes finales):

| CLIENTE | CONTACTO | EMAIL | TELEFONO | COMISION % | DIA DE CORTE |
|---|---|---|---|---|---|
| ADRENASPORTS | ISRAEL HIDALGO | contabilidad@quitopaintball.com | 0988009084 | 0.00 | 15 |
| AFEMPE | Favio Morales | afempe@fiscalia.gob.ec | 022548936 | 5.00 | Fin de Mes |
| AGENCIA DE TURISMO COSTACRUCEROS S.A. | JARITZA LISBETH VALENCIA RIVAS | financiera@costacrucerosviajes.com | 0958885997 | 0.00 | 15 |
| ARGOS EMPRESARIAL | *(sin contacto)* | ventas@argos-ec.com | *(vacío)* | 0.00 | Fin de Mes |

- **Sub-reporte "Detalle Tarjetas"** (`?view=rptcompanycardsdetail&id=N`): columnas MARCA, DOCUMENTO, CLIENTE, TARJETA, VALOR, ESTADO. Ejemplo anonimizado: `PIZZA HUT | 09XXXXXXX01 | AHCORP ECUADOR | 8543 50XX XXXX 2269 | 300.00 | BLOQUEADA`.
- **Totales/subtotales**: no aplica (listado plano).
- **Notas de negocio**: "DIA DE CORTE" puede ser un número (día del mes) o el texto "Fin de Mes".
- **Excel descargado**: no — no se encontró botón de exportar.

---

## Detalle Cobranza

- **URL formulario**: `/index.php/reportes/detalle-cobranza`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptcollectiondetail&view=result&mark=N&year=AAAA&month=M&is_argos=`
- **Filtros**: MARCA (select, obligatorio: `- SELECCIONE -`, FRIDAYS, PIZZA HUT), AÑO (2016–2026), MES (ENERO–DICIEMBRE), ARGOS (select: TODOS, NO, SI — filtra si es cortesía Argos).
- **Columnas**: son varios bloques, no una sola tabla:
  - **Detalle Cobranza Business Card**: EMPRESA, PERIODO, VALOR
  - **Detalle Ventas Business Card**: EMPRESA, VALOR
  - **Detalle Ventas Gift Certificate**: EMPRESA, VALOR
  - **Detalle Ventas Tarjetas Regalo**: FECHA, VALOR
- **Datos de ejemplo** (PIZZA HUT, julio 2026 — datos agregados por empresa, no personales):

| EMPRESA | VALOR |
|---|---|
| Asoc. Funcionarios SRI | 536.54 |
| AFEMPE | 615.19 |
| ARGOS EMPRESARIAL | 5,544.03 |
| ASOC. CONTRALORIA GENERAL DEL ESTADO | 899.59 |
| ZURICH SEGUROS | 285.65 |

- **Totales/subtotales**: `TOTAL VENTA INCLUIDO IVA: 14,234.37`, `TOTAL VENTA NETA: 12,709.26`, `TOTAL VENTA GIFT CERTIFICATE: 490.54`, `TOTAL COBRANZA: 0.00`, y al final: `COMISION COBRANZA (1,5%)`, `COMISION TOTAL VENTAS (1,5%): 190.64`, `COMISION TOTAL VENTAS GIFT (3%): 14.72`, `TOTAL COMISION: 205.35`.
- **Notas de negocio**: distingue comisión sobre cobranza vs. comisión sobre ventas, y aplica tasas distintas para Business Card (1.5%) vs. Gift (3%).
- **Excel descargado**: no.

---

## Total Ventas

- **URL formulario**: `/index.php/reportes/total-ventas`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptsales&view=result&mark=N`
- **Filtros**: MARCA únicamente (select: ARGOS HOME, BIOGENET, BRANGUS, ELECTROMEGA, FRIDAYS, LATITUD CERO, NOS MEDICAL CENTER, OPTICA SOLOVISIÓN, PICARO, PIZZA HUT, PROSALUDMEDIC, PULCINELLAS C.A., VACO Y VACA, VITAGEL).
- **Columnas**: matrices mes×año, no una tabla simple. Secciones: "Evolutivo Ventas" (filas=años 2019–2026, columnas=Ene…Dic + Total), comparativos año vs año (valor absoluto y %), "Ventas Desglose" (Business Card / Gift Card / Total por mes), "Ventas por Ciudad" (GYE / UIO / Total por mes).
- **Datos de ejemplo** (PIZZA HUT):

| Año | Ene | Feb | ... | Total |
|---|---|---|---|---|
| 2024 | 17,687 | 14,122 | ... | 199,518 |
| 2025 | 16,647 | 14,930 | ... | 193,197 |
| 2026 | 13,731 | 11,867 | ... | 100,397 (parcial, hasta agosto) |

- **Totales/subtotales**: columna "Total" por año y fila "TOTAL" en desgloses.
- **Notas de negocio**: el título indica "VALORES PONDERADOS (SIN IVA)".
- **Excel descargado**: no.

---

## Registro Cobranza

- **URL formulario**: `/index.php/reportes/registro-cobranza`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptregistrationcollection&view=result&datestart=YYYY-MM-DD&dateend=YYYY-MM-DD&companyid=&mark=`
- **Filtros**: MARCAR (`- TODAS -`, FRIDAYS, PIZZA HUT), CLIENTE (mismo listado que Anexo A), FECHA DESDE, FECHA HASTA (texto libre).
- **Columnas**: EMPRESA, MARCA, FECHA REGISTRO, FECHA DESDE, FECHA HASTA, MORA, TOTAL, OBSERVACION.
- **Datos de ejemplo** (2026-01-01 a 2026-08-13, todas las marcas):

| EMPRESA | MARCA | FECHA REGISTRO | MORA | TOTAL | OBSERVACION |
|---|---|---|---|---|---|
| ARGOS EMPRESARIAL | *(vacío=todas)* | 2026-04-23 | mas de 120 | 1949.86 | BANCO INTERNACIONAL |
| ASOCIACION DE FUNCIONARIOS DEL SRI | | 2026-06-23 | 90 | 984.93 | BANCO INTERNACIONAL |
| Cooperativa de Ahorro y Crédito Universidad Católica del Ecuador | | 2026-05-15 | 120 | 18.14 | BANCO INTERNACIONAL |

- **Totales/subtotales**: fila "TOTAL" al final (ej. 3586.31 para el rango probado).
- **Notas de negocio**: "MORA" es un texto categórico (ej. "90", "120", "mas de 120"), no siempre un número puro.
- **Excel descargado**: no.

---

## Ventas Locales

- **URL formulario**: `/index.php/reportes/ventas-locales`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptsaleslocal&view=result&mark=N&datestart=YYYY-MM-DD&dateend=YYYY-MM-DD`
- **Filtros**: MARCA (obligatorio: FRIDAYS, PIZZA HUT, VACO Y VACA — nota: lista más corta que otros reportes), FECHA DESDE, FECHA HASTA.
- **Columnas**: agrupado por ciudad; dentro de cada ciudad: N, LOCAL, VALOR BRUTO, VALOR PONDERADO NETO (SIN IVA), y fila "TOTAL [CIUDAD]".
- **Datos de ejemplo** (PIZZA HUT, 2026-07-01 a 2026-08-13):

| Ciudad | Local | Valor Bruto | Neto (sin IVA) |
|---|---|---|---|
| QUITO | CHILLOS | 2005.17 | 1790.33 |
| QUITO | COTOCOLLAO | 1196.48 | 1068.29 |
| GUAYAQUIL | GUAYACANES C.C. POLARIS | 444.35 | 396.74 |
| CUENCA | REMIGIO CRESPO | 356.97 | 318.72 |

- **Totales/subtotales**: fila "TOTAL [CIUDAD]" por cada ciudad (ej. `TOTAL QUITO: 12110.21 / 10812.69`); no se vio un gran total general al final del listado completo.
- **Notas de negocio**: ninguna nota adicional visible en pantalla.
- **Excel descargado**: no.

---

## Cobranza Pendiente por Empresa

- **URL formulario**: `/index.php/reportes/cobranza-pendiente-por-empresa`
- **URL resultado**: `/index.php/reportes/index.php?option=com_rptpendingcollectioncompany&view=result&dateend=YYYY-MM-DD&companyid=&mark=N&localid=&cityid=&company_local_id=`
- **Filtros**: MARCA (obligatorio, select vacío por defecto), COMPANIA (select: `- TODAS -`, ADELNORTE, COSTAHUT, SODETUR — son las 3 "compañías cobradoras"/franquicias), CIUDAD (`- TODAS -` + CUENCA, IBARRA, MANTA, GUAYAQUIL, QUITO), LOCAL (dependiente), CLIENTE (Anexo A), FECHA CORTE.
- **Columnas**: EMPRESA, COMPANIA, AÑO, VALOR.
- **Datos de ejemplo** (PIZZA HUT, corte 2026-08-13):

| EMPRESA | COMPANIA | AÑO | VALOR |
|---|---|---|---|
| AFEMPE | SODETUR | 2026 | 1608.88 |
| ARGOS EMPRESARIAL | SODETUR | 2024 | 94017.67 |
| ARGOS EMPRESARIAL | SODETUR | 2025 | 95508.18 |
| ASOC. EMPLEADOS UTN | ADELNORTE | 2026 | 198.90 |

- **Totales/subtotales**: no se vio un total general en la porción visible (posible fila de total al final del listado completo, que es largo — no confirmado).
- **Notas de negocio**: el mismo cliente puede aparecer repetido por año con saldos pendientes distintos (deuda histórica acumulada por año).
- **Excel descargado**: no.

---

## Cobranza Pendiente por Mes — **INCOMPLETO, falta terminar de levantar**

- **URL resultado (parcial, cortada)**: `/index.php/reportes/index.php?option=com_rptpendingcollectionmonth&view=...` (URL truncada en el insumo original)
- Falta: filtros completos, columnas, datos de ejemplo, totales, notas de negocio, si tiene exportación a Excel.

---

## Anexo B — Lista de CLIENTE del módulo Gift
(usada en Registro Pagos Gift, Reporte GifCards, Reporte Pendiente Giftcards;
Tarjetas Virtuales usa una variante muy similar)

Distinta al Anexo A: mezcla empresas y personas naturales que
compraron/recibieron gift cards (cientos de opciones, ids propios de la
tabla "companies" del módulo gift). Muestra representativa (no el volcado
completo de las ~400 opciones — pendiente si se necesita el literal
íntegro): 0991028544001, 1792131936001, Academia Aeronáutica Mayor Pedro
Traversari, ACOVI SA PAGUE YA, Acromax Laboratorio Quimico Farmaceutico,
ADITMAQ CIA. LTDA., AEBP, AFEMPE, AGRIPAC S.A., AMA AMERICA S.A EMPRESA DE
SEGUROS, ARGOS CORTESIA, ARGOS PUBLICIDAD S.C.C., BANCO GUAYAQUIL, BANCO
INTERNACIONAL S.A., BANCO MACHALA, BANCO SOLIDARIO, CERVECERIA NACIONAL CN
S.A., CLUB DEPORTIVO FORMATIVO NAUTICO, COLEGIO AMERICANO DE GUAYAQUIL, DHL,
DIARIO EL UNIVERSO, ENI ECUADOR, EY ECUADOR, FARMAENLACE CIA. LTDA., FLACSO,
FRESENIUS MEDICAL CARE ECUADOR, GENTE OIL ECUADOR PTE. LTD., GIFTPOINT,
HOSPITAL SAN JUAN DE DIOS, INDURAMA, INFINIX MOBILITY LIMITED, LG
ELECTRONICS PANAMA S A, MARCIMEX S.A., MINISTERIO DE AGRICULTURA, MUTUALISTA
PICHINCHA, NETLIFE, PANIJU, PRIMAX, PRODUBANCO, PYCCA, Samsung Electronics S
A, SEGUROS DEL PICHINCHA S. A., TOYOTA DEL ECUADOR S.A., UNIVERSIDAD
CATOLICA SANTIAGO DE GUAYAQUIL, UNIVERSIDAD INTERNACIONAL SEK, ZURICH
SEGUROS ECUADOR S.A., además de decenas de nombres propios de personas
naturales (clientes individuales que recibieron/canjearon gift cards —
nombres reales omitidos aquí a propósito, ya se anonimizaron en el origen).

---

## Refinamientos de la segunda pasada (datos reales con movimiento, no ceros)

La primera pasada probó combinaciones de filtro que en varios casos dieron
0/tabla vacía. La segunda pasada repitió los mismos reportes con datos que
sí tuvieron movimiento — estos datos son más confiables que los de las
secciones de arriba para el mismo reporte:

- **Estado de Cuenta para Cliente** (CLIENTE=ARGOS EMPRESARIAL, MARCA=PIZZA
  HUT, jul-2025): sí tuvo filas. `TOTAL 0 / 7570.73 / 7570.73`. Bloque final:
  `VENTA NETA 13166.49`, `IVA -5595.76`, `TOTAL VENTA 7570.73`,
  `COMISION 0.00`, `TOTAL A PAGAR 7570.73`. Confirma columnas DOCUMENTO,
  NOMBRES, FRIDAYS, PIZZA HUT, VALOR TOTAL. Con filtros vacíos (sin cliente
  puntual) el reporte no devuelve nada — CLIENTE es obligatorio de facto.
- **Transacciones por Local** (el título real de la página es "Transacciones
  por Local", el link del menú dice "Transaciones Local" con typo): confirma
  que es un log fila por fila sin agregación al pie, incluye transacciones
  hechas con la tarjeta institucional "GIFTPOINT" (documento
  0190430863001) como cliente recurrente. Con rangos de fecha amplios el
  dataset puede ser grande y tardar en renderizar.
- **Detalle Cobranza** (PIZZA HUT, jul-2025): `TOTAL VENTA INCLUIDO IVA
  18,322.47`, `TOTAL VENTA NETA 16,359.35`, `TOTAL COBRANZA 0.00`,
  `COMISION COBRANZA (1,5%) 0.00`, `TOTAL VENTAS 16,359.35`,
  `COMISION TOTAL VENTAS (1,5%) 245.39`, `TOTAL VENTAS GIFT 223.21`,
  `COMISION TOTAL VENTAS GIFT (3%) 6.70`, `TOTAL VENTAS TARJETAS REGALO
  0.00`, `COMISION TOTAL VENTAS TARJETAS REGALO (3%) 0.00`,
  `TOTAL COMISION 252.09`. **Importante**: los porcentajes de comisión
  (1,5% cobranza/ventas, 3% gift/tarjetas regalo) están hardcodeados en la
  vista del sistema viejo, no son un parámetro configurable por cliente.
- **Registro Cobranza**: con CLIENTE=ARGOS EMPRESARIAL / PIZZA HUT / todo
  2025 la tabla vino vacía (`TOTAL 0.00`) — esto es normal, es un log de
  gestiones de cobranza cargadas **manualmente** por un operador, no
  autogenerado desde transacciones. No siempre va a tener datos.
- **Ventas Locales** (PIZZA HUT, jul-2025): confirma agrupación por ciudad
  con fila `TOTAL <CIUDAD>` al cierre de cada grupo (ej. `TOTAL GUAYAQUIL
  6006.91 5363.31`), sin gran total general visible al final del listado
  completo.
- **Total Ventas** (PIZZA HUT): fila 2025 completa del evolutivo mensual:
  `16,647 / 14,930 / 15,890 / 12,043 / 19,723 / 21,043 / 16,738 / 16,484 /
  15,914 / 16,029 / 15,325 / 12,432` → Total `193,197`.

---

## Cobranza Pendiente por Empresa — dato adicional, pero corte de nuevo

En la segunda pasada, al re-ejecutar este reporte con MARCA=PIZZA HUT el
mensaje se cortó de nuevo a mitad de palabra (`...VALOR / Datos de ejemplo
/ Totales: No se pudo re-confirmar en esta pasada — al ejecutar el reporte
con MARCA=PIZZ[...]`). No hay información nueva utilizable de este segundo
intento; se mantiene válida la documentación de la primera pasada más
arriba en este archivo.

---

## Pendientes por documentar (todavía sin levantar, 3 intentos de paste se cortaron en el mismo rango de longitud)

1. Cobranza Pendiente por Mes
2. Reporte Pendiente Empresas
3. Ventas por Locales (Liquidación)
4. Reporte Recargas
5. Ventas Ciudades
6. Giftpoint
7. Tarjetas Virtuales
8. Comisión Mensual Empresas
9. Reporte Landing (datos muy sensibles — cédula, fecha de nacimiento y
   teléfono personal de miles de clientes reales, anonimizar estrictamente)
10. Registro Pagos Gift
11. Transacciones DataFast
12. Reporte GifCards

**Recomendación para levantar el resto**: en vez de pegar el documento en el
chat (se corta siempre en el mismo rango de longitud), pedile a Claude en
Chrome que **escriba el archivo directamente en el filesystem**, en
`C:\xampp\htdocs\SGC_ARGOS26\docs\migracion-reportes\argos-ec-reportes-legacy-parte2.md`
(mismo formato de sección usado arriba, uno por reporte). Así no hay límite
de longitud de mensaje y yo lo leo directo del archivo.
