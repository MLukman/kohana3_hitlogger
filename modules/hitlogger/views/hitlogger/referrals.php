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
<h2>Referrals</h2>
<form action="" method="POST" name="plot">
<input type="submit" value="Select listing type:" onclick="return (document.forms.plot.action='#');" /> <select name="listing_type">
<?php
foreach ($listing_types as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $listing_type ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<h3><a name="daily">Daily referrals</a></h3>
<input type="submit" value="Select month:" onclick="return (document.forms.plot.action='#daily');" /> <select name="month">
<?php 
foreach ($months as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $month ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>
<?php if (empty($daily)): ?>
<h4>No referral during this month.</h4>
<?php else: ?>
<ul id="dailytree" class="treeview-black">
<?php foreach ($daily as $k1 => $v1): ?>
	<li><span><?php echo $k1; ?></span>
		<ul>
		<?php foreach ($v1 as $k2 => $v2): ?>
			<li><span><?php echo $k2; ?></span>
				<ul>
				<?php foreach ($v2 as $k3 => $v3): ?>
					<li><span><?php echo $k3; ?> (<?php $sum = 0; foreach ($v3 as $v4) { $sum += $v4['count']; } echo $sum; ?>)</span>
						<ul style="display: none;">
							<?php foreach ($v3 as $v4): ?>
								<li><span><a href="<?php echo $v4['referer']['url']; ?>"><?php echo $v4['referer']['url']; ?></a> (<?php echo $v4['count']; ?>)</span></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
				</ul>
			</li>
		<?php endforeach; ?>
		</ul>
	</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<h3><a name="monthly">Monthly referrals</a></h3>
<input type="submit" value="Select year:" onclick="return (document.forms.plot.action='#monthly');" /> <select name="year">
<?php 
foreach ($years as $k => $v) {
	echo '<option value="'.$k.'"'.($k == $year ? ' selected="selected"' : '').'>'.$v.'</option>';
}
?>
</select>

<?php if (empty($monthly)): ?>
<h4>No referral during this year.</h4>
<?php else: ?>
<ul id="monthlytree" class="treeview-black">
<?php foreach ($monthly as $k1 => $v1): ?>
	<li><span><?php echo $k1; ?></span>
		<ul>
		<?php foreach ($v1 as $k2 => $v2): ?>
			<li><span><?php echo $k2; ?></span>
				<ul>
				<?php foreach ($v2 as $k3 => $v3): ?>
					<li><span><?php echo $k3; ?> (<?php $sum = 0; foreach ($v3 as $v4) { $sum += $v4['count']; } echo $sum; ?>)</span>
						<ul style="display: none;">
							<?php usort($v3, create_function('$a, $b', '$c = $b[\'count\'] - $a[\'count\']; if ($c == 0) { $c = strcmp($a[\'referer\'][\'url\'], $b[\'referer\'][\'url\']); } return $c;'));
							foreach ($v3 as $v4): ?>
								<li><span><a href="<?php echo $v4['referer']['url']; ?>"><?php echo $v4['referer']['url']; ?></a> (<?php echo $v4['count']; ?>)</span></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
				</ul>
			</li>
		<?php endforeach; ?>
		</ul>
	</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

</form>