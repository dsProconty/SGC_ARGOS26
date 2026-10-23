<?php
require_once 'config/database.php';
require_once 'helpers/db_compat.php';
require_once 'helpers/session_helpers.php';
if (!isset($_SESSION['id_user']) || !esSuperAdmin($mysqli)) {
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

        <div class="row justify-content-center mt-4">
            <div class="col-lg-6">
                <div class="card">
                    <h5 class="card-header"><i class="icon dripicons-reload"></i> Cobranza — Cartera</h5>
                    <div class="card-body">
                        <p class="mb-2">Última actualización: <strong id="cartera_ultima">…</strong></p>
                        <p class="text-muted small">La cartera de Cobranza (30, 60, 90 y +90 días) se actualiza sola todos los días a las 5:30 (cron). Usa este botón si el cron no corrió, o en una emergencia. Recalcula con los consumos actuales y <strong>no cambia el estado de gestión</strong> (pendiente, cobrada, compromiso) de las carteras que siguen en el mismo ciclo.</p>
                        <div id="alerta_cartera" class="mb-3" style="display:none;"></div>
                        <button type="button" class="btn btn-primary" id="btn_actualizar_cartera">
                            <i class="icon dripicons-reload"></i> Actualizar cartera ahora
                        </button>
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

    // ── Cartera de Cobranza ──────────────────────────────────────────────
    function cargarUltimaCartera() {
        $.getJSON('ajax/configuracion/configuracion.php?action=cartera_estado', function(r) {
            $('#cartera_ultima').text(r.success && r.ultima ? r.ultima : 'aún no se ha actualizado');
        });
    }
    cargarUltimaCartera();

    $('#btn_actualizar_cartera').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Actualizando… no cierres esta página');
        $('#alerta_cartera').hide();
        $.ajax({
            url: 'ajax/configuracion/configuracion.php',
            type: 'POST',
            data: { action: 'actualizar_cartera' },
            dataType: 'json',
            success: function(r) {
                if (!r.success) {
                    $('#alerta_cartera').html('<div class="alert alert-danger mb-0">' + esc(r.mensaje || 'No se pudo actualizar la cartera') + '</div>').show();
                    return;
                }
                var x = r.resumen;
                $('#cartera_ultima').text(r.ultima);
                $('#alerta_cartera').html('<div class="alert alert-success mb-0">Cartera actualizada: ' + x.convenios + ' convenios, ' + x.creada + ' creadas, ' + x.ciclo_nuevo + ' con ciclo nuevo, ' + x.refrescada + ' refrescadas, ' + x.sin_consumos + ' sin consumos en la ventana.</div>').show();
            },
            error: function() {
                $('#alerta_cartera').html('<div class="alert alert-danger mb-0">Error de conexión o la actualización tardó demasiado. Revisa la fecha de última actualización y vuelve a intentar.</div>').show();
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="icon dripicons-reload"></i> Actualizar cartera ahora');
            }
        });
    });

    function mostrarAlerta(tipo, msg) {
        $('#alerta_cfg').html('<div class="alert alert-' + tipo + '">' + esc(msg) + '</div>').show();
        setTimeout(function() { $('#alerta_cfg').fadeOut(); }, 3000);
    }
});
</script>
