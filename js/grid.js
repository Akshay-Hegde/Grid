$(function() {

	/**
	 * Delete a Grid Row
	 */
	$('.grid_delete_row').click(function() {

		var table_id = $(this).attr('data-table-id');
		var row_id = $(this).attr('data-row-id');

	});

	/**
	 * Add a Grid Row
	 */
	$('.add_grid_row').click(function() {

		var field_slug = $(this).attr('data-field-slug');
		var field_id = $(this).attr('data-field-id');

		var row_count = $('table#'+field_slug+'_input_table tr').length;

		// Get a row via AJAX
		$.ajax({
			dataType: "text",
			type: "POST",
			data: 'count='+row_count+'&field_slug='+field_slug+'&field_id='+field_id,
			url:  SITE_URL+'streams_core/public_ajax/field/grid/new_grid_row',
			success: function(returned_html) {
				$('table#'+field_slug+'_input_table tr:last').after(returned_html);
				pyro.chosen();
			}
		});
	
	});

	$('.grid_table_container').on('mouseover', '.grid_row', function() {

		$('.grid_row_delete').removeClass('red').addClass('gray');
		var this_id = $(this).attr('id');
		$('#'+this_id+' .grid_row_delete').removeClass('gray').addClass('red');

	});

	$('.grid_table_container').on('mouseout', '.grid_row', function() {$('.grid_row_delete').removeClass('red').addClass('gray');});

	$('.grid_table_container').on('click', '.grid_row_delete', function() {

		var row_id = $(this).attr('data-delete-id');
		$('#'+row_id).fadeOut('slow').remove();

	});
	
	/**
	 * Re-order
	 */	 
	$('table.grid_table tbody').sortable({

		handle: 'td',
		helper: 'clone',
		opacity: 0.8,
		placeholder: 'empty_grid_row'
		
	}).disableSelection();

});