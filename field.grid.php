<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PyroStreams Grid Field Type
 *
 * The grid field type allows you to create a grid of
 * inputs and loop through the values via the
 * plugin interface.
 *
 * @author		Adam Fairholm
 * @copyright	Copyright (c) 2012-2013, Adam Fairholm
 */
class Field_grid
{
	public $field_type_slug			= 'grid';
	
	public $alt_process				= true;
	
	public $db_col_type				= false;

	public $custom_parameters		= array('rows', 'min_rows', 'max_rows', 'add_button_text');

	public $version					= '0.8';

	public $author					= array(
										'name' 	=> 'Adam Fairholm',
										'url' 	=> 'http://adamfairholm.com'
									);

	/**
	 * Prefix before grid database tables.
	 *
	 * Grid creates an extra grid streams for each grid
	 * created.
	 *
	 * @var 	string
	 */
	public $grid_table_prefix		= 'grid_rows_';

	/**
	 * Pre-Save
	 *
	 * Process the rows before saving them
	 * to the database.
	 *
	 * @param	string
	 * @param	obj
	 * @param	obj
	 * @param	int
	 * @return	void
	 */
	public function pre_save($input, $field, $stream, $entry_id)
	{
		$table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;

		// -------------------------------------
		// Get Field Stream
		// -------------------------------------

		$stream_id = $this->CI->db->select('id')->where('stream_slug', $table_name)->get(STREAMS_TABLE)->row()->id;

		// -------------------------------------
		// Get Fields
		// -------------------------------------

		$grid_fields = $this->CI->streams_m->get_stream_fields($stream_id);

		// Go through the columns in the structure.
		$count 		= 1;

		$insert_data = array();
		$update_data = array();
		$skip_delete = array();

		// We can have up to 100 fields.
		while ($count < 100)
		{
			// The beacon is how we know there is a row there
			// with any data.
			if (isset($_POST[$field->field_slug.'_row_'.$count.'_beacon']))
			{
				// This is where we'll put the row data.
				$row_data = array();

				// Go through and grab/process the data from each row.
				// Note: the grid field type does not support alt proceses
				// at this time.
				foreach ($grid_fields as $row_field)
				{
					// Grab the post value
					$row_data[$row_field->field_slug] = $this->CI->input->post($field->field_slug.'_'.$row_field->field_slug.'_'.$count);

					$type =& $this->CI->type->types->{$row_field->field_type};

					if (( ! isset($type->alt_process) or ! $type->alt_process) and method_exists($type, 'pre_save'))
					{
						$row_data[$row_field->field_slug] = $type->pre_save(
									$row_data[$row_field->field_slug],
									$row_field,
									null,
									null,
									null);

					}
				}

				// Set the ordering.
				// @todo - this will not work as it is not
				// the true ordering. We need a way to pass the actual
				// order of the row.
				$row_data['ordering_count'] = $count;

				// If the beacon is an ID, then we are going to update
				// the row instead of just replace it, otherwise our AUTO_INCREMENT
				// would get out of hand pretty quick. So, we have an array
				// of IDs not to delete.
				if (is_numeric($this->CI->input->post($field->field_slug.'_row_'.$count.'_beacon')))
				{
					$beacon_id = $this->CI->input->post($field->field_slug.'_row_'.$count.'_beacon');
				
					$skip_delete[] = $beacon_id;
					
					// Since we are updating we need to set the updated time.
					$row_data['updated'] = date('Y-m-d H:i:s');

					$this->CI->db->limit(1)->where('id', $beacon_id)->update($table_name, $row_data);
				}
				else
				{
					$row_data['entry_id'] 	= $entry_id;
					$row_data['stream_id']	= $stream->id;
					$row_data['created']	= date('Y-m-d H:i:s');
					$row_data['created_by']	= (isset($this->CI->current_user->id)) ? $this->CI->current_user->id : null;

					$this->CI->db->insert($table_name, $row_data);

					// Let's not delete one we just added
					$skip_delete[] = $this->CI->db->insert_id();
				}

				unset($row_data);
			}
			else
			{
				$continue = false;
			}

			$count++;
		}

		// Remove all the rows for this entry
		foreach ($skip_delete as $dont_delete)
		{
			$this->CI->db->where('id !=', $dont_delete);
		}

		$this->CI->db->where('entry_id', $entry_id)->where('stream_id', $stream->id)->delete($table_name);
	}

