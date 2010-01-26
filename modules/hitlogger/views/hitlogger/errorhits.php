<script type="text/javascript" src="<?php echo $filepaths['treeview']; ?>"></script>
<script type="text/javascript">
$(function() {
<?php if (!empty($daily)): ?>
	$("#dailytree").treeview();
<?php endif; ?>
<?php if (!empty($monthly)): ?>
	$("#monthlytree").treeview();
<?php endif; ?>
});
</script>
<h2>Error Hits</h2>
<form action="" method="post" name="plot">
<h3><a name="daily">Daily error hits</a></h3>
<input type="submit" value="Select month:" onclick="return (document.forms.plot.action='#daily');" /> <select name="month">
<?php 
foreach ($months as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $month ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<?php if (empty($daily)): ?>
<h4>No error hit during this month.</h4>
<?php else: ?>
<ul id="dailytree" class="treeview-black">
<?php foreach ($daily as $date => $errors): ?>
	<li><span><?php echo $date; ?></span>
		<ul>
		<?php foreach ($errors as $error): ?>
			<li><span>[HTTP_<?php echo $error['status']; ?>] <a href="<?php echo url::base() . $error['uri']['uri']; ?>">/<?php echo $error['uri']['uri']; ?></a> (<?php echo $error['accesses']; ?>)</span>
			<?php if (!empty($error['referers'])): ?>
				<ul style="display: none">
				<?php foreach ($error['referers'] as $ref): ?>
					<li><span>[HTTP_Referer] <a href="<?php echo $ref['url']; ?>"><?php echo $ref['url']; ?></a></span></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
	</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<h3><a name="monthly">Monthly error hits</a></h3>
<input type="submit" value="Select year:" onclick="return (document.forms.plot.action='#monthly');" /> <select name="year">
<?php 
foreach ($years as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $year ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>

<?php if (empty($monthly)): ?>
<h4>No error hit during this year.</h4>
<?php else: ?>
<ul id="monthlytree" class="treeview-black">
<?php foreach ($monthly as $date => $errors): ?>
	<li><span><?php echo $date; ?></span>
		<ul>
		<?php foreach ($errors as $error): ?>
			<li><span>[HTTP_<?php echo $error['status']; ?>] <a href="<?php echo url::base() . $error['uri']['uri']; ?>">/<?php echo $error['uri']['uri']; ?></a> (<?php echo $error['accesses']; ?>)</span>
			<?php if (!empty($error['referers'])): ?>
				<ul style="display: none">
				<?php foreach ($error['referers'] as $ref): ?>
					<li><span>[HTTP_Referer] <a href="<?php echo $ref['url']; ?>"><?php echo $ref['url']; ?></a></span></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
	</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

</form>