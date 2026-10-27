// Impresión de vouchers de venta (POS y reimpresión desde Historial).
// Un solo layout que entra en las dos impresoras de ticket de los locales:
//   - Epson TM-T88VII: térmica, papel 80 mm (~72 mm imprimibles)
//   - Epson TM-U220:   matricial, papel 76 mm (~63 mm imprimibles, solo B/N)
// Por eso el ticket mide 60 mm de ancho (el menor de los dos con margen) y
// no se fija el tamaño de página: se usa el papel configurado en el driver.
var VOUCHER_ANCHO_MM = 60;

function voucherEstilos() {
    return '<style>'
        + '@page { margin: 0; }'
        + '* { box-sizing: border-box; }'
        + 'html, body { margin: 0; padding: 0; }'
        + 'body { width: ' + VOUCHER_ANCHO_MM + 'mm; margin: 0 auto; padding: 2mm; font-family: "Courier New", monospace; font-size: 10px; color: #000; }'
        + 'table { width: 100%; border-collapse: collapse; }'
        + 'td { padding: 1px 0; vertical-align: top; }'
        + 'td:first-child { white-space: nowrap; }'
        + 'td.text-right { padding-left: 4px; overflow-wrap: anywhere; }'
        + 'hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }'
        + '.text-right { text-align: right; }'
        + '.text-center { text-align: center; }'
        + '.mb-0 { margin-bottom: 0; } .mb-1 { margin-bottom: 2px; } .mb-2 { margin-bottom: 4px; } .mb-3 { margin-bottom: 6px; }'
        + '.mt-2 { margin-top: 4px; } .mt-3 { margin-top: 6px; }'
        + 'strong, .font-weight-bold { font-weight: bold; }'
        + 'small { font-size: 9px; }'
        + '.badge { display: inline-block; padding: 2px 6px; border: 1px solid #000; font-size: 10px; }'
        + 'code { font-family: "Courier New", monospace; }'
        + '</style>';
}

function voucherImprimir(contenidoHtml) {
    var ventana = window.open('', '_blank', 'width=400,height=600');
    // Estilos propios, sin bootstrap.css: cargar ese archivo en una ventana en
    // blanco es lento/frágil (H-016) y está pensado para pantallas anchas.
    // H-B2B-02: cerrar recién con el evento afterprint; cerrar justo después de
    // print() hacía que la ventana desapareciera antes de verse el diálogo.
    var scriptImprimir = '<script>'
        + 'window.onafterprint = function () { window.close(); };'
        + 'window.focus();'
        + 'window.print();'
        + '<\/script>';
    ventana.document.open();
    ventana.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Voucher SGC</title>' + voucherEstilos()
        + '</head><body>' + contenidoHtml + scriptImprimir + '</body></html>');
    ventana.document.close();
}

