$(document).ready(function () {
    getTotal('PIZZA HUT','pizza_hut_')
    getTotal('FRIDAYS','fridays_')
    getTotal('OTRO','otro_')
    load_pagos()
    load_top_ten()

    // Dash-2: la lista de últimos pagos solo cargaba una vez al abrir el
    // dashboard; se refresca sola cada 60s para reflejar pagos nuevos sin
    // depender de que alguien recargue la página.
    setInterval(load_pagos, 60000)
});

// Dash-2: el botón "Descargar" era un <a href="javascript:void(0)"> sin
// ningún handler. Genera un comprobante imprimible con los datos ya
// disponibles en la fila (mismo patrón de ventana de impresión que usa
// Estado de Cuenta), sin necesitar un endpoint nuevo.
$(document).on('click', '.btn-descargar-pago', function () {
    var d = $(this).data();
    var ventana = window.open('', '_blank', 'width=500,height=600');
    ventana.document.write('<html><head><title>Comprobante de Pago - SGC ARGOS</title>');
    ventana.document.write('<style>body{font-family:Arial,sans-serif;font-size:14px;padding:24px;color:#2c2c2c;}');
    ventana.document.write('h4{color:#6d1b3a;margin-bottom:4px;}hr{border-top:2px solid #6d1b3a;}');
    ventana.document.write('table{width:100%;border-collapse:collapse;margin-top:12px;}');
    ventana.document.write('td{padding:8px 0;border-bottom:1px solid #e6d3da;}');
    ventana.document.write('.label{color:#888;font-size:12px;}');
    ventana.document.write('@media print{.no-print{display:none}}</style></head><body>');
    ventana.document.write('<h4>SGC ARGOS</h4><p>Comprobante de Pago</p><hr>');
    ventana.document.write('<table>');
    ventana.document.write('<tr><td><span class="label">Cliente</span><br>' + d.cliente + '</td></tr>');
    ventana.document.write('<tr><td><span class="label">Gestor</span><br>' + d.gestor + '</td></tr>');
    ventana.document.write('<tr><td><span class="label">Fecha</span><br>' + d.fecha + '</td></tr>');
    ventana.document.write('<tr><td><span class="label">Monto</span><br><strong>$' + parseFloat(d.monto).toFixed(2) + '</strong></td></tr>');
    ventana.document.write('</table>');
    ventana.document.write('<div class="no-print" style="text-align:right;margin-top:20px;">');
    ventana.document.write('<button onclick="window.print();" style="background:#6d1b3a;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;">Guardar / Imprimir</button></div>');
    ventana.document.write('</body></html>');
    ventana.document.close();
});

function getTotal(marca,span){
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/ventas.php?action=consumos_semana&marca="+marca,
        success: function (response) {
            $('#'+span+'semana').html(response)
        }
    });
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/ventas.php?action=consumos_mes&marca="+marca,
        success: function (response) {
            $('#'+span+'mes').html(response)
        }
    });
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/ventas.php?action=consumos_anio&marca="+marca,
        success: function (response) {
            $('#'+span+'anio').html(response)
        }
    });
}

function load_pagos(){
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/ventas.php?action=ultimos_pagos",
        success: function (response) {
            if ($.fn.DataTable.isDataTable('#recent-transaction-table')) {
                $('#recent-transaction-table').DataTable().destroy()
            }
            $('#outer_pagos').html(response)
            $('#recent-transaction-table').DataTable({
                "columnDefs": [{
                    "targets": 'no-sort',
                    "orderable": false,
                }],
                "columns": [
                    null,
                    null,
                    null,
                    null,
                    {
                        "width": "10%"
                    }]
            });
        }
    });
}

function load_top_ten(){
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/ventas.php?action=top_ten_clientes",
        success: function (response) {
            $('#sales-month-tab').html(response)
        }
    });
}