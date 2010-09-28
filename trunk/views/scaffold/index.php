<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			<?php echo View::factory("scaffold/css")->render(); ?>
		</style>
	</head>
	
	<body>
		<div id="container">
			<?php
				if ( ! empty( $msg ) ) {
					echo "<div class=\"msg success\">" . $msg . "</div>";
				};
			?>
			<p>
				<?php echo HTML::anchor('scaffold/insert', __("Insert"), Array("class"=>"submit right")); ?>
			</p>
			<table width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<?php echo $header ?>
						<th>Action</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<?php echo $header ?>
						<th>Action</th>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($content as $items) : ?>
					<tr>
					<?php foreach ( $items as $item ) : ?>
						<td><?php echo $item ?></td>
					<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<?php echo $pagination ?>
			</p>
		</div>
	</body>

</html>