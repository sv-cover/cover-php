<?php

require_once 'src/framework/form.php';

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;

interface SignUpFieldType
{
	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(Form $form);

	// Add field to a symfony form
	public function build_form(FormBuilderInterface $form_builder);

	// Process configuration form
	public function process_configuration(Form $form);

	// Render configuration form
	public function render_configuration($renderer, array $form_attr);

	// Store the current configuration as an associative array
	public function configuration();

	// Export it to a CSV (as an array with name => text value)
	public function export($value);

	// Export it to a CSV (as an array with name => text value)
	public function get_form_data($value);

	// Get field info as name => info
	public function column_labels();

	// Suggest a value (like process) for a logged-in member
	public function prefill(\DataIterMember $member);
}
