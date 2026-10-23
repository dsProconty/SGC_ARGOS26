// Escape de HTML para todo texto que viene de la base (nombres, descripciones, correos...) antes de armarlo en HTML.
// Escapa también las comillas, así es seguro dentro de atributos (href="mailto:..."), no solo entre etiquetas.
// Se carga en main.php justo después de jQuery. Las pantallas llaman esc(valor).
window.esc = function (s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
};

// Para meter un texto dentro de un manejador en línea: onclick="fn('<aquí>')". Primero se escapa como texto de JavaScript
// (barra y apóstrofe) y después como HTML, que es el orden en que lo lee el navegador.
window.escJs = function (s) {
    return window.esc(String(s === null || s === undefined ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\r?\n/g, ' '));
};
