'Use Strict';
let body = document.querySelector('body'),
    buttonChangeFullscreen = document.querySelector('#js_fullscreen');

buttonChangeFullscreen.addEventListener('click', function (event) {
    event.preventDefault();
    console.log(this.getAttribute('data-full'))
    if (this.getAttribute('data-full') == 'on') {
        cancelFullscreen();
    } else {
        launchFullScreen(body);
    }
});

body.addEventListener('webkitfullscreenchange', onfullscreenchange);
body.addEventListener('mozfullscreenchange', onfullscreenchange);
body.addEventListener('fullscreenchange', onfullscreenchange);

function onfullscreenchange() {
    var fullscreenElement = document.fullscreenElement || document.mozFullscreenElement || document.webkitFullscreenElement;

    if (fullscreenElement) {
        buttonChangeFullscreen.innerText = 'Свернуть';
        buttonChangeFullscreen.setAttribute('data-full', 'on')
    } else {
        buttonChangeFullscreen.innerText = 'Развернуть';
        buttonChangeFullscreen.setAttribute('data-full', 'off')
    }
}

function launchFullScreen(element) {
    if (element.requestFullScreen) {
        element.requestFullScreen();
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else if (element.webkitRequestFullScreen) {
        element.webkitRequestFullScreen();
    }
}

// Выход из полноэкранного режима
function cancelFullscreen() {
    if (document.cancelFullScreen) {
        document.cancelFullScreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitCancelFullScreen) {
        document.webkitCancelFullScreen();
    }
}

function init() {
    let megaplan = axios.get('index.php?type=megaplan')
        .then(function (response) {
            //managerDeals(response.data.manager_total);
            renderTemplate('#table_A',
                response.data.manager_total,
                function (manager, i) {
                    let total = Math.round(manager['total']);
                    let class_add = total > 2000150 ? 'table-success text-dark' : '';
                    return `<tr class="${class_add} manager_${manager['manager_id']}">
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${new Intl.NumberFormat('ru-RU', {
                        style: 'currency',
                        currency: 'RUB'
                    }).format(total).replace(',00', '')}</td>
                        </tr>`;
                });

            response.data.manager_total.sort((a, b) => b['not_processed'] - a['not_processed']);
            renderTemplate('#table_B',
                response.data.manager_total,
                function (manager, i) {
                    return `<tr>
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${manager['not_processed']}</td>
                        </tr>`;
                })
            return 'megaplan-ok';
        })
        .catch(function (error) {
            console.log(error);
        });

    let mango = axios.get('index.php?type=mangooffice')
        .then(function (response) {

            renderTemplate('#table_C',
                response.data.users,
                function (manager, i) {
                    let class_add = manager['count_calls'] >= 15 ? 'table-success' : 'table-danger';
                    return `<tr class="${class_add} text-dark">
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${manager['count_calls']}</td>
                        </tr>`;
                })
            return 'mangooffice-ok';
        })
        .catch(function (error) {
            console.log(error);
        });

    Promise.all([megaplan, mango]).then((result) => {
        console.log(result)
        setTimeout(function () {
            init();
        }, 600000);
    })
}

init();


function renderTemplate(selector, managersData, callback) {
    let element = document.querySelector(selector);
    let table = '';
    let i = 1;
    for (let manager in managersData) {
        table += callback(managersData[manager], i);
        i++;
    }
    element.innerHTML = table;
}

function timeFormat(seconds) {
    var hours = Math.floor(seconds / 60 / 60);
    var minutes = Math.floor(seconds / 60) - (hours * 60);
    var seconds = seconds % 60;
    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        seconds.toString().padStart(2, '0')
    ].join(':');
}

