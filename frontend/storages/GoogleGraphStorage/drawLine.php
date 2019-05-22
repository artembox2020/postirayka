<?= Yii::$app->view->render($storage->storagePath.'/auxiliary_functions', ['data' => $data]) ?>

<script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawLine);

    var dataArray = makeDataForHistogram();
    var min = getMinValue(-128) - 8;

    var options = {
        curveType: 'function',
        legend: { position: 'top' },
        hAxis: {
            textStyle: {
                fontSize: 8
            }
        },
        vAxis: { 
            minValue: min,
            viewWindow: { min: min},
            viewWindowMode: 'pretty',
            baseline: -128,
            baselineColor: "black"
        }
    };

    //draws line
    function drawLine() {
        var data = google.visualization.arrayToDataTable(dataArray);

        var formatter = new google.visualization.NumberFormat({
            negativeColor: 'red', negativeParens: true, pattern: '######.##'
        });

        for (var i = 1; i < dataArray[0].length; ++i) {
            formatter.format(data, i);
        }

        var chart = new google.visualization.LineChart(document.querySelector('<?= $selector ?>'));

        google.visualization.events.addListener(chart, 'select', function () {
            var sel = chart.getSelection();
            if (sel.length > 0 && sel[0].row === null) {
                var dataArray = makeDataForHistogramByActive(sel[0].column, min - 6);
                var data = google.visualization.arrayToDataTable(dataArray);
                chart.draw(data, options);
            }
        });

        chart.draw(data, options);
        graphBuilder.isGraphBusy = false;
    }
</script>