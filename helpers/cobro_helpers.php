<?php
// Lo que se le cobra a un convenio por una venta: SOLO la parte que salió del cupo de su empleado
// (consumo.con_monto_convenio). Una venta mixta (parte con cupo, parte con gift card o pago externo)
// ya trae esas otras partes pagadas por otro medio, y no se le pueden facturar a la empresa.
// Decisión del cliente (15-oct-2026). Las ventas migradas del sistema viejo traen con_monto_convenio = total,
// y las filas viejas sin ese dato (NULL) se toman completas.
//
// Toda suma que represente cobranza / estado de cuenta de una empresa debe usar estas expresiones en vez de
// con_valor_total. (Las cifras de venta por marca o local siguen siendo el total vendido.)
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (!function_exists('cobroConvenioSql')) {
    // Monto cobrable al convenio de la venta (expresión SQL). $alias = alias de la tabla consumo.
    function cobroConvenioSql($alias = 'con') {
        return "COALESCE($alias.con_monto_convenio, $alias.con_valor_total)";
    }
}

if (!function_exists('cobroFraccionSql')) {
    // Fracción de la venta que corresponde al convenio (0 a 1), para repartir neto e IVA en la misma proporción.
    function cobroFraccionSql($alias = 'con') {
        return "(CASE WHEN $alias.con_valor_total > 0
                      THEN LEAST(1, " . cobroConvenioSql($alias) . " / $alias.con_valor_total)
                      ELSE 1 END)";
    }
}
