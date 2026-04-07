<?php
if (!isset($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit;
}
?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">CONFIGURACIÓN DEL SISTEMA</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active">Configuración</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <h5 class="card-header"><i class="icon dripicons-gear"></i> Parámetros del Sistema</h5>
                    <div class="card-body">
                        <div id="alerta_cfg" class="mb-3" style="display:none;"></div>
                        <div id="loader_cfg" class="text-center py-4">
                            <span class="spinner-border text-primary"></span>
                        </div>
                        <div id="form_cfg" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
var LABELS = {
    iva_porcentaje: { label: 'IVA (%)', desc: 'Porcentaje de IVA aplicado en cada transacción', tipo: 'number', min: 0, max: 100, step: 0.01 }
};

$(document).ready(function () {
    $.getJSON('ajax/configuracion/configuracion.php?action=get', function(r) {
        $('#loader_cfg').hide();
        if (!r.success) { mostrarAlerta('danger', 'Error al cargar configuración'); return; }

        var html = '';
        r.data.forEach(function(p) {
            var meta  = LABELS[p.cfg_clave] || { label: p.cfg_clave, desc: p.cfg_descripcion, tipo: 'text' };
            var attrs = '';
            if (meta.tipo === 'number') {
                attrs = 'type="number" min="' + (meta.min ?? 0) + '" max="' + (meta.max ?? 999) + '" step="' + (meta.step ?? 1) + '"';
            } else {
                attrs = 'type="text"';
            }
            html +=
                '<div class="form-group">' +
                '  <label class="font-weight-bold">' + meta.label + '</label>' +
                '  <div class="input-group">' +
                '    <input ' + attrs + ' class="form-control form-control-lg cfg-input"' +
                '           data-clave="' + p.cfg_clave + '" value="' + p.cfg_valor + '">' +
                '    <div class="input-group-append">' +
                '      <button class="btn btn-primary btn-guardar-param" data-clave="' + p.cfg_clave + '">' +
                '        <i class="icon dripicons-checkmark"></i> Guardar' +
                '      </button>' +
                '    </div>' +
                '  </div>' +
                '  <small class="text-muted">' + meta.desc + '</small>' +
                '</div>';
        });

        $('#form_cfg').html(html).show();
    });

    $(document).on('click', '.btn-guardar-param', function() {
        var clave = $(this).data('clave');
        var valor = $('[data-clave="' + clave + '"].cfg-input').val().trim();
        var btn   = $(this);

        if (valor === '') { mostrarAlerta('warning', 'El valor no puede estar vacío'); return; }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'ajax/configuracion/configuracion.php',
            type: 'POST',
            data: { action: 'save', cfg_clave: clave, cfg_valor: valor },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    mostrarAlerta('success', 'Parámetro actualizado correctamente');
                } else {
                    mostrarAlerta('danger', resp.mensaje);
                }
            },
            error: function() { mostrarAlerta('danger', 'Error de conexión'); },
            complete: function() {
                btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
            }
        });
    });

    function mostrarAlerta(tipo, msg) {
        $('#alerta_cfg').html('<div class="alert alert-' + tipo + '">' + msg + '</div>').show();
        setTimeout(function() { $('#alerta_cfg').fadeOut(); }, 3000);
    }
});
</script>
