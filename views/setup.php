<table class="grid_table" id="grid_table_setup">

	<thead>

	<tr id="setup_row">
		<th>Field</th>
		<th>Required</th>
		<th>Unique</th>
		<th>Instructions</th>
		<th><?php echo lang('streams.grid.width'); ?></th>
	<tr>

	</thead>

	<tbody>

	<?php if( ! $current_data) { ?>

		<tr>
			<td><?php echo form_dropdown('row_field_id_1', $fields_array, null, 'style="width: 100px!important;"'); ?></td>
			<td><?php echo form_checkbox('row_is_required_1', 'yes'); ?></td>
			<td><?php echo form_checkbox('row_is_unique_1', 'yes'); ?></td>
			<td><?php echo form_textarea('row_instructions_1'); ?></td>
			<td><?php echo form_input('row_width_1', null, 'size="6"'); ?></td>
		</tr>

	<?php } else { ?>

		<?php $count = 1; foreach ($current_data as $row) { ?>

		<tr>
			<td><?php echo form_dropdown('row_field_id_'.$count, $fields_array, $row['field_id'], 'style="width: 150px!important;"'); ?></td>
			<td><?php echo form_checkbox('row_is_required_'.$count, 'yes', ($row['is_required'] == 'yes') ? true : false); ?></td>
			<td><?php echo form_checkbox('row_is_unique_'.$count, 'yes', ($row['is_unique'] == 'yes') ? true : false); ?></td>
			<td><?php echo form_textarea('row_instructions_'.$count, $row['instructions']); ?></td>
			<td><?php echo form_input('row_width_'.$count, $row['width'], 'size="6"'); ?></td>
		</tr>

		<?php $count++; } ?>

	<?php } ?>

	</tbody>

</table>

<p><a class="add_row" data-namespace="<?php echo $namespace; ?>">Add Field</a></p>