<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

function writeErrorLogAndDie(string $message)
{
    http_response_code(500);
    header('Content-type: text/plain');
    echo $message;
    exit(1);
}

$json               = null;
$backLink           = null;
$nextLink           = null;
$nearTimeLinkFormat = null;

function main()
{
    global $json;
    global $backLink;
    global $nextLink;
    global $nearTimeLinkFormat;

    $backCount = 0;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_GET,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_GET,
            'cpu_serial_number'
        );

        $backCount = ApacheEnvironmentWrapper::getIntValueByParams(
            $_GET,
            'back_count'
        );
    } catch (OutOfBoundsException $e) {
        writeErrorLogAndDie(
            'OutOfBoundsException: ' .
            $e->getMessage()
        );
    } catch (UnexpectedValueException $e) {
        writeErrorLogAndDie(
            'UnexpectedValueException: ' .
            $e->getMessage()
        );
    }

    $pdo = null;

    try {
        $pdo = DBCommon::createConnection();
    } catch (PDOException $e) {
        writeErrorLogAndDie(
            'PDOException in createConnection: ' .
            $e->getMessage()
        );
    }

    $photostandId = null;

    try {
        $photostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (RuntimeException $e) {
        writeErrorLogAndDie(
            'RuntimeException in Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        writeErrorLogAndDie(
            'RangeException in Authorization: ' .
            $e->getMessage()
        );
    }

    $sensorDataArray = DBCSensorData::getSensorDataArrayByRange(
        $pdo,
        $photostandId,
        ($backCount + 1) * 12,
        $backCount * 12
    );

    $json = json_encode($sensorDataArray);

    $nearTimeLinkFormat = "'./images.php" .
        "?password=$password" .
        "&cpu_serial_number=$cpuSerialNumber" .
        "&datetime=' + escapedCreatedAt";

    $backLinkBackCount = $backCount + 1;
    $backLink = './graph.php' .
        "?password=$password" . 
        "&cpu_serial_number=$cpuSerialNumber" .
        "&back_count=$backLinkBackCount";

    $nextLinkBackCount = $backCount - 1;
    if ($nextLinkBackCount >= 0) {
        $nextLink = './graph.php' .
            "?password=$password" . 
            "&cpu_serial_number=$cpuSerialNumber" .
            "&back_count=$nextLinkBackCount";
    }
}

main();

?>

