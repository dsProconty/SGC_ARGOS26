$(document).ready(function () {
    var id_cliente = $('#id_cartera').val()
    load_datos(id_cliente);
    load_gestiones();
    load_pagos();
});

function load_datos(id) {
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=cliente&id=" + id,
        success: function (response) {
            var data = JSON.parse(response);
            $('#ciudad').val(data.cli_ciudad)
            $('#nombre_cliente').val(data.cli_descripcion)
            $('#dia_corte').val(data.cli_dia_corte)
            $('#telefono').val(data.cli_telefono)
            $('#contacto').val(data.cli_contacto)
            $('#email').val(data.cli_email)
            $('#cli_telefono').val(data.cli_telefono)
            $('#id_cliente').val(data.cli_id)
            $('#fecha_inicio').val(data.car_fecha_inicio)
            $('#fecha_fin').val(data.car_fecha_fin)
            var id_cliente = $('#id_cliente').val();
            var fecha_inicio = $('#fecha_inicio').val();
            var fecha_fin = $('#fecha_fin').val();

            $.ajax({
                type: "GET",
                url: "ajax/gestiones/gestiones.php?action=total&id=" + id_cliente + "&fecha_inicio=" + fecha_inicio + "&fecha_fin=" + fecha_fin + "&id_cartera=" + id,
                success: function (response) {
                    $('#total_deuda').val(parseFloat(response).toFixed(2))
                }
            });

            load_consumos()
        }
    });
}

function load_consumos() {
    var id_cliente = $('#id_cliente').val();
    var fecha_inicio = $('#fecha_inicio').val();
    var fecha_fin = $('#fecha_fin').val();

    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=consumos&id=" + id_cliente + "&fecha_inicio=" + fecha_inicio + "&fecha_fin=" + fecha_fin,
        success: function (response) {
            $('#outer_consumos').html(response);
            $('#table_consumos').dataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 20, 50],
                "order": [[0, "desc"]]
            });
        }
    });
}

function load_gestiones() {
    var id_cartera = $('#id_cartera').val();
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=gestiones&id_cartera=" + id_cartera,
        success: function (response) {
            $('#outer_gestiones').html(response);
            $('#table_gestiones').dataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 20, 50],
                "order": [[0, "desc"]]
            });
        }
    });
}

function ver_observacion(id) {
    $('#modal_observacion').modal('show');
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=observacion&id=" + id,
        success: function (response) {
            $('#content_observacion').html(response)
        }
    });
}

function load_pagos() {
    var id_cartera = $('#id_cartera').val();
    $.ajax({
        type: "GET",
        url: "ajax/gestiones/gestiones.php?action=pagos&id_cartera=" + id_cartera,
        success: function (response) {
            $('#outer_pagos').html(response);
            $('#table_pagos').dataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 20, 50],
                "order": [[0, "desc"]]
            });
        }
    });
}

$('#tipo_contacto').change(function (e) {
    e.preventDefault();
    if ($('#tipo_contacto').val() == 'no_contactado') {
        $("#respuesta option[value=no_contactado]").attr("selected", true);
        $('#respuesta').attr("disabled",true)
    }else{
        $('#respuesta').attr("disabled",false)
    }
});

$('#respuesta').change(function (e) {
    e.preventDefault();
    if ($('#respuesta').val() == 'pago') {
        $('#form_compromiso').hide('slow')
        $('#form_pago').show('slow')
    } else if ($('#respuesta').val() == 'compromiso') {
        $('#form_pago').hide('slow')
        $('#form_compromiso').show('slow')
    }
    else {
        $('#form_pago').hide('slow')
        $('#form_compromiso').hide('slow')
    }
});

$('#tipo_gestion').change(function (e) {
    if ($('#tipo_gestion').val() == 'correo') {
        $('#email_form').show()
        $('#telefono_contacto').hide()
    } else {
        $('#telefono_contacto').show()
        $('#email_form').hide()
    }
    e.preventDefault();

});

$('#form_gestion').submit(function (e) {
    e.preventDefault();
    var car_id = $('#id_cartera').val();
    if ($('#tipo_gestion').val() != 0) {
        if ($('#tipo_contacto').val() != 0) {
            if ($('#respuesta').val() != 0) {
                if ($('#tipo_gestion').val() == 'email' && $('#email_contacto').val() == '') {
                    alert('El email de contacto es requerido')
                } else {
                    if ($('#tipo_gestion').val() == 'telefonica' && $('#numero_contacto').val() == '') {
                        alert('El numero de contacto es requerido')
                    } else {
                        if (parseFloat($('#monto').val()) > parseFloat($('#total_deuda').val())) {
                            alert('No se puede pagar un monto mayor a la deuda')
                        } else {
                            var data = $(this).serialize();
                            $.ajax({
                                type: "POST",
                                url: "ajax/gestiones/gestiones.php?action=save&id_car=" + car_id,
                                data: data,
                                success: function (response) {
                                    if (response == 'exito') {
                                        window.location.href = '?module=gestiones&cartera=30';
                                    }
                                }
                            });
                        }
                    }
                }


            } else {
                alert('Seleccione una respuesta')
            }
        } else {
            alert('Seleccione un tipo de contacto')
        }
    } else {
        alert('Seleccione un tipo de gestión')
    }
});

$('#btn_pago').click(function (e) {
    e.preventDefault();

});