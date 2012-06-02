<div class="grid_table_container">

<table class="grid_table" id="<?php echo $field_slug; ?>_input_table" stlye="margin-bottom: 0!important;">

<thead>

<tr>
	<?php foreach($grid_fields as $grid_field): ?>
		<th><?php echo $grid_field->field_name; ?><br><span><?php echo $grid_field->instructions; ?></span></th>
	<?php endforeach; ?>
	<th width="5%"></th>
</tr>

</thead>

<tbody>

	<?php if($entries): $count=1; foreach($entries as $entry): ?>
	<tr class="grid_row" id="<?php echo $field_slug; ?>_row_<?php echo $count; ?>">
		<?php foreach($grid_fields as $field): ?>
		<input type="hidden" name="<?php echo $field_slug; ?>_row_<?php echo $count; ?>_beacon" value="<?php echo (is_numeric($entry['id'])) ? $entry['id'] : 'y'; ?>" />
		<td><?php echo $this->type->types->{$field->field_type}->form_output(array('form_slug' => $field_slug.'_'.$field->field_slug.'_'.$count, 'value' => $entry[$field->field_slug], 'custom' => $field->field_data), $entry['id'], $field); ?></td>
		<?php endforeach; ?>
		<td><a class="btn gray grid_row_delete" data-delete-id="<?php echo $field_slug; ?>_row_<?php echo $count; ?>">x</a></td>
	</tr>
	<?php $count++; endforeach; else: ?>

	<?php 

		$row_count = 1;
		while($min >= $row_count):	

	?>
	<tr class="grid_row" id="<?php echo $field_slug; ?>_row_<?php echo $row_count; ?>">
		<?php foreach($grid_fields as $field): ?>
		<input type="hidden" name="<?php echo $field_slug; ?>_row_<?php echo $row_count; ?>_beacon" value="y" />
		<td><?php echo $this->type->types->{$field->field_type}->form_output(array('form_slug' => $field_slug.'_'.$field->field_slug.'_'.$row_count, 'value' => null, 'custom' => $field->field_data), null, $field); ?></td>
		<?php endforeach; ?>
	</tr>
	<?php $row_count++; endwhile; ?>

	<?php endif; ?>

</tbody>

</table>

</div><!--.grid_table_container-->

<p><a class="add_grid_row btn orange" data-field-slug="<?php echo $field_slug; ?>" data-field-id="<?php echo $field_id; ?>"><?php echo $add_button_text; ?></a></p>

<input type="hidden" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" value="y" /> 