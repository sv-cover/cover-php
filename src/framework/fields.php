<?php

require_once 'src/framework/form.php';

interface SignUpFieldType
{
	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data, &$error);

	// Render the form field
	public function render($renderer, $value, $error);

	public function process_configuration(array $post_data, ErrorSet $errors);

	public function render_configuration($renderer, ErrorSet $errors);

	// Store the current configuration as an associative array
	public function configuration();

	// Export it to a CSV (as an array with name => text value)
	public function export($value);

	// Get field info as name => info
	public function column_labels();

	// Suggest a value (like process) for a logged-in member
	public function suggest(DataIterMember $member);
}

// Register an autoloader for field types
spl_autoload_register(function($class) {
	if (strncmp($class, 'fields\\', 7) === 0)
		require_once sprintf('%s/../fields/%s.php', __DIR__, substr($class, 7));
});