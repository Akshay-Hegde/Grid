$(function() {
	
	$('.form_inputs').on('click', '.add_row', function()
	{
		var row_count = $('table#grid_table_setup tbody tr').length;
		row_count++;
		var namespace = $(this).attr('data-namespace');
		new_field_row(row_count, namespace);
	});

});

function new_field_row(count, namespace)
{
	// Get a row via AJAX
	$.ajax({
		dataType: "text",
		type: "POST",
		data: 'count='+count+'&namespace='+namespace+'&csrf_hash_name='+$.cookie('csrf_cookie_name'),
		url:  SITE_URL+'streams_core/public_ajax/field/grid/new_setup_row',
		success: function(returned_html) {
			console.log(returned_html);
			$('table#grid_table_setup tr:last').after(returned_html);
			pyro.chosen();
		}
	});
}


