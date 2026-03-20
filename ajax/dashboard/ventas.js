$(document).ready(function () {
    getTotal('PIZZA HUT','pizza_hut_')
    getTotal('FRIDAYS','fridays_')
    load_pagos()
    load_top_ten()
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