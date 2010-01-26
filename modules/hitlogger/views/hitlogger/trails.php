<?php defined('SYSPATH') OR die('No direct access allowed.');

function mask_ip($ip) {
	return implode('.', array_slice(explode('.', $ip), 0, 3)) . '.x';
}
?>
<script type="text/javascript" src="<?php echo $filepaths['treeview']; ?>"></script>
<script type="text/javascript">
$(function() {
<?php if (!empty($items)): ?>
	$("#trail").treeview();
<?php endif; ?>
<?php if (!empty($sessions)): ?>
	$("#sessions").treeview();
<?php endif; ?>
});
</script>
<?php if (!empty($session_id)): ?>
<h2>Trail #<?php echo $session_id; ?></h2>
<?php if (empty($items)): ?>
<h4>Trail is empty or does not exist. Please select another trail below.	</h4>
<?php else: ?>
<h4>
<?php echo $session->user_agent; ?> @ <?php echo mask_ip($session->visitor->ip); ?>
</h4>
<ul id="trail" class="treeview-black">
<?php
function print_tree($item) {
	echo '<li><span>'.date('h:i:sa', $item['item']['timestamp']).' /'.$item['item']['uri']['uri'];
	if ($item['item']['stat']['status'] > 200) {
		echo ' [HTTP_'.$item['item']['stat']['status'].']';
	}
	echo '</span>';
	if (!empty($item['children'])) {
		echo '<ul>';
		foreach ($item['children'] as $child) {
			print_tree($child);
		}
		echo '</ul>';
	}
	echo '</li>';
}
foreach ($items as $item) {
	print_tree($item);
}
?>
</ul>
<?php endif; ?>
<?php endif; ?>
<h2>Trails</h2>
<?php if (empty($sessions)): ?>
<h4>There is no trail to view here.</h4>
<?php else: ?>
<h4>Please click on any of the trails below.</h4>
<ul id="sessions" class="treeview-black">
<?php $first = TRUE;
foreach ($sessions as $date => $datesessions): ?>
	<li>
		<span><?php echo $date; ?></span>
		<?php if (!$first): ?><form action="" method="post" style="display:inline">
			<p style="display:inline">
			<input type="hidden" name="op" value="truncate" />
			<input type="hidden" name="date" value="<?php echo $date; ?>" />
			<input type="submit" value="Truncate" /></p></form>
		<?php else: $first = FALSE; ?><?php endif; ?>
		<ul>
		<?php foreach ($datesessions as $session): ?>
			<li><span>[<?php echo date('h:i:sa', $session['timestamp']); ?>]
					<a href="<?php echo $trail_uri . '/' . $session['session']; ?>">
						<?php echo $session['session']; ?> (<?php echo mask_ip($session['visitor']['ip']); ?>)
					</a>
			</span></li>
		<?php endforeach; ?>
		</ul>
	</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>