	/**
	 * Process before outputting to the backend
	 *
	 * @param	array
	 * @return	string
	 */
	public function alt_pre_output($row_id, $extra, $type, $stream)
	{
		return null;
	}

	/**
	 * Function called when being accessed via
	 * the plugin system.
	 */
	public function plugin_override($field, $attributes)
	{
		// Get the table name for this particular table
		$table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;

		$params = array(
			'stream'        => $table_name,
			'namespace'     => $field->field_namespace
		);

		$params = array_merge($params, $attributes);

		// We are going to turn the where param into an array
		// of where parameters.
		if (isset($params['where']))
		{
			$params['where'] = array($params['where']);
		}
		else
		{
			$params['where'] = array();
		}

		// -------------------------------------
		// Filter by Entry ID
		// -------------------------------------
		
		$params['where'][] = "`entry_id`='{$attributes['row_id']}'";

		$entries = $this->CI->streams->entries->get_entries($params);

		return $entries['entries'];
	}

	/**
	 * Event
	 *
	 * Adds the events for our grid field type
	 * as well as the fields contained
	 * without our grid.
	 *
	 * @return	void
	 */
	public function event($field)
	{
		// Run the grid events.
		$this->CI->type->add_css('grid', 'grid_public.css');
		$this->CI->type->add_js('grid', 'grid.js');
	
		// Run the field events for our actual grid fields.
		$table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;

		$streamId = $this->CI->streams_m->get_stream_id_from_slug($table_name, $field->field_namespace);

		$stream_fields = $this->CI->streams_m->get_stream_fields($streamId);

		$this->CI->fields->run_field_events($stream_fields);
	}

	/**
	 * Field Setup Event
	 *
	 * @return	void
	 */
	public function field_setup_event()
	{
		$this->CI->type->add_js('grid', 'setup.js');
		$this->CI->type->add_css('grid', 'grid.css');
	}
	
	/**
	 * Process for when adding field assignment
	 *
	 * Create a new table with a name of:
	 * grid_rows_{field_slug}
	 *
	 * This will almost certainly never be used, as the field is
	 * created automatically when you start adding fields to
	 * it in the grid field type setup.
	 *
	 * @param 	obj
	 * @param 	obj
	 * @return 	bool
	 */
	public function field_assignment_construct($field)
	{
		$table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;
		return $this->create_grid_table($table_name, $field->field_namespace);
	}

	/**
	 * Process for when removing field assignment
	 *
	 * @param	obj
	 * @param	obj
	 * @return	void
	 */
	public function field_assignment_destruct($field, $stream)
	{
		// Is this field assigned anywhere else?
		if ($this->CI->db
					->where('field_id', $field->id)
					->where('stream_id', $stream->id)
					->get(ASSIGN_TABLE)->num_rows() <= 1)
		{
			return $this->remove_grid_instance($field);
		}

		return true;

	}

	/**
	 * Process delete for fields that have
	 * no assignments. In this case we are just
	 * going to drop the table
	 *
	 * @param 	obj - field
	 * @return 	bool
	 */
	public function field_no_assign_destruct($field)
	{
		return $this->remove_grid_instance($field);
	}

	/**
	 * Remove a Grid Instance
	 *
	 * Grid instances are just streams, so we are basically
	 * just deleting the stream which gets rid of the assignments
	 * as well as the stream instance and the table itself.
	 *
	 * @access 	private
	 * @param 	obj - field obj
	 * @return 	bool - success/failure of remove
	 */
	private function remove_grid_instance($field)
	{
		$table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;

		// Get the stream via the name we can deduce from the field.
		$stream = $this->CI->streams_m->get_stream($table_name, true, $field->field_namespace);

		// Delete the stream
		return $this->CI->streams_m->delete_stream($stream);
	}

	/**
	 * Called when deleting an entry
	 *
	 * @param	obj
	 * @param	obj
	 * @return	void
	 */
	public function entry_destruct($entry, $field, $stream)
	{		
		$this->CI->db->where('entry_id', $entry->id)->delete($this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug);
	}

