$(document).ready(function () {
    load_gestion('sin_gestion');
});

function load_gestion(caso){
    var cartera = $('#cartera').val();
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=list&case="+caso+"&cartera="+cartera,
        success: function (response) {
            $('#loader_'+caso).html(response);
            $('#table_'+caso).dataTable();
        }
    });
}

function ver_observacion(id){
    $('#modal_observacion').modal('show');
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=observacion&id="+id,
        success: function (response) {
            $('#content_observacion').html(response)
        }
    });
}

function confirmarPago(pag_id, car_id) {
    if (!confirm('¿Confirmar este pago? La cartera pasará a estado Cobrada.')) return;
    $.ajax({
        type: 'POST',
        url: 'ajax/gestiones/gestiones.php?action=confirmar_pago',
        data: { pag_id: pag_id, car_id: car_id },
        dataType: 'json',
        success: function(res) {
            alert(res.mensaje);
            if (res.success) load_gestion('pendiente_confirmacion');
        }
    });
}

function rechazarPago(pag_id, car_id) {
    if (!confirm('¿Rechazar este pago? La cartera volverá a estado Pendiente.')) return;
    $.ajax({
        type: 'POST',
        url: 'ajax/gestiones/gestiones.php?action=rechazar_pago',
        data: { pag_id: pag_id, car_id: car_id },
        dataType: 'json',
        success: function(res) {
            alert(res.mensaje);
            if (res.success) load_gestion('pendiente_confirmacion');
        }
    });
}