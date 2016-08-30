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
}
