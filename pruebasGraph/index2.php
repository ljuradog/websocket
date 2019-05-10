<!DOCTYPE HTML>
<html>
<head>
<script>
window.onload = function () {

var options = {
	title: {
		text: "Number of Active Users in Website"
	},

	data: [{
		type: "column",
		yValueFormatString: "#,###",
		indexLabel: "{y}",
      	color: "#546BC1",
		dataPoints: [
			{ label: "Home", y: 196 },
			{ label: "Gallery", y: 263 },
			{ label: "Dashboards", y: 134 },
			{ label: "Docs", y: 216 },
			{ label: "Support", y: 174 },
			{ label: "Blog", y: 122 },
			{ label: "Others", y: 182 }
		]
	}]
};
$("#chartContainer").CanvasJSChart(options);

function updateChart() {
	var performance, deltaY, yVal;
	var dps = options.data[0].dataPoints;
	for (var i = 0; i < dps.length; i++) {
		deltaY = Math.round(2 + Math.random() * (-2 - 2));
		yVal = deltaY + dps[i].y > 0 ? dps[i].y + deltaY : 0;
		dps[i].y = yVal;
	}
	options.data[0].dataPoints = dps;
	$("#chartContainer").CanvasJSChart().render();
};
updateChart();

setInterval(function () { updateChart() }, 500);

}
</script>
</head>
<body>
<div id="chartContainer" style="height: 300px; width: 100%;"></div>
<script src="assets/script/jquery-1.11.1.min.js"></script>
<script src="assets/script/jquery.canvasjs.min.js"></script>
</body>
</html>