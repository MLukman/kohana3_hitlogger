<script type="text/javascript">
//<!--
$(function() {
<?php if (!empty($daily['values'])):
		$values = array();
		foreach ($daily['values'] as $value) {
			$values[] = "[ {$value[0]}, {$value[1]} ]";
		}
	?>
	$.plot(
		$("#dailyplot"),
		[ [<?php echo implode(', ', $values); ?>] ],
		{ 
			yaxis: { minTickSize: 1, tickDecimals:0, min:0 }
			, xaxis: { tickSize: [2, "day"], mode:"time" } 
			, series: { lines: { show: true }, points: { show: true }, legend: { show: false } }
            , grid: { hoverable: true }
		}
	); 
<?php endif; ?>
<?php if (!empty($monthly['values'])):
		$values = array();
		foreach ($monthly['values'] as $value) {
			$values[] = "[ {$value[0]}, {$value[1]} ]";
		}
	?>
	$.plot(
		$("#monthlyplot"),
		[ [<?php echo implode(', ', $values); ?>] ],
		{ 
			yaxis: { minTickSize: 1, tickDecimals:0, min:0 }
			, xaxis: { tickSize: [1, "month"], mode:"time" } 
			, series: { lines: { show: true }, points: { show: true } }
            , grid: { hoverable: true }
		}
	); 
<?php endif; ?>
	
    function showTooltip(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 5,
            left: x + 5,
            border: '1px solid #fdd',
            padding: '2px',
            'background-color': '#fee',
            opacity: 0.80
        }).appendTo("body").fadeIn(200);
    }
    
    var previousPoint = null;
    var hoverHandler = function (event, pos, item) {
		if (item) {
			if (previousPoint != item.datapoint) {
				previousPoint = item.datapoint;				
				$("#tooltip").remove();
				var x = item.series.xaxis.tickFormatter(item.datapoint[0], item.series.xaxis),
					y = item.datapoint[1].toFixed(0);
		
				showTooltip(item.pageX, item.pageY,
					(item.series.label != undefined? item.series.label + " of " : "") + x + " = " + y);
			}
		}
		else {
			$("#tooltip").remove();
			previousPoint = null;            
		}
    };

    $("#dailyplot").bind("plothover", hoverHandler);
    $("#monthlyplot").bind("plothover", hoverHandler);

});
// -->
</script>
<h2>Unique visitors</h2>
<form action="" method="post" name="plot">
<h3><a name="daily">Daily unique visitors</a></h3>
<input type="submit" value="Select month:" onclick="return (document.forms.plot.action='#daily');" /> <select name="month">
<?php 
foreach ($months as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $month ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<div id="dailyplot" style="width:980px;height:300px">
<?php if (empty($daily['values'])): ?>
<h4>No visitor during this month.</h4>
<?php else: ?>
<h4>Error plotting the graph.</h4>
<?php endif; ?>
</div>    

<h3><a name="monthly">Monthly unique visitors</a></h3>
<input type="submit" value="Select year:" onclick="return (document.forms.plot.action='#monthly');" /> <select name="year">
<?php 
foreach ($years as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $year ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<div id="monthlyplot" style="width:980px;height:300px">
<?php if (empty($monthly['values'])): ?>
<h4>No visitor during this year.</h4>
<?php else: ?>
<h4>Error plotting the graph.</h4>
<?php endif; ?>
</div>
</form>