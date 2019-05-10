<!DOCTYPE HTML> 
<html> 
<head> 
<title>jQuery Column Chart</title> 
<script type="text/javascript" src="assets/script/jquery-1.11.1.min.js"></script> 
<script type="text/javascript" src="assets/script/jquery.canvasjs.min.js"></script> 
<script type="text/javascript"> 
window.onload = function(){ 
	$(".chartContainer").CanvasJSChart({ 
		title: { 
			text: "Unemployment Rate by Year in UK" 
		}, 
		axisY: { 
			title: "Unemployment Rate", 
			suffix: "%", 
			includeZero: false 
		}, 
		data: [ 
		{ 
			type: "column", 
			toolTipContent: "{label}: {y}%", 
			dataPoints: [ 
				{ label: 2000, y: 5.4 }, 
				{ label: 2001, y: 5.1 }, 
				{ label: 2002, y: 5.2 }, 
				{ label: 2003, y: 5.0 }, 
				{ label: 2004, y: 4.8 }, 
				{ label: 2005, y: 4.9 }, 
				{ label: 2006, y: 5.4 }, 
				{ label: 2007, y: 5.3 }, 
				{ label: 2008, y: 5.7 }, 
				{ label: 2009, y: 7.7 }, 
				{ label: 2010, y: 7.8 }, 
				{ label: 2011, y: 8.1 }, 
				{ label: 2012, y: 7.9 }, 
				{ label: 2013, y: 7.6 } 
			] 
		} 
		] 
	}); 
}
</script> 
</head> 
<body> 
<div class="chartContainer" style="height: 300px; width: 100%"></div> 
</body> 
</html>