function voucherEsc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Contenido del voucher. Es el ÚNICO lugar donde se arma: lo usan el POS
// (venta recién confirmada) y la reimpresión del Historial, para que ambos
// tickets sean idénticos (antes el Historial tenía una copia vieja sin IVA,
// combo ni gift card, que mostraba "null" en ventas con gift card).
function voucherHtml(d, reimprimir, ivaPct) {
    var reimpresionBadge = reimprimir
        ? '<div class="text-center"><span class="badge badge-warning">REIMPRESIÓN</span><br><small>' + new Date().toLocaleString() + '</small></div><hr>'
        : '';
    var descripcionRow = d.con_descripcion
        ? '<tr><td><strong>Descripción</strong></td><td class="text-right">' + voucherEsc(d.con_descripcion) + '</td></tr>'
        : '';
    // Sección beneficiario (empleado o gift card)
    var beneficiarioSection = '';
    if (d.per_nombre) {
        beneficiarioSection = '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
            + '<tr><td><strong>Empleado</strong></td><td class="text-right">' + voucherEsc(d.per_nombre) + '</td></tr>'
            + '<tr><td><strong>Cédula</strong></td><td class="text-right">' + voucherEsc(d.per_documento) + '</td></tr>'
            + '<tr><td><strong>Empresa</strong></td><td class="text-right">' + voucherEsc(d.cli_descripcion) + '</td></tr>'
            + '</table><hr>';
    } else if (d.con_giftcard_codigo) {
        beneficiarioSection = '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
            + '<tr><td><strong>Gift Card</strong></td><td class="text-right"><code>' + voucherEsc(d.con_giftcard_codigo) + '</code></td></tr>'
            + '</table><hr>';
    }
    var comboSection = d.combo_nombre
        ? '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
            + '<tr><td><strong>Combo</strong></td><td class="text-right">' + voucherEsc(d.combo_nombre) + '</td></tr>'
            + '<tr><td><strong>Código</strong></td><td class="text-right"><code>' + voucherEsc(d.combo_codigo) + '</code></td></tr>'
            + '</table><hr>'
        : '';

    // Saldo que le quedó justo después de esta venta (se guarda al vender: una reimpresión muestra el mismo valor).
    // Las ventas anteriores a esta función no lo tienen y no muestran la línea.
    var saldoRow = '';
    if (d.con_saldo_posterior !== null && d.con_saldo_posterior !== undefined && d.con_saldo_posterior !== '') {
        saldoRow = '<tr><td>' + (d.per_nombre ? 'Cupo disponible' : 'Saldo Gift Card') + '</td><td class="text-right">$' + parseFloat(d.con_saldo_posterior).toFixed(2) + '</td></tr>';
    }

    return '<div id="voucher_print" style="font-family:monospace; font-size:12px; padding:10px;">'
        + reimpresionBadge
        + '<div class="text-center mb-2"><strong>SGC ARGOS</strong><br><strong>COMPROBANTE DE CONSUMO</strong></div><hr>'
        + '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
        + '<tr><td><strong>N° Comprobante</strong></td><td class="text-right">#' + voucherEsc(d.con_id) + '</td></tr>'
        + '<tr><td><strong>Fecha</strong></td><td class="text-right">' + voucherEsc(d.con_fecha) + '</td></tr>'
        + '<tr><td><strong>Hora</strong></td><td class="text-right">' + voucherEsc(d.con_hora) + '</td></tr>'
        + '<tr><td><strong>Local</strong></td><td class="text-right">' + voucherEsc(d.loc_direccion || 'N/A') + '</td></tr>'
        + '<tr><td><strong>Cajero</strong></td><td class="text-right">' + voucherEsc(d.cajero || 'N/A') + '</td></tr>'
        + '</table><hr>'
        + beneficiarioSection
        + comboSection
        + '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
        + descripcionRow
        + (parseFloat(d.con_monto_convenio) > 0 ? '<tr><td>Cargo convenio</td><td class="text-right">$' + parseFloat(d.con_monto_convenio).toFixed(2) + '</td></tr>' : '')
        + (parseFloat(d.con_monto_giftcard) > 0 ? '<tr><td>Gift Card</td><td class="text-right">$' + parseFloat(d.con_monto_giftcard).toFixed(2) + '</td></tr>' : '')
        + (parseFloat(d.con_monto_externo) > 0 ? '<tr><td>Pago externo</td><td class="text-right">$' + parseFloat(d.con_monto_externo).toFixed(2) + '</td></tr>' : '')
        + '<tr><td colspan="2"><hr style="margin:4px 0;"></td></tr>'
        + '<tr><td>Subtotal</td><td class="text-right">$' + parseFloat(d.con_valor_neto).toFixed(2) + '</td></tr>'
        + '<tr><td>IVA (' + ivaPct + '%)</td><td class="text-right">$' + parseFloat(d.con_iva || 0).toFixed(2) + '</td></tr>'
        + '<tr><td><strong>TOTAL</strong></td><td class="text-right"><strong>$' + parseFloat(d.con_valor_total).toFixed(2) + '</strong></td></tr>'
        + saldoRow
        + '</table><hr>'
        + '<div class="mt-3" style="font-size:10px;"><p class="mb-1">Firma: ______________________</p><p class="mb-1">N° Cédula: ______________________</p><p class="mb-1">N° Teléfono: __________________</p></div>'
        + '<div class="text-center mt-2" style="font-size:9px;">El comprobante firmado constituye respaldo legal del consumo</div>'
        + '</div>';
}