/*
const config = {
    chartColors: {
        red: 'rgb(255, 99, 132)',
        orange: 'rgb(255, 159, 64)',
        yellow: 'rgb(255, 205, 86)',
        green: 'rgb(75, 192, 192)',
        blue: 'rgb(54, 162, 235)',
        purple: 'rgb(153, 102, 255)',
        grey: 'rgb(201, 203, 207)'
    },
    megaplan: {
        labels: [],
        total: [],
        count: []
    },
    mango: {
        labels: [],
        total: [],
    }
}

function addData(chart, label, data) {
    chart.data.labels.push(label);
    chart.data.datasets.forEach((dataset) => {
        dataset.data.push(data);
    });
    chart.update();
}

function removeData(chart) {
    chart.data.labels.pop();
    chart.data.datasets.forEach((dataset) => {
        dataset.data.pop();
    });
    chart.update();
}



const tableManagers = document.getElementById('table_managers'), tableCall = document.getElementById('table_call');

function managerDeals(managersData) {
    let table = '';
    let i = 1;
    for (let manager in managersData) {
        //console.log(managersData[manager])

        config.megaplan.labels.push(managersData[manager]['name'])
        config.megaplan.total.push(Math.round(managersData[manager]['total']));
        config.megaplan.count.push(managersData[manager]['deal_count']);

        table += `<tr>
                    <th scope="row">${i++}</th>
                    <td>${managersData[manager]['name']}</td>
                    <td>${managersData[manager]['deal_count']}</td>
                    <td>${Math.round(managersData[manager]['total'])}</td>
                </tr>`
    }
    tableManagers.innerHTML = table;

    const ctx = document.getElementById('managersChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: config.megaplan.labels,
            datasets: [{
                //yAxisID: 'A',
                label: 'Сумма',
                data: config.megaplan.total,
                backgroundColor: [
                    config.chartColors.red,
                    config.chartColors.orange,
                    config.chartColors.yellow,
                    config.chartColors.green,
                    config.chartColors.blue,
                    config.chartColors.purple,
                    config.chartColors.grey,
                ],
            }, {
                //yAxisID: 'B',
                label: 'Кол-во',
                data: config.megaplan.count,
                backgroundColor: 'rgba(53,81,103,1)',
                borderColor: 'rgba(53,81,103,.4)',
                minBarLength: 1,
            }]
        },
        options: {
            layout: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 20,
                    bottom: 0
                }
            },
            legend: {
                display: true,
                position: 'top',
            },
            hover: {
                animationDuration: 0
            },
            scales: {
                xAxes: [{}],
                yAxes: [{}]
            },
            animation: {
                duration: 1,
                onComplete: function () {
                    let chartInstance = this.chart,
                        ctx = chartInstance.ctx;
                    ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'center';

                    this.data.datasets.forEach(function (dataset, i) {
                        let meta = chartInstance.controller.getDatasetMeta(i);
                        meta.data.forEach(function (bar, index) {
                            let data = dataset.data[index];
                            ctx.fillText(data, bar._model.x + 30, bar._model.y);
                        });
                    });
                }
            },
        }
    });
}

function managerCall(managersData) {
    let table = '';
    let i = 1;

    for (let manager in managersData) {
        config.mango.labels.push(managersData[manager]['name'])
        config.mango.total.push(Math.round(managersData[manager]['count_calls']));
        // data.count.push(managersData[manager]['deal_count']);
        table += `<tr>
                    <th scope="row">${i++}</th>
                    <td>${managersData[manager]['name']}</td>
                    <td>${managersData[manager]['count_calls']}</td>
                    <td>${(managersData[manager]['time_answer'] / managersData[manager]['count_calls']).toFixed(2)}</td>
                    <td>${timeFormat(managersData[manager]['time_calls'])}</td>
                </tr>`
    }
    tableCall.innerHTML = table;

    const ctx = document.getElementById('managersCall').getContext('2d');
    ctx.height = 500;
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: config.mango.labels,
            datasets: [{
                label: 'Кол-во звонков',
                data: config.mango.total,
                backgroundColor: [
                    config.chartColors.red,
                    config.chartColors.orange,
                    config.chartColors.yellow,
                    config.chartColors.green,
                    config.chartColors.blue,
                    config.chartColors.purple,
                    config.chartColors.grey,
                ],
            }]
        },
        options: {
            layout: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 50,
                    bottom: 0
                }
            },
            legend: {
                display: true,
                position: 'top',
            },
            hover: {
                animationDuration: 0
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    },
                }],
            },
            animation: {
                duration: 1,
                onComplete: function () {
                    let chartInstance = this.chart,
                        ctx = chartInstance.ctx;
                    ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    this.data.datasets.forEach(function (dataset, i) {
                        let meta = chartInstance.controller.getDatasetMeta(i);
                        meta.data.forEach(function (bar, index) {
                            let data = dataset.data[index];
                            ctx.fillText(data, bar._model.x, bar._model.y - 5);
                        });
                    });
                }
            },
        }
    });
}
*/
// setTimeout(function () {
//     location.reload();
// }, 900000);