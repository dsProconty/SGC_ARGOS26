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