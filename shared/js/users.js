$(document).ready(function () {
    load_usuarios()
});

function load_usuarios(){
    $.ajax({
        type: "GET",
        url: "ajax/users/users.php?action=list",
        success: function (response) {
            $('#loader_usuarios').html(response);
            $('#table_usuarios').dataTable();        
        }
    });
}

function bloquear_usuario(id){
    $.ajax({
        type: "GET",
        url: "pages/user/proses.php?act=off&id="+id,
        success: function (response) {
            load_usuarios();
        }
    });
}

function desbloquear_usuario(id){
    $.ajax({
        type: "GET",
        url: "pages/user/proses.php?act=on&id="+id,
        success: function (response) {
            load_usuarios();
        }
    });
}