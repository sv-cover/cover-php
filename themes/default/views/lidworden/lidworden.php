<?php

class LidwordenView extends View
{
	public function render_form(array $errors = array())
	{
		$editable_model = get_model('DataModelEditable');

		$voorwaarden = $editable_model->get_iter_from_title('Voorwaarden aanmelden');

		$opmerkingen = $editable_model->get_iter_from_title('Opmerkingen aanmelden');

		$academic_year = time() < mktime(0, 0, 0, 7, 1, date('Y')) ? date('Y') - 1 : date('Y');

		return $this->render('lidworden.twig', compact('errors', 'voorwaarden', 'opmerkingen', 'academic_year'));
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
		return $this->search_link(['full_name' => format_string('$first_name$family_name_preposition|optional $family_name', $row['data'])]
	}

	public function search_link_for_email_address($row)
	{
		return $this->search_link(['full_name' => '', 'email_address' => $row['data']['email_address']]);
	}
}