	/**
	 * Process renaming column
	 *
	 * @access	public
	 * @param	obj
	 * @param	obj
	 * @return	void
	 */
	public function alt_rename_column($field, $stream)
	{
		return null;
	}

	public function validation()
	{
		// Is this required and there are no minmum rows?
		// Make the minimum rows "1"

		// Get a row count

		// Is there a minimum rows? If so, make sure that
		// we have enough to cover the minimum amount

		// Is there a maximum rows? If so, make sure that we
		// don't have too many.

		// Passed? Okay, let's go through and see which rows have
		// values that were required by were not filled out.
		$needed = array();

		return true;
	}

	/**
	 * Output Form Input
	 *
	 * @param	array
	 * @return	string
	 */
	public function form_output($data, $entry_id, $field)
	{
		$data_table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$data['form_slug'];

		$pass_data = array(
			'field_slug'	=> $data['form_slug'],
			'field_id'		=> $field->field_id,
			'fields'		=> $data['custom']['rows']
		);

		// -------------------------------------
		// Get Current Data
		// -------------------------------------
		// Do we have an entry ID? If so, then let's
		// get the data we have
		// -------------------------------------

		if (is_numeric($entry_id))
		{
			$pass_data['entries'] = $this->CI->db->where('entry_id', $entry_id)->get($data_table_name)->result_array();
		}
		else
		{
			$pass_data['entries'] = array();
		}

		// -------------------------------------
		// Get Field Stream
		// -------------------------------------

		$stream_id = $this->CI->db->select('id')->where('stream_slug', $data_table_name)->get(STREAMS_TABLE)->row()->id;

		// -------------------------------------
		// Get Fields
		// -------------------------------------

		$pass_data['grid_fields'] = $this->CI->streams_m->get_stream_fields($stream_id);

		// -------------------------------------
		// Repopulate
		// -------------------------------------

		if ($_POST)
		{
			// This is for keeping the state of things if validation
			// on other fiels (or this field) has been triggered. We are going
			// through and re-creating the entries table from the $_POST data.
			$entries = array();
			$entry_count = 1;
			$continue = true;

			while($continue === true)
			{
				if (isset($_POST[$field->field_slug.'_row_'.$entry_count.'_beacon']))
				{
					$entries[$entry_count] = array('id' => null);
				
					foreach ($pass_data['grid_fields'] as $row_field)
					{
						$entries[$entry_count][$row_field->field_slug] = $this->CI->input->post($field->field_slug.'_'.$row_field->field_slug.'_'.$entry_count);
						$entries[$entry_count]['ordering_count'] = $entry_count;
					}
				}
				else
				{
					$continue = false;
				}

				$entry_count++;
			}

			$pass_data['entries'] = $entries;
		}

		// Establish Minimum
		$pass_data['min'] = (isset($data['custom']['min_rows']) and is_numeric($data['custom']['min_rows'])) ? $data['custom']['min_rows'] : 0;

		// Establish Button Text
		$pass_data['add_button_text'] = (isset($data['custom']['add_button_text']) and $data['custom']['add_button_text']) ? $this->CI->fields->translate_label($data['custom']['add_button_text']) : lang('streams:grid.default_add_row');

		return $this->CI->type->load_view('grid', 'input_table', $pass_data, true);
	}

