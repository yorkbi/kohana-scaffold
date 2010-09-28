<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Scaffold - <?php echo __("Insert") ?> - <?php echo $column; ?></title>
		<?php echo View::factory("scaffold/snippets/head")->render(); ?>
	</head>
	
	<body>
		<div id="container">
			<p>
				<?php echo HTML::anchor('scaffold', 'Models'); ?> > <?php echo HTML::anchor('scaffold/list/'.strtolower($column), $column); ?> > <strong><?php echo __("Insert") ?></strong>
			</p>
			<?php echo Form::open('scaffold/insert/save', array('id'=>'scaffold_edit')); ?>
				<fieldset>
					<table width="100%" cellpadding="0" cellspacing="0">
						<tfoot>
							<tr>
								<td><input type="hidden" name="column" value="<?php echo strtolower($column); ?>" style="display:none" /></td>
								<td><?php echo Form::submit('', __("Save"), Array("class"=>"submit")); ?> <span><?php echo __("or") ?></span> <?php echo HTML::anchor('/scaffold', __("Cancel")); ?></td>
							</tr>
						</tfoot>
						<tbody>
						<?php foreach ($header as $item) : ?>
							<tr>
								<td><label for="<?php echo $item ?>"><?php echo $item ?></label></td>
								<?php $disabled = ( ( $item === $first ) ? "disabled" : "" ); ?>
								<td><?php echo Form::input($item, "", Array( "id" => $item, "class" => "text", $disabled => $disabled)) ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</fieldset>
			<?php echo Form::close(); ?>
			</form>
			<p>
				<?php echo HTML::anchor('scaffold/list/'.$column, "< " . __("Back") . " " . __("to") . " " . $column ); ?>
			</p>
		</div>
		<?php echo View::factory("scaffold/snippets/footer")->render(); ?>
	</body>

</html>