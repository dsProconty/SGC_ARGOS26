// Impresión de vouchers de venta (POS y reimpresión desde Historial).
// Perfiles de las impresoras de ticket que usan los locales; el cajero elige
// una vez por equipo y queda guardada en el navegador (localStorage).
var VOUCHER_PERFILES = {
    t88:  { nombre: 'Epson TM-T88VII (térmica 80 mm)',  pagina: '80mm', ancho: '72mm', fuente: '11px' },
    u220: { nombre: 'Epson TM-U220 (matricial 76 mm)', pagina: '76mm', ancho: '63mm', fuente: '10px' }
};
var VOUCHER_PERFIL_DEFAULT = 't88';
var VOUCHER_STORAGE_KEY = 'sgc_voucher_impresora';

function voucherPerfilActual() {
    var clave = $('#voucher_impresora').val();
    return VOUCHER_PERFILES[clave] ? clave : VOUCHER_PERFIL_DEFAULT;
}

function voucherInitSelector() {
    var $sel = $('#voucher_impresora');
    if (!$sel.length) return;
    $.each(VOUCHER_PERFILES, function (clave, p) {
        $sel.append($('<option>').val(clave).text(p.nombre));
    });
    var guardada = null;
    try { guardada = window.localStorage.getItem(VOUCHER_STORAGE_KEY); } catch (e) {}
    $sel.val(VOUCHER_PERFILES[guardada] ? guardada : VOUCHER_PERFIL_DEFAULT);
    $sel.on('change', function () {
        try { window.localStorage.setItem(VOUCHER_STORAGE_KEY, $sel.val()); } catch (e) {}
    });
}

function voucherImprimir(contenidoHtml) {
    var perfil = VOUCHER_PERFILES[voucherPerfilActual()];
    var ventana = window.open('', '_blank', 'width=400,height=600');
    // Estilos propios, sin bootstrap.css: cargar ese archivo en una ventana en
    // blanco es lento/frágil (H-016) y está pensado para pantallas anchas, no
    // para un ticket angosto. Solo blanco y negro: la TM-U220 es matricial.
    var estilos = '<style>'
        + '@page { size: ' + perfil.pagina + ' auto; margin: 0; }'
        + '* { box-sizing: border-box; }'
        + 'html, body { margin: 0; padding: 0; }'
        + 'body { width: ' + perfil.ancho + '; margin: 0 auto; padding: 2mm 3mm; font-family: "Courier New", monospace; font-size: ' + perfil.fuente + '; color: #000; }'
        + 'table { width: 100%; border-collapse: collapse; }'
        + 'td { padding: 1px 0; vertical-align: top; }'
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
    // H-B2B-02: cerrar recién con el evento afterprint; cerrar justo después de
    // print() hacía que la ventana desapareciera antes de verse el diálogo.
    var scriptImprimir = '<script>'
        + 'window.onafterprint = function () { window.close(); };'
        + 'window.focus();'
        + 'window.print();'
        + '<\/script>';
    ventana.document.open();
    ventana.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Voucher SGC</title>' + estilos
        + '</head><body>' + contenidoHtml + scriptImprimir + '</body></html>');
    ventana.document.close();
}

$(document).ready(voucherInitSelector);