	/**
	 * New Grid Row
	 *
	 * Front-end AJAX function to return a new row
	 *
	 * @access 	public
	 * @return 	string
	 */
	public function ajax_new_grid_row()
	{
		$this->CI->load->helper('form');

		$count 			= $this->CI->input->post('count');
		$field_slug 	= $this->CI->input->post('field_slug');
		$field_id 		= $this->CI->input->post('field_id');

		$field = $this->CI->fields_m->get_field($field_id);

		$data_table_name = $this->grid_table_prefix.$field->field_namespace.'_'.$field->field_slug;

		$field_rows = $this->CI->streams->fields->get_stream_fields($data_table_name, $field->field_namespace);

		$html = '<tr class="grid_row" id="'.$field_slug.'_row_'.$count.'">';

		// @todo: Show required
		// @todo: Do width
		foreach ($field_rows as $row_field)
		{
			$html .= '<td';

			/*if (isset($field['width']))
			{
				$html .= ' width="'.$field['width'].'"';
			}*/

			// @todo: this is a really dumb way to do this, but it works
			// for now. Please replace this.
			$html .= '>'.str_replace(
				array('name="'.$row_field['field_slug'].'"', 'id="'.$row_field['field_slug'].'"'),
				array('name="'.$field->field_slug.'_'.$row_field['field_slug'].'_'.$count.'"', 'id="'.$field->field_slug.'_'.$row_field['field_slug'].'_'.$count.'"'),
				$row_field['input']).'</td>';
		}

		$html .= '<td><a class="btn gray grid_row_delete" data-delete-id="'.$field_slug.'_row_'.$count.'">x</a></td>';

		return $html .= '</tr><input type="hidden" name="'.$field_slug.'_row_'.$count.'_beacon" value="y" />';
	}

	/**
	 * Setup Row Structure
	 *
	 * Almost all of the logic for setting up the row structure is
	 * in param_rows_pre_save(). This is just a form display with
	 * some AJAX calls.
	 *
	 * @param	int - stream_id
	 * @return	string
	 */
	public function param_rows($current_data, $namespace)
	{
		$data['current_data'] 	= $current_data;
		$data['fields_array'] 	= $this->fields_array($namespace);
		$data['namespace']		= $namespace;

		return $this->CI->type->load_view('grid', 'setup', $data, true);
	}

	/**
	 * Fields Array
	 *
	 * Returns array of available fields for drop down.
	 *
	 * @todo - in the future, this should filter out
	 * field types that are not eligible such as alt process field types.
	 *
	 * @access 	private
	 * @param 	string - namespace
	 * @return 	array
	 */
	private function fields_array($namespace)
	{
		// Get the fields.
		$fields = $this->CI->fields_m->get_fields($namespace);

		// Can be left null to delete the row
		$fields_array = array('' => '-----');

		foreach ($fields as $field)
		{
			$fields_array[$field->id] = $field->field_name;
		}

		return $fields_array;
	}

	/**
	 * AJAX call to setup a new row
	 * when we are creating the row structure
	 * during field creation/editing
	 *
	 * @access 	public
	 * @return 	string
	 */
	public function ajax_new_setup_row()
	{
		$this->CI->load->helper('form');

		$count = $this->CI->input->post('count');
		$namespace = $this->CI->input->post('namespace');

		$html = '<tr id="row_'.$count.'">';

		$html .= '<td>'.form_dropdown('row_field_id_'.$count, $this->fields_array($namespace), null, 'style="width: 100px!important;"').'</td>';
		$html .= '<td>'.form_checkbox('row_is_required_'.$count).'</td>';
		$html .= '<td>'.form_checkbox('row_is_unique_'.$count).'</td>';
		$html .= '<td>'.form_textarea('row_instructions_'.$count).'</td>';

		return $html .= '</tr>';
	}

	/**
	 * Create the Grid Stream
	 *
	 * @access 	public
	 * @param 	string - the table name to create
	 * @return 	bool
	 */
	public function create_grid_table($table_name, $namespace)
	{
		// Check for table before creating it.
		if ( ! $this->CI->db->table_exists($table_name))
		{
			$this->CI->streams->streams->add_stream(
							$table_name,
							$table_name,
							$namespace,
							null,
							'Used by the grid field type.',
							array('id_hidden' => 'yes'));

			// ----------------------------------
			// Streams ID & Entry ID columns
			// ----------------------------------
			// We are just going to add these manually instead of via
			// the Streams API so we don't end up with
			// conflicting field name errors for multiple grid instances
			// within a namespace.
			// ----------------------------------

			$this->CI->load->dbforge();
			return $this->CI->dbforge->add_column($table_name, 
					array(
						'stream_id' 	=> array('type' => 'INT', 'constraint' => '11'),
						'entry_id'		=> array('type' => 'INT', 'constraint' => '11')
					));
		}
		else
		{
			return true;
		}
	}

