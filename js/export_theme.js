// Estilo compartido "vinotinto" para las exportaciones a Excel (ExcelJS) del
// sistema, para que coincidan con el diseño de los PDF exportados. Requiere
// que assets/vendor/exceljs/exceljs.min.js esté cargado antes que este script.
var ArgosExport = (function () {
    var VINO       = 'FF6D1B3A';
    var VINO_DARK   = 'FF4A1226';
    var STRIPE      = 'FFFAF3F6';
    var BORDER      = 'FFE6D3DA';

    function styleWorksheet(ws, totalRowIndex) {
        var headerRow = ws.getRow(1);
        headerRow.eachCell(function (cell) {
            cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: VINO } };
            cell.font      = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.alignment = { vertical: 'middle', wrap: true };
            cell.border    = {
                top: { style: 'thin', color: { argb: VINO } }, bottom: { style: 'thin', color: { argb: VINO } },
                left: { style: 'thin', color: { argb: VINO } }, right: { style: 'thin', color: { argb: VINO } }
            };
        });
        headerRow.height = 20;

        for (var r = 2; r <= ws.rowCount; r++) {
            var isTotal = (totalRowIndex && r === totalRowIndex);
            var row = ws.getRow(r);
            row.eachCell({ includeEmpty: true }, function (cell) {
                cell.font   = { name: 'Arial', size: 10, bold: !!isTotal, color: isTotal ? { argb: 'FFFFFFFF' } : { argb: 'FF2C2C2C' } };
                cell.border = {
                    top: { style: 'thin', color: { argb: BORDER } }, bottom: { style: 'thin', color: { argb: BORDER } },
                    left: { style: 'thin', color: { argb: BORDER } }, right: { style: 'thin', color: { argb: BORDER } }
                };
                if (isTotal) {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VINO_DARK } };
                } else if (r % 2 === 0) {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: STRIPE } };
                }
            });
        }

        ws.columns.forEach(function (col) {
            var maxLen = 10;
            col.eachCell({ includeEmpty: true }, function (cell) {
                var len = cell.value ? String(cell.value).length : 0;
                if (len > maxLen) maxLen = len;
            });
            col.width = Math.min(maxLen + 3, 45);
        });
    }

    function download(wb, filename) {
        wb.xlsx.writeBuffer().then(function (buffer) {
            var blob = new Blob([buffer], { type: 'application/octet-stream' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href = url; a.download = filename;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        });
    }

    // Construye una hoja a partir de un <table> del DOM (thead + tbody + tfoot
    // opcional), preservando exactamente las columnas y el orden ya presentes
    // en la tabla (incluye cualquier filtrado/limpieza que se le haya hecho
    // antes, p.ej. quitar badges o columnas de acciones).
    function tableToSheet(wb, table, sheetName) {
        var ws = wb.addWorksheet(sheetName);
        var theadRow = table.querySelector('thead tr');
        if (theadRow) {
            var headers = [];
            theadRow.querySelectorAll('th').forEach(function (th) { headers.push(th.textContent.trim()); });
            ws.addRow(headers);
        }
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var vals = [];
            tr.querySelectorAll('td').forEach(function (td) { vals.push(td.textContent.trim()); });
            if (vals.length) ws.addRow(vals);
        });
        var totalRowIndex = null;
        var tfootRow = table.querySelector('tfoot tr');
        if (tfootRow) {
            var vals2 = [];
            tfootRow.querySelectorAll('th,td').forEach(function (c) { vals2.push(c.textContent.trim()); });
            if (vals2.length) { ws.addRow(vals2); totalRowIndex = ws.rowCount; }
        }
        styleWorksheet(ws, totalRowIndex);
        return ws;
    }

    // Construye una hoja a partir de un arreglo de objetos planos; el orden de
    // columnas es el orden de las llaves del primer objeto (igual que
    // XLSX.utils.json_to_sheet).
    function rowsToSheet(wb, rows, sheetName) {
        var ws = wb.addWorksheet(sheetName);
        if (rows.length) {
            var headers = Object.keys(rows[0]);
            ws.addRow(headers);
            rows.forEach(function (r) { ws.addRow(headers.map(function (h) { return r[h]; })); });
        }
        styleWorksheet(ws, null);
        return ws;
    }

    return { tableToSheet: tableToSheet, rowsToSheet: rowsToSheet, download: download };
})();
