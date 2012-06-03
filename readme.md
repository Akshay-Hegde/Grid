# Grid for Streams

Grid is a field type for use with Streams for PyroCMS. It allows you to create rows of custom data for an entry.

## Requirements

Required PyroCMS 2.2 and the PyroStreams 2.3.

## Installation

Place the grid folder in either your addons/_site\_ref_/field_types folder or addons/shared\_addons/field_types folder. It will then be accessible to 

## Setting up a Grid Field Type

To use Grid, create a field, and choose "Grid" as the field type. An interface will appear that allows you to choose which fields you'd like to appear. For each field, you can choose whether the field needs to be unique and/or required. You can also provide instructions.

Note: some field types will not appear since they are not compatible with Grid. These include multiple relationships, and other alternative process field types.

## Displaying Grid Data

Once you have assigned your field to a stream, you can start entering in data. To display it on the front end, simply create a tag loop with the slug of the grid field you created. All the field variables will be available within this loop:

	{{ my_field }}

		{{ name }}

	{{ /my_field }}

You can also use almost all of the stream cycle variable to restrict display of the variables:

	{{ my_fields limit="5" sort="asc" order_by="name" }}

		{{ name }}

	{{ /my_fields }}