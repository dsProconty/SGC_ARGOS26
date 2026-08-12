// Lógica compartida para renderizar y leer inputs de cupo por marca, usada
// tanto en el modal de Editar Empleado (Clientes, admin) como en los modales
// de Nuevo/Editar Empleado (Portal Empresa, autoservicio). Ambas páginas
// llaman a sus propias funciones (renderCupoMarcaInputs/leerCupoMarcaInputs
// en Portal Empresa, renderCupoPorMarcaInputsGenerico/leerCupoPorMarcaInputsGenerico
// en Clientes) que delegan aquí — esto centraliza la lógica sin tener que
// tocar ningún punto de llamada existente en ninguna de las dos páginas.
function cupoMarcaRenderInputs(containerSelector, cssClass, porMarca, valoresActuales) {
    valoresActuales = valoresActuales || {};
    var html = '';
    (porMarca || []).forEach(function (m) {
        var valor = valoresActuales[m.mar_id] || '';
        html += '<div class="col-md-6 mb-2">'
            + '<label class="small mb-1">' + esc(m.mar_descripcion) + ' <span class="text-muted">(máx. $' + parseFloat(m.monto_max).toFixed(2) + ')</span></label>'
            + '<div class="input-group input-group-sm">'
            + '<div class="input-group-prepend"><span class="input-group-text">$</span></div>'
            + '<input type="number" class="form-control ' + cssClass + '" data-mar-id="' + m.mar_id + '" min="0" step="0.01" value="' + valor + '" placeholder="0.00">'
            + '</div></div>';
    });
    $(containerSelector).html(html);
}

function cupoMarcaLeerInputs(containerSelector, cssClass) {
    var out = {};
    $(containerSelector + ' .' + cssClass).each(function () {
        var marId = $(this).data('mar-id');
        var val   = parseFloat($(this).val());
        if (val > 0) out[marId] = val;
    });
    return out;
}