<!DOCTYPE HTML>
<html lang=ja>
    <head>
        <meta charset=utf-8>
        <title>Graph</title>
        <meta name=viewport content="width=device-width, initial-scale=1.0">
        <link rel=stylesheet crossorigin=anonymous integrity="sha256-Q0zCrUs2IfXWYx0uMKJfG93CvF6oVII21waYsAV4/8Q=" href=https://cdnjs.cloudflare.com/ajax/libs/pure/1.0.0/pure-min.css>

        <!--[if lte IE 8]>
            <link rel=stylesheet crossorigin=anonymous integrity="sha256-r/sKuPk30/v587KhP6Bo+6jx9gpKQKHoGuxcA6FBhJo=" href=https://cdnjs.cloudflare.com/ajax/libs/pure/1.0.0/grids-responsive-old-ie-min.css>
        <![endif]-->
        <!--[if gt IE 8]><!-->
            <link rel=stylesheet crossorigin=anonymous integrity="sha256-YqnnS/cQ7vE7gfVjdfx+JMi5EFD6m6Zqdemj81rs6PU=" href=https://cdnjs.cloudflare.com/ajax/libs/pure/1.0.0/grids-responsive-min.css>
        <!--<![endif]-->
    </head>
    <body>
        <h1>Graph</h1>
        <a class="pure-button pure-button-primary" href=<?php echo $backLink; ?>>&lt;&#61;</a>
        <?php if ($nextLink == null) { ?>
            <a class="pure-button pure-button-primary" disabled>&#61;&gt;</a>
        <?php } else { ?>
            <a class="pure-button pure-button-primary" href=<?php echo $nextLink; ?>>&#61;&gt;</a>
        <?php } ?>
        <div class="pure-g">
            <div class="pure-u-1 pure-u-lg-1-2">
                <canvas id=basicChart></canvas>
            </div>
            <div class="pure-u-1 pure-u-lg-1-2">
                <canvas id=cdsChart></canvas>
            </div>
            <div class="pure-u-1 pure-u-lg-1-2">
                <canvas id=tempChart></canvas>
            </div>
        </div>
        <script crossorigin=anonymous integrity="sha256-OI3N9zCKabDov2rZFzl8lJUXCcP7EmsGcGoP6DMXQCo=" src=https://cdnjs.cloudflare.com/ajax/libs/es6-promise/4.1.1/es6-promise.auto.min.js></script>
        <script crossorigin=anonymous integrity="sha256-zG8v+NWiZxmjNi+CvUYnZwKtHzFtdO8cAKUIdB8+U9I=" src=https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.1/moment.min.js></script>
        <script crossorigin=anonymous integrity="sha256-VX6SyoDzanqBxHY3YQyaYB/R7t5TpgjF4ZvotrViKAY=" src=https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.21/moment-timezone-with-data.min.js></script>
        <script crossorigin=anonymous integrity="sha256-CfcERD4Ov4+lKbWbYqXD6aFM9M51gN4GUEtDhkWABMo=" src=https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js></script>
        <script>
            const SENSOR_DATA           = <?php echo $json; ?>;
            const SENSOR_DATA_LENGTH    = SENSOR_DATA.length;
            const BASE_OPTIONS = {
                animation: {
                    duration: 0,
                },
                hover: {
                    mode: false,
                },
                tooltips: {
                    enabled: false,
                    mode: 'index',
                    axis: 'x',
                    intersect: false,
                },
                responsiveAnimationDuration: 0,
                elements: {
                    line: {
                        tension: 0,
                    },
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            displayFormats: {
                                quarter: 'h:mm',
                            },
                        },
                    }],
                    yAxes: [],
                },
            };

            // IE が Arrow function をサポートしていないせいで function な行
            const fakeDeepClone = function(obj) {
                return JSON.parse(JSON.stringify(obj));
            };

            const getDatasByColumnName = function(columnName) {
                const returnArray = new Array(SENSOR_DATA_LENGTH);

                for(let i = 0; i < SENSOR_DATA_LENGTH; i++)
                    returnArray[i] = {
                        't': SENSOR_DATA[i]['created_at'],
                        'y': SENSOR_DATA[i][columnName],
                    };

                return returnArray;
            };

            const apply2element = function(e, data, options){
                return new Chart(
                    e.getContext('2d'),
                    {
                        type    : 'bar',
                        data    : data,
                        options : options
                    }
                );
            };

            const drawBasicSensorsChart = new Promise(function(resolve, reject){
                const data = {
                    datasets: [
                        {
                            type: 'line',
                            backgroundColor: 'rgba(0,192,255,0.5)',
                            borderColor : 'rgba(0,192,255,0.5)',
                            fill: false,
                            label: 'Infrared sensor',
                            yAxisID: 'y-axes-1',
                        },{
                            type: 'line',
                            backgrounColor: 'rgba(106,0,255,0.75)',
                            borderColor: 'rgba(106,0,255,0.75)',
                            fill: false,
                            label: 'Ultrasonic sensor',
                            yAxisID: 'y-axes-1',
                        },{
                            type: 'line',
                            backgroundColor: 'rgba(136,0,153,0.5)',
                            borderColor: 'rgba(136,0,153,0.3)',
                            fill: false,
                            label: 'Pyroelectric sensor',
                            yAxisID: 'y-axes-2',
                        },
                    ],
                };

                const getInfraredSensorData = new Promise(function(resolve, reject){
                    resolve(getDatasByColumnName('infrared_centimetear'));
                });

                const getUltrasonicSensorData = new Promise(function(resolve, reject){
                    resolve(getDatasByColumnName('ultrasonic_centimetear'));
                });

                const getPyroelectricSensorData = new Promise(function(resolve, reject){
                    resolve(getDatasByColumnName('pyroelectric'));
                });

                const options = fakeDeepClone(BASE_OPTIONS);

                options.scales.yAxes = [
                    {
                        id: 'y-axes-1',
                        position: 'left',
                    },{
                        id: 'y-axes-2',
                        position: 'right',
                        gridLines: {
                            drawOnChartArea: false,
                        },
                    },
                ];

                Promise.all([
                    getInfraredSensorData,
                    getUltrasonicSensorData,
                    getPyroelectricSensorData,
                ]).then(function(values){
                    data.datasets[0].data = values[0];
                    data.datasets[1].data = values[1];
                    data.datasets[2].data = values[2];

                    const element = document.getElementById('basicChart');
                    const chart = apply2element(element, data, options);

                    element.addEventListener('click', function(e) {
                        const escapedCreatedAt = encodeURIComponent(
                            SENSOR_DATA[
                                chart.getElementsAtEvent(e)[0]._index
                            ]['created_at']
                        );

                        location.href = <?php echo $nearTimeLinkFormat; ?>;
                    });

                    resolve(chart);
                });
            });

            const drawBrightnessChart = new Promise(function(resolve, reject){
                const data = {
                    datasets: [
                        {
                            type: 'line',
                            backgroundColor: '#f60',
                            borderColor: '#f60',
                            fill: false,
                            label: 'Brightness Sensor',
                            data: getDatasByColumnName('cds_lux'),
                        },
                    ],
                };

                const options = fakeDeepClone(BASE_OPTIONS);

                options.scales.yAxes[0] = {type: 'logarithmic'};

                const element = document.getElementById('cdsChart');
                console.log(element, data, options)
                const chart = apply2element(element, data, options);

                resolve();
            });

            const drawTemperatureChart = new Promise(function(resolve, reject){
                const data = {
                    datasets: [
                        {
                            type: 'line',
                            backgroundColor: '#f60',
                            borderColor: '#f60',
                            fill: false,
                            label: 'Temperature Sensor',
                            data: getDatasByColumnName('temperature_celsius'),
                        },
                    ],
                };

                const element = document.getElementById('tempChart');
                const chart = apply2element(element, data, BASE_OPTIONS);

                resolve();
            });

            Promise.all([
                drawBasicSensorsChart,
                drawBrightnessChart,
                drawTemperatureChart,
            ]).then(function(values){
                console.log("Done!");
            });
        </script>
    </body>
</html>

