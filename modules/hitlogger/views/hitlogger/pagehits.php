<script type="text/javascript">
function dynamicPlotter(datasets, choiceContainer, plotArea, config) {
	// hard-code color indices to prevent them from shifting as
	// countries are turned on/off
	var i = 0;
	$.each(datasets, function(key, val) {
		val.color = i;
		++i;
	});

	// insert checkboxes
	$.each(datasets, function(key, val) {
		choiceContainer.append('<br/><input type="checkbox" name="' + key +
							   '" checked="checked" id="id' + key + '">' +
							   '<label for="id' + key + '">'
								+ val.label + '</label>');
	});
	choiceContainer.find("input").click(plotAccordingToChoices);

	function plotAccordingToChoices() {
		var data = [];

		choiceContainer.find("input:checked").each(function () {
			var key = $(this).attr("name");
			if (key && datasets[key])
				data.push(datasets[key]);
		});

		if (data.length > 0) {
			$.plot(plotArea, data, config);
		}
	}

	plotAccordingToChoices();
}

$(function() {
<?php if (!empty($dailys)): ?>
	var datasetsDaily = {
		<?php
		$daily_data = array();
		foreach ($dailys as $uri => $daily) {
			$values = array();
			foreach ($daily['values'] as $value) {
				$values[] = "[ {$value[0]}, {$value[1]} ]";
			}
			$daily_data[] = '"/'.$uri.'": { label: "/'.$uri.'", data: [' . implode(', ', $values) . '] }';
		}
		echo implode(", \n", $daily_data);
		?>
	};

	dynamicPlotter(datasetsDaily, $("#choicesDaily"), $("#dailyplot"), {
		yaxis: { minTickSize: 1, tickDecimals:0, min:0 }
		, xaxis: { tickSize: [2, "day"], mode:"time" }
		, series: { lines: { show: true }, points: { show: true }, legend: { show: false } }
		, grid: { hoverable: true }
		, legend: { noColumns: 5, position: "nw" }
	});
<?php endif; ?>
<?php if (!empty($monthlys)): ?>
	var datasetsMonthly = {
		<?php
		$monthly_data = array();
		foreach ($monthlys as $uri => $monthly) {
			$values = array();
			foreach ($monthly['values'] as $value) {
				$values[] = "[ {$value[0]}, {$value[1]} ]";
			}
			$monthly_data[] = '"/'.$uri.'": { label: "/'.$uri.'", data: [' . implode(', ', $values) . '] }';
		}
		echo implode(", \n", $monthly_data);
		?>
	};

	dynamicPlotter(datasetsMonthly, $("#choicesMonthly"), $("#monthlyplot"), 		{
		yaxis: { minTickSize: 1, tickDecimals:0, min:0 }
		, xaxis: { tickSize: [1, "month"], mode:"time" }
		, series: { lines: { show: true }, points: { show: true } }
		, grid: { hoverable: true }
		, legend: { noColumns: 5, position: "nw" }
	});
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
</script>
<h2>Page hits</h2>
<form action="" method="post" name="plot">
<h3><a name="daily">Daily page hits</a></h3>
<input type="submit" value="Select month:" onclick="return (document.forms.plot.action='#daily');" /> <select name="month">
<?php 
foreach ($months as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $month ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<div id="dailyplot" style="width:980px;height:300px">
<?php if (empty($daily['values'])): ?>
<h4>No hits during this month.</h4>
<?php else: ?>
<h4>Error plotting the graph.</h4>
<?php endif; ?>
</div>    
<p id="choicesDaily"><span style="text-decoration: underline; font-weight: bold;">Pages:</span></p>

<h3><a name="monthly">Monthly page hits</a></h3>
<input type="submit" value="Select year:" onclick="return (document.forms.plot.action='#monthly');" /> <select name="year">
<?php 
foreach ($years as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $year ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<div id="monthlyplot" style="width:980px;height:300px">
<?php if (empty($monthly['values'])): ?>
<h4>No hits during this year.</h4>
<?php else: ?>
<h4>Error plotting the graph.</h4>
<?php endif; ?>
</div>
<p id="choicesMonthly"><span style="text-decoration: underline; font-weight: bold;">Pages:</span></p>

</form>