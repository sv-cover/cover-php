<?php
	class LidwordenView extends View {
		protected $__file = __FILE__;
		
		function render_text_row($caption, $field, $errors, $required) {
			$args = array_slice(func_get_args(), 4);
			array_unshift($args, null);
			array_unshift($args, $field);

			return table_row(label($caption, $field, $errors, $required),
			 	call_user_func_array('input_text', $args)) . "\n";
		}

		function view_verzonden($model, $iter, $params = null) {
			echo '<h1>' . __('Lidmaatschapsformulier') . '</h1>
			<p>Je lidmaatschapsaanvraag is verstuurd.</p>
			<h2>' . __('Opmerkingen') . '</h2>
			<ul>
				<li>Contributie wordt zolang je lid bent van Cover jaarlijks van je bank- of girorekening afgeschreven.</li>
				<li>Je bent lid af wanneer je afstudeert of je lidmaatschap opzegt.</li>
				<li>Opzegging van het lidmaatschap moet schriftelijk gedaan worden bij de secretaris.</li>
				<li>Een wijziging in je gegevens kun je mailen naar bestuur@svcover.nl, schriftelijk melden bij de secretaris of in je profiel op de Cover website aanpassen.</li>
				<li>De contributie bedraagt ¤ 10,- per jaar</li>
			</ul>';
		}	
		
	}
?>