	/**
	 * Rows Pre-Save
	 *
	 * Pre-save function for creating rows.
	 *
	 * @param	int - stream_id
	 * @return	string
	 */
	public function param_rows_pre_save($data)
	{
		$data_table_name = $this->grid_table_prefix.$data['field_namespace'].'_'.$data['field_slug'];

		// Get all of the table columns
		// that are custom columns.
		$existing_columns = array();

		// We need to create the stream
		// before the field construct gets to it 
		if ( ! $this->CI->db->table_exists($data_table_name))
		{
			$this->create_grid_table($data_table_name, $data['field_namespace']);
		}

		// Get the stream ID
		$stream_id = $this->CI->streams_m->get_stream_id_from_slug($data_table_name, $data['field_namespace']);

		// We need a stream ID to go on
		if ( ! $stream_id) return false;

		$reserved_names = array('id', 'created_by', 'created', 'updated', 'ordering_count', 'entry_id');

		$table_cols = $this->CI->db->list_fields($data_table_name);

		foreach ($table_cols as $col)
		{
			if ( ! in_array($col, $reserved_names))
			{
				$existing_columns[] = $col;
			}
		}

		// Start off with row 1
		$count = 1;

		$this->CI->load->dbforge();

		// We can have up to 10 rows.
		while ($count <= 10)
		{
			// If the there isn't any more rows
			// to go through, just forget about it.
			if ( ! isset($_POST['row_field_id_'.$count]))
			{
				$count++;
				continue;
			}
		
			$field_id = $this->CI->input->post('row_field_id_'.$count);

			// We'll skip counts that are not there. The most common
			// situation this catches is deleted set rows.
			if ( ! is_numeric($field_id))
			{
				$count++;
				continue;
			}

			// -------------------------------------
			// Get the field data
			// -------------------------------------
			
			$field = $this->CI->fields_m->get_field($field_id);

			// We need a valid field.
			if ( ! $field)
			{
				$count++;
				continue;
			}

			// -------------------------------------
			// Assign Field
			// -------------------------------------

			// Does this assignment already exist?
			if ( ! $this->CI->fields_m->assignment_exists($stream_id, $field_id))
			{
				// If we got this far we can remove it from the
				// list of existing columns to drop
				unset($existing_columns[array_search($field->field_slug, $existing_columns)]);

				$is_required 	= ($this->CI->input->post('row_is_required_'.$count) == 'yes') ? 'yes' : 'no';
				$is_unique 		= ($this->CI->input->post('row_is_unique_'.$count) == 'yes') ? 'yes' : 'no';
							
				$assign_data = array(
					'title_column'  => false,
					'required'      => $is_required,
					'unique'        => $is_unique,
					'instructions'	=> $this->CI->input->post('row_instructions_'.$count)
				);

				$this->CI->streams->fields->assign_field($field->field_namespace, $data_table_name, $field->field_slug, $assign_data);

				$count++;
			}
			else
			{
				// If it does exist, then we still need to
				// increment.
				$count++;
			}

			unset($field);
			unset($field_id);
			unset($assign_data);
		}

		// Go through and delete the fields
		// that were not submitted by were there 
		// when we started the journey.
		if ( ! empty($existing_columns))
		{
			foreach ($existing_columns as $column)
			{
				$this->CI->streams->fields->deassign_field($data_table_name, $data['field_namespace'], $column);
			}
		}

		return null;
	}

	/**
	 * Minimum Number of Choices
	 *
	 * @param	[string - value]
	 * @return	string
	 */	
	public function param_max_rows($value = null)
	{
		return form_input('max_rows', $value);
	}

	/**
	 * Minimum Number of Rows
	 *
	 * @access	public
	 * @param	[string - value]
	 * @return	string
	 */	
	public function param_min_rows($value = null)
	{
		return form_input('min_rows', $value);
	}

	/**
	 * Add Button Text
	 *
	 * @access	public
	 * @param	[string - value]
	 * @return	string
	 */	
	public function param_add_button_text($value = null)
	{
		return array(
				'input'			=> form_input('add_button_text', $value),
				'instructions'	=> lang('streams:grid.add_button_instr')
			);
	}

}