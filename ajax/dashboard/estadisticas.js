$(document).ready(function () {
    loadMarcas()
    loadSemanas()
});


function loadMarcas(marca) {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/estadisticas.php?action=consumo_marcas&marca=PIZZA HUT",
        success: function (response) {
            var dataPizzaHut = JSON.parse(response);
            for (let index = 0; index < dataPizzaHut.length; index++) {
                dataPizzaHut[index] = parseFloat(dataPizzaHut[index]);
            }
            $.ajax({
                type: "GET",
                url: "ajax/dashboard/estadisticas.php?action=consumo_marcas&marca=FRIDAYS",
                success: function (response2) {
                    var dataFridays = JSON.parse(response2)
                    for (let index = 0; index < dataFridays.length; index++) {
                        dataFridays[index] = parseFloat(dataFridays[index]);
                    }
                    if ($('#chartjs_lineChart').length) {
                        var ctx = document.getElementById('chartjs_lineChart').getContext('2d');
                        var myChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"],
                                datasets: [{
                                    label: 'Fridays',
                                    data: dataFridays,
                                    backgroundColor: "rgba(57, 154, 242,0.4)",
                                    borderColor: "rgba(57, 154, 242,0.5)",
                                    borderWidth: .6
                                }, {
                                    label: 'Pizza Hut',
                                    data: dataPizzaHut,
                                    backgroundColor: "rgba(255, 92, 117,0.4)",
                                    borderColor: "rgba(255, 92, 117,0.5)",
                                    borderWidth: .6
                                }]
                            }
                        });
                    }
                }
            });
        }
    });

}

function loadSemanas() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/estadisticas.php?action=consumo_semanas&marca=PIZZA HUT",
        success: function (response) {
            var dataPizzaHut = JSON.parse(response)
            for (let index = 0; index < dataPizzaHut.length; index++) {
                dataPizzaHut[index] = parseFloat(dataPizzaHut[index]);
            }
            $.ajax({
                type: "GET",
                url: "ajax/dashboard/estadisticas.php?action=consumo_semanas&marca=FRIDAYS",
                success: function (response) {
                    var dataFridays = JSON.parse(response)
                    for (let index = 0; index < dataFridays.length; index++) {
                        dataFridays[index] = parseFloat(dataFridays[index]);
                    }
                    if ($('#chartjs_barChart').length) {
                        var ctx = document.getElementById("chartjs_barChart").getContext('2d');
                        var myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ["1", "2", "3", "4", "1", "2", "3", "4", "1", "2", "3", "4"],
                                datasets: [{
                                    label: 'Fridays',
                                    data: dataFridays,
                                    backgroundColor: "rgba(57, 154, 242,0.4)"
                                }, {
                                    label: 'Pizza Hut',
                                    data: dataPizzaHut,
                                    backgroundColor: "rgba(255, 92, 117,0.4)"
                                }]
                            }
                        });
                    }
                }
            });
            $.ajax({
                type: "GET",
                url: "ajax/dashboard/estadisticas.php?action=meses",
                success: function (response) {
                    var data = JSON.parse(response);
                    for (let index = 1; index <= 3; index++) {
                        $('#mes'+index).html(data[index-1]);
                        
                    }
                }
            });
        }
    });
}