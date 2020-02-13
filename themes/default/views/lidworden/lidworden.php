<?php

class LidwordenView extends View
{
	public function render_form(array $errors = array())
	{
		$config = get_model('DataModelConfiguratie');

		$editable_model = get_model('DataModelEditable');

		$voorwaarden = $editable_model->get_iter_from_title('Voorwaarden aanmelden');

		$academic_year = time() < mktime(0, 0, 0, 7, 1, date('Y')) ? date('Y') - 1 : date('Y');

		$terms_conditions_version = $config->get_value('terms_conditions_version');

		return $this->render('lidworden.twig', compact('errors', 'voorwaarden', 'academic_year', 'terms_conditions_version'));
	}

	public function render_submitted()
	{
		return $this->render('submitted.twig');
	}

	public function render_confirmed()
	{
		return $this->render('confirmed.twig');
	}

	public function render_pending(array $registrations, $message = null)
	{
		return $this->render('pending.twig', compact('registrations', 'message'));
	}

	public function render_pending_form(array $registration)
	{
		return $this->render('pending_form.twig', compact('registration'));
	}

	public function search_link($conditions)
	{
		$url = 'https://secretary.svcover.nl/administration/everyone/?' . http_build_query($conditions);
		return sprintf('<a href="%s" target="_blank" title="Search for this in Secretary"><img src="themes/default/images/search.png" width="12" height="12"></a>', markup_format_attribute($url));
	}

	public function full_name($row)
	{
		return format_string('$first_name$family_name_preposition|optional $family_name', $row['data']);
	}

	public function search_link_for_full_name($row)
	{
		return $this->search_link(['full_name' => format_string('$first_name$family_name_preposition|optional $family_name', $row['data'])]);
	}

	public function search_link_for_email_address($row)
	{
		return $this->search_link(['full_name' => '', 'email_address' => $row['data']['email_address']]);
	}
}
