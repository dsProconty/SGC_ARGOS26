<?php
// Columna que usa el voucher impreso (v7.55):
//   consumo.con_saldo_posterior  cupo (o saldo de gift card) que le quedó al cliente justo después de esa venta;
//                                se guarda en el momento de la venta para que una reimpresión muestre lo que
//                                decía el voucher original y no el saldo de hoy. NULL en ventas anteriores.
// Se crea sola en el primer request que la necesita (migrations/bloque28_voucher_saldo.sql hace lo mismo).
//
// Sin type hints ni sintaxis de PHP nuevo: producción corre PHP < 7.1.
require_once __DIR__ . '/db_helpers.php';

if (!function_exists('voucherAsegurarColumnas')) {
    function voucherAsegurarColumnas($mysqli) {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        agregarColumnaSiNoExiste($mysqli, 'consumo', 'con_saldo_posterior', 'DECIMAL(10,2) NULL');
    }
}
