'Use Strict';
let body = document.querySelector('body'),
    buttonChangeFullscreen = document.querySelector('#js_fullscreen');

buttonChangeFullscreen.addEventListener('click', function (event) {
    event.preventDefault();
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

//.manager_1000001, .manager_1000043 {
//             display: none;
//         }
//
//         #table_B .manager_1000043 {
//             display: contents;
//         }

function managerBlock($managerId) {
    return ['1000001', '1000043'].indexOf($managerId + '') != -1;
}

function init() {
    const plan = 20000000;
    let total = 0,
        balancePercent = 0,
        currentPercent = 0;

    let megaplan = axios.get('index.php?type=megaplan')
        .then(function (response) {
            console.log(response.data)

            if (response.data.deals_to_manager) {
                var t = 1;
                renderTemplate('#table_E',
                    response.data.deals_to_manager,
                    function (manager, i) {
                        if (managerBlock(manager['manager_id']) === true) return '';
                        i = t++;
                        return `<tr class="manager_${manager['manager_id']}">
                            <th scope ="row">${i++} </th>
                            <td>${manager['name']} </td>
                            <td>${manager['total']} % </td>
                            </tr>`;
                    });
            }
            if (response.data.manager_total) {
                var t = 1;
                renderTemplate('#table_A',
                    response.data.manager_total,
                    function (manager, i) {
                        if (managerBlock(manager['manager_id']) === true) return '';
                        i = t++;
                        let managerTotal = Math.round(manager['total']);
                        let class_add = managerTotal >= 2500000 ? 'table-success text-dark' : 'table-danger text-dark';
                        total += managerTotal;
                        return `<tr class="${class_add} manager_${manager['manager_id']}">
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${new Intl.NumberFormat('ru-RU', {
                            style: 'currency',
                            currency: 'RUB'
                        }).format(managerTotal).replace(',00', '')}</td>
                        </tr>`;
                    });
                var t = 1;
                response.data.manager_total.sort((a, b) => b['not_processed'] - a['not_processed']);
                renderTemplate('#table_B',
                    response.data.manager_total,
                    function (manager, i) {
                        if ('1000043' !== '' + manager['manager_id'] && managerBlock(manager['manager_id']) === true) return '';
                        i = t++;
                        let total = Math.round(manager['not_processed']);
                        let class_add = total > 0 ? 'table-danger text-dark' : '';
                        return `<tr class="${class_add} manager_${manager['manager_id']}">
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${manager['not_processed']}</td>
                        </tr>`;
                    })
                // console.log('total', total)
                currentPercent = Math.round((total / plan) * 100);
                balancePercent = (100 - currentPercent);

                charRenderPlan({
                    plan: 'Всего: ' + new Intl.NumberFormat('ru-RU', {
                        style: 'currency',
                        currency: 'RUB'
                    }).format(plan).replace(',00', ''),
                    labels: [
                        new Intl.NumberFormat('ru-RU', {
                            style: 'currency',
                            currency: 'RUB'
                        }).format(plan - total).replace(',00', ''),
                        new Intl.NumberFormat('ru-RU', {
                            style: 'currency',
                            currency: 'RUB'
                        }).format(total).replace(',00', '')
                    ],
                    percents: [balancePercent, currentPercent]
                })
            }

            if (response.data.manager_day_total) {
                //table_D
                var t = 1;
                renderTemplate('#table_D',
                    response.data.manager_day_total,
                    function (manager, i) {
                        if (managerBlock(manager['manager_id']) === true) return '';
                        i = t++;

                        let total = Math.round(manager['current_day_total']);
                        let class_add = total >= 80000 ? 'table-success text-dark' : 'table-danger text-dark';
                        return `<tr class="${class_add} manager_${manager['manager_id']}">
                            <th scope="row">${i++}</th>
                            <td>${manager['name']}</td>
                            <td>${new Intl.NumberFormat('ru-RU', {
                            style: 'currency',
                            currency: 'RUB'
                        }).format(total).replace(',00', '')}</td>
                        </tr>`;
                    });
            }

            document.getElementById('change_time').innerHTML = (response.data.change_file ? response.data.change_file : '');
            //managerDeals(response.data.manager_total);
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
                    let class_add = manager['count_calls'] >= 20 ? 'table-success' : 'table-danger';
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

setTimeout(function () {
    init();
}, 1000);


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

const charConfig = {
    colors: {
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
    },
    months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Нояюрь', 'Декабрь']
}

function charRenderPlan(data) {
    setTimeout(function () {
        // console.log(data.percents)
        new Chart(document.getElementById("chart_month"), {
            type: "bar",
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.plan,
                    data: data.percents,
                    fill: false,
                    backgroundColor: ["rgba(255, 99, 132, 0.7)", "rgba(75, 192, 192, 0.7)"],
                    borderColor: ["rgb(255, 99, 132)", "rgb(75, 192, 192)"],
                    borderWidth: 1,
                }],
            },

            options: {
                responsive: true,
                legend: {
                    labels: {
                        // This more specific font property overrides the global property
                        fontColor: 'white',
                        fontSize: 28
                    }
                },
                title: {
                    display: false,
                },
                tooltips: false,
                hover: false,
                animation: {
                    duration: 1,
                    onComplete: function () {
                        let chartInstance = this.chart
                        ctx = chartInstance.ctx;
                        //Chart.defaults.global.defaultFontSize
                        ctx.font = Chart.helpers.fontString(20, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                        ctx.fillStyle = 'white';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'bottom';

                        this.data.datasets.forEach(function (dataset, i) {
                            var meta = chartInstance.controller.getDatasetMeta(i);
                            meta.data.forEach(function (bar, index) {
                                var data = dataset.data[index];
                                if (index === 0) {
                                    data = 'Осталось ' + data + '%';
                                } else if (index === 1) {
                                    data = 'Выполнено ' + data + '%';
                                }
                                // console.log(data)
                                ctx.fillText(data, bar._model.x - 75, bar._model.y - 5);
                            });
                        });
                    }
                },
                scales: {
                    yAxes: [
                        {
                            ticks: {
                                fontColor: 'white',
                                min: 0,
                                max: 100,// Your absolute max value
                                callback: function (value) {
                                    return (value / this.max * 100).toFixed(0) + '%'; // convert it to percentage
                                },
                                beginAtZero: true
                            }
                        }
                    ],
                    xAxes: [{
                        ticks: {
                            fontColor: 'white',
                            fontSize: 28
                        },
                    }]
                },
            },
        });
    }, 1000);
}

/*
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