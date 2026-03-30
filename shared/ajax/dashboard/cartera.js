const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$(document).ready(function () {
    getGestiones()
    load_cartera30()
    load_cartera60()
    load_cartera90()
    load_cartera91()
    getCarteraMes()
    PieCartera()
});



function getGestiones() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=total_cartera",
        success: function (response) {
            var total_cartera = response;
            $.ajax({
                type: "GET",
                url: "ajax/dashboard/cartera.php?action=gestiones",
                success: function (response) {
                    var data = JSON.parse(response)
                    var per_espera = (data.sin_gestiones * 100) / total_cartera;
                    $('#sin_gestion_porcentaje').attr('data-count', per_espera)
                    $('#sin_gestion').attr('data-count', data.sin_gestiones)
                    $('#exitosas').attr('data-count', data.exitosas)
                    $('#negativas').attr('data-count', data.negativas)
                    $('#pendientes').attr('data-count', data.pendientes)
                    $.ajax({
                        type: "GET",
                        url: "ajax/dashboard/cartera.php?action=total_gestiones",
                        success: function (response) {
                            $('#exitosas_porcentaje').attr('data-count', (data.exitosas * 100) / response)
                            $('#negativas_porcentaje').attr('data-count', (data.negativas * 100) / response)
                            $('#pendientes_porcentaje').attr('data-count', (data.pendientes * 100) / response)


                            $(".progress-active-sessions .progress-bar").animate({
                                width: $('#exitosas_porcentaje').attr('data-count') + '%'
                            }, 400);
                            $(".progress-add-to-cart .progress-bar").animate({
                                width: $('#sin_gestion_porcentaje').attr('data-count') + '%'
                            }, 400);
                            $(".progress-new-account .progress-bar").animate({
                                width: $('#negativas_porcentaje').attr('data-count') + '%'
                            }, 400);
                            $(".progress-total-revenue .progress-bar").animate({
                                width: $('#pendientes_porcentaje').attr('data-count') + '%'
                            }, 400);

                            load_data()

                        }
                    });


                }
            });
        }
    });

}

function load_data() {
    if ($('.counter').length > 0) {
        $.each($('.counter'), function () {
            var count = $(this).data('count'),
                numAnim = new CountUp(this, 0, count);
            numAnim.start();
        });
    }
}

function load_cartera30() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=recuperado_anual&cartera=30",
        success: function (response) {
            let total_recuperdo = 0;
            var datos = JSON.parse(response);
            for (let index = 0; index < datos.length; index++) {
                datos[index] = parseFloat(datos[index]);
                total_recuperdo += datos[index]
            }
            $('#recuperado_30').html('$ ' + total_recuperdo)
            if ($('#usersChart').length == 0) {
                return;
            }
            var ctx = document.getElementById("usersChart").getContext("2d");
            var gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, Chart.helpers.color(QuantumPro.APP_COLORS.info).alpha(0.9).rgbString());
            gradient.addColorStop(1, Chart.helpers.color('#ffffff').alpha(0).rgbString());
            var config = {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: "Dinero Recuperado",
                        backgroundColor: gradient,
                        borderWidth: 2,
                        borderColor: QuantumPro.APP_COLORS.info,
                        pointBackgroundColor: Chart.helpers.color(QuantumPro.APP_COLORS.info).alpha(1).rgbString(),
                        pointBorderColor: Chart.helpers.color('#ffffff').alpha(0).rgbString(),
                        pointHoverBackgroundColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        pointHoverBorderColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        data: datos
                    }]
                },
                options: {
                    title: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'nearest',
                        intersect: false,
                        position: 'nearest',
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Mes'
                            }
                        }],
                        yAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Valor'
                            },
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    elements: {
                        line: {
                            tension: 0.000001
                        },
                        point: {
                            radius: 4,
                            borderWidth: 8
                        }
                    },
                    layout: {
                        padding: {
                            left: 0,
                            right: 0,
                            top: 50,
                            bottom: 0
                        }
                    }
                }
            };

            var chart = new Chart(ctx, config);
        }
    });

}

function load_cartera60() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=recuperado_anual&cartera=60",
        success: function (response) {
            let total_recuperdo = 0;
            var datos = JSON.parse(response);
            for (let index = 0; index < datos.length; index++) {
                datos[index] = parseFloat(datos[index]);
                total_recuperdo += datos[index]
            }
            $('#recuperado_60').html('$ ' + total_recuperdo)
            if ($('#bounceRateChart').length == 0) {
                return;
            }
            var ctx = document.getElementById("bounceRateChart").getContext("2d");
            var gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, Chart.helpers.color(QuantumPro.APP_COLORS.warning).alpha(0.9).rgbString());
            gradient.addColorStop(1, Chart.helpers.color('#ffffff').alpha(0).rgbString());
            var config = {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: "Bounce Rate",
                        backgroundColor: gradient,
                        borderWidth: 2,
                        borderColor: QuantumPro.APP_COLORS.warning,
                        pointBackgroundColor: Chart.helpers.color(QuantumPro.APP_COLORS.warning).alpha(1).rgbString(),
                        pointBorderColor: Chart.helpers.color('#ffffff').alpha(0).rgbString(),
                        pointHoverBackgroundColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        pointHoverBorderColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        data: datos
                    }]
                },
                options: {
                    title: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'nearest',
                        intersect: false,
                        position: 'nearest',
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Month'
                            }
                        }],
                        yAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Value'
                            },
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    elements: {
                        line: {
                            tension: 0.000001
                        },
                        point: {
                            radius: 4,
                            borderWidth: 8
                        }
                    },
                    layout: {
                        padding: {
                            left: 0,
                            right: 0,
                            top: 50,
                            bottom: 0
                        }
                    }
                }
            };

            var chart = new Chart(ctx, config);
        }
    });

}

function load_cartera90() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=recuperado_anual&cartera=90",
        success: function (response) {
            let total_recuperdo = 0;
            var datos = JSON.parse(response);
            for (let index = 0; index < datos.length; index++) {
                datos[index] = parseFloat(datos[index]);
                total_recuperdo += datos[index]
            }
            $('#recuperado_90').html('$ ' + total_recuperdo)
            if ($('#sessionDuration').length == 0) {
                return;
            }
            var ctx = document.getElementById("sessionDuration").getContext("2d");
            var gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, Chart.helpers.color(QuantumPro.APP_COLORS.primary).alpha(0.9).rgbString());
            gradient.addColorStop(1, Chart.helpers.color('#ffffff').alpha(0).rgbString());
            var config = {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: "Session Duration",
                        backgroundColor: gradient,
                        borderWidth: 2,
                        borderColor: QuantumPro.APP_COLORS.primary,
                        pointBackgroundColor: Chart.helpers.color(QuantumPro.APP_COLORS.primary).alpha(1).rgbString(),
                        pointBorderColor: Chart.helpers.color('#ffffff').alpha(0).rgbString(),
                        pointHoverBackgroundColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        pointHoverBorderColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        data: datos
                    }]
                },
                options: {
                    title: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'nearest',
                        intersect: false,
                        position: 'nearest',
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Month'
                            }
                        }],
                        yAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Value'
                            },
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    elements: {
                        line: {
                            tension: 0.000001
                        },
                        point: {
                            radius: 4,
                            borderWidth: 8
                        }
                    },
                    layout: {
                        padding: {
                            left: 0,
                            right: 0,
                            top: 50,
                            bottom: 0
                        }
                    }
                }
            };

            var chart = new Chart(ctx, config);
        }

    });
}

