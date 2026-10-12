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