function load_cartera91() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=recuperado_anual&cartera=91",
        success: function (response) {
            let total_recuperdo = 0;
            var datos = JSON.parse(response);
            for (let index = 0; index < datos.length; index++) {
                datos[index] = parseFloat(datos[index]);
                total_recuperdo += datos[index]
            }
            $('#recuperado_91').html('$ ' + total_recuperdo)
            if ($('#cartera_90').length == 0) {
                return;
            }
            var ctx = document.getElementById("cartera_90").getContext("2d");
            var gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, Chart.helpers.color(QuantumPro.APP_COLORS.success).alpha(0.9).rgbString());
            gradient.addColorStop(1, Chart.helpers.color('#ffffff').alpha(0).rgbString());
            var config = {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: "User Accounts",
                        backgroundColor: gradient,
                        borderWidth: 2,
                        borderColor: QuantumPro.APP_COLORS.success,
                        pointBackgroundColor: Chart.helpers.color(QuantumPro.APP_COLORS.success).alpha(1).rgbString(),
                        pointBorderColor: Chart.helpers.color('#ffffff').alpha(0).rgbString(),
                        pointHoverBackgroundColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        pointHoverBorderColor: Chart.helpers.color('#ffffff').alpha(0.1).rgbString(),
                        data: datos
                    }]
                },
                options: {
                    title: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'nearest',
                        intersect: false,
                        position: 'nearest',
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Month'
                            }
                        }],
                        yAxes: [{
                            display: false,
                            gridLines: false,
                            scaleLabel: {
                                display: true,
                                labelString: 'Value'
                            },
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    elements: {
                        line: {
                            tension: 0.000001
                        },
                        point: {
                            radius: 4,
                            borderWidth: 8
                        }
                    },
                    layout: {
                        padding: {
                            left: 0,
                            right: 0,
                            top: 50,
                            bottom: 0
                        }
                    }
                }
            };

            var chart = new Chart(ctx, config);
        }
    });

}

function getCarteraMes() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=cartera_mes",
        success: function (response) {
            var data = JSON.parse(response);
            let total = 0;
            data.forEach(element => {
                total += parseFloat(element);
            });
            
            $('#valor_cartera_30').attr('data-count', data[0])
            $('#valor_cartera_60').attr('data-count', data[1])
            $('#valor_cartera_90').attr('data-count', data[2])
            $('#valor_cartera_91').attr('data-count', data[3])

            $('#valor_cartera_30').html(data[0])
            $('#valor_cartera_60').html(data[1])
            $('#valor_cartera_90').html(data[2])
            $('#valor_cartera_91').html(data[3])

            $('#valor_cartera_30_porcentaje').attr('data-count', (data[0] * 100) / total)
            $('#valor_cartera_60_porcentaje').attr('data-count', (data[1] * 100) / total)
            $('#valor_cartera_90_porcentaje').attr('data-count', (data[2] * 100) / total)
            $('#valor_cartera_91_porcentaje').attr('data-count', (data[3] * 100) / total)

            $(".progress-cartera-30 .progress-bar").animate({
                width: $('#valor_cartera_30_porcentaje').attr('data-count') + '%'
            }, 400);
            $(".progress-cartera-60 .progress-bar").animate({
                width: $('#valor_cartera_60_porcentaje').attr('data-count') + '%'
            }, 400);
            $(".progress-cartera-90 .progress-bar").animate({
                width: $('#valor_cartera_90_porcentaje').attr('data-count') + '%'
            }, 400);
            $(".progress-cartera-91 .progress-bar").animate({
                width: $('#valor_cartera_91_porcentaje').attr('data-count') + '%'
            }, 400);

            

        }
    });
}

function PieCartera() {
    $.ajax({
        type: "GET",
        url: "ajax/dashboard/cartera.php?action=cartera_porcentaje",
        success: function (response) {
            var datos = JSON.parse(response);
            $('#por_cobrar').html(datos.Cartera)
            $('#cobrado').html(datos.Pagos)
            var chart = c3.generate({
                bindto: "#total-revenue",
                data: {
                    columns: [
                        ["Cobrado", datos.Pagos],
                        ["Por Cobrar", datos.Cartera]
                    ],

                    type: "donut"
                },
                donut: {
                    label: {
                        show: false
                    },
                    title: "Cartera Total",
                    width: 30
                },

                legend: {
                    hide: false
                },
                color: {
                    pattern: [
                        QuantumPro.APP_COLORS.info,
                        QuantumPro.APP_COLORS.accent
                    ]
                }
            });
        }
    });

}