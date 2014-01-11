<?php
	require_once('markup.php');
	require_once('form.php');
	require_once('Notebook.php');
	require_once('member.php');
	require_once('csv.php');
	
	class BoekenView extends View {
		protected $__file = __FILE__;
		
		function view_auth($model, $iter, $params = null) {
			echo '<hr>';
			$this->view_auth_common();
		}
	
		function winkelwagentje($model) {
			echo '<h2>' . __('Bestelde boeken') . '</h2>';
			
			$member_data = logged_in();
			$books = $model->get_from_member($member_data['id']);
		
			$configuratie = get_model('DataModelConfiguratie');
			$bestellen = $configuratie->get_value('boeken_bestellen');
		
			if ($books && count($books) > 0) {
				if ($bestellen) {
					echo '<form action="boeken.php" method="post">';
					echo input_hidden('submboekenunbestel', 'yes');
				}

				echo '<table class="boeken">
				<tr>';
			
				if ($bestellen)
					echo '<th class="check">' . image('delete_small.png', __('V'), __('Verwijderen')) . '</td>';
			
				echo '<th>' . __('Vak') . '</th><th>' . __('Titel') . '</th><th>' . __('Auteur') . '</th><th>' . __('Prijs') .  "</th></tr>\n";

				$total = 0;
				$nnb = false;

				foreach ($books as $book) {
					echo '<tr>';
				
					if ($bestellen)
						echo '<td class="text_center">' . input_checkbox('id_' . $book->get('id'), null) . '</td>';
					
					echo '<td>' . markup_format_text($book->get('vak')) . '</td>
						<td>' . markup_format_text($book->get('titel')) . '</td>
						<td>' . markup_format_text($book->get('auteur')) . '</td>
						<td>' . ($book->get('prijs') == 0 ? 'n.n.b.' : ('&euro;&nbsp;' . $book->get('prijs'))) . '</td></tr>';
					if ($book->get('prijs') == 0)
						$nnb = true;

					$total += $book->get('prijs');
				}
			
				echo '<tr class="submit"><td colspan="' . ($bestellen ? '5' : '4') . '"><br><div class="text_right"><span class="bold">' . __('Totaal bedrag: &euro;&nbsp;') . number_format($total, 2) . '</span></div>';
			
				if ($nnb) {
					echo '<p><span class="italic">' . __('Boeken waarvan de prijs nog niet bekend is zijn niet opgenomen in de totaalprijs. De uiteindelijke totaal prijs zal dus hoger uitvallen!') . '</span></p>';
				}
			
				echo '</td></tr>';

				if ($bestellen)
					echo '<tr class="submit"><td colspan="5" class="submit">' . input_submit('subm', __('Verwijderen uit bestelling')) . '</td></tr>';
			
				echo '</table>';
			
				if ($bestellen)
					echo '</form>';
			} else {
				echo '<p><span class="italic">' . __('Je hebt nog geen boeken besteld.') . '</span></p>';
			}	
		}
	
		function beschikbare_boeken($model) {
			echo '<h2>' . __('Beschikbare boeken') . '</h2>';

			$configuratie = get_model('DataModelConfiguratie');
			$bestellen = $configuratie->get_value('boeken_bestellen');
		
			if (!$bestellen) {
				echo '<div class="error_message">' . __('De deadline om boeken te bestellen is verstreken') . '</div>';
				return;
			}
		
			$member_data = logged_in();
			$categories = $model->get_categories($member_data['id']);
		
			if (!$categories) {
				echo '<p><span class="italic">' . __('Momenteel zijn er geen boeken te bestellen') . '</span></p>';
				return;
			}
		
			echo '<form action="boeken.php" method="post">'
				. input_hidden('submboekenbestel', 'yes');
			
			$notebook = new Notebook('boeken');

			foreach ($categories as $id => $name) {
				$contents = '<table class="boeken">
					<col class="check">
					<col class="course">
					<col class="title">
					<col class="author">
					<col class="price">
				<tr>
					<th class="check"></th>
					<th>' . __('Vak') . '</th>
					<th>' . __('Titel') . '</th>
					<th>' . __('Auteur') . '</th>
					<th>' . __('Prijs') . '</th>
				</tr>';
			
				$books = $model->get_from_category($id);
			
				foreach ($books as $book) {
					$contents .= table_row(
						input_checkbox('boek_' . $book->get('id'), null),
						htmlspecialchars($book->get('vak')),
						htmlspecialchars($book->get('titel')),
						'<span class="italic">' . htmlspecialchars($book->get('auteur')) . '</span>',
						$book->get('prijs') == 0
							? __('n.n.b.')
							: ('&euro;&nbsp;' . $book->get('prijs')));
				}
			
				$contents .= '</table>';
				$notebook->add_page($name, $contents);
			}
		
			echo $notebook->render()
				. input_submit('subm', __('Bestellen'))
				. '</form>';
		}
	
		function toevoegen_boeken($model, $errors, $added) {
			echo '<div class="bar"><a href="javascript:boek_toevoegen();">' . image('add.png', __('toevoegen'), __('Boek toevoegen'), 'class="button"') . '</a> <a href="javascript:boek_toevoegen();">' . __('Toevoegen') . '</a></div>';
		
			echo '<div id="boek_toevoegen">
				<form action="boeken.php" method="post">';
		
			echo input_hidden('submboekenadd', 'yes');
		
			echo '<table>';
		
			$categories = $model->get_categories();
		
			echo table_row(label(__('Categorie'), 'categorie', $errors, true) . ':', select_field('categorie', $categories, null));
			echo table_row(label(__('Vak'), 'vak', $errors, true) . ':', input_text('vak', null, 'id', 'vak'));
			echo table_row(label(__('Titel'), 'titel', $errors, true) . ':', input_text('titel', null));
			echo table_row(label(__('Auteur'), 'auteur', $errors, true) . ':', input_text('auteur', null));
			echo table_row(label(__('Prijs'), 'prijs', $errors, true) . ':', input_text('prijs', array('prijs' => 0), 'class', 'currency') . ' (' . __('0 = nog niet bekend') . ')');
			echo '<tr><td colspan="2" class="submit">' . input_submit('subm', __('Boek toevoegen')) . '</td></tr>';
		
			echo '</table></form></div>
		
			<script type="text/javascript">
				function boek_toevoegen() {
					div = document.getElementById("boek_toevoegen");
				
					if (div.style.display == "" || div.style.display == "none") {
						div.style.display = "block";
						vak = document.getElementById("vak");
					
						vak.focus();
					} else
						div.style.display = "none";
				}
			
				';
		
			if ((isset($errors) && count($errors) > 0) || isset($added))
				echo 'boek_toevoegen();';
		
			echo '</script>
		
			';
		}
	
		function view_deadline($model, $iter, $params = null) {
			echo '<h2>' . __('Boeken bestellen') . '</h2>
			<div class="error_message">' . __('De deadline om boeken te bestellen is verstreken') . '</div>';
		}
	
		function view_boekcie($model, $iter, $params = null) {
			echo '<h2>' . __('Bestelde boeken') . '</h2>
			<div class="error_message">' . __('Deze pagina is alleen beschikbaar voor de Boekcie') . '</div>';
		}
	
		function beheren($model) {
			echo '<h2>Boeken beheren</h2>';
			$books = $model->get();
		
			if (count($books) == 0) {
				echo '<p><span class="italic">' . sprintf(__('Er zijn geen boeken dit moment. Je kunt wel boeken %s.'), 
				'<a href="javascript:boek_toevoegen();">' . __('toevoegen') . '</a>') . '</span></p>';
				return;
			}
		
			echo '<form action="boeken.php" method="post">';
			echo input_hidden('submboekenedit', 'yes');
			echo '<table class="boeken" id="lijst_boeken">';
		
			echo '<tr><th class="check">' . image('delete_small.png', __('V'), __('Verwijderen')) . '</th>
						<th class="check">' . image('lock_small.png', __('S'), __('Niet meer te bestellen')) . '</th>
						<th>' . __('Vak') . '</th>
						<th>' . __('Titel') . '</th>
						<th>' . __('Auteur') . '</th>
						<th>' . __('Prijs') . '</th></tr>';
		
			foreach ($books as $book) {
				$status = array('status' => $book->get('status') ? 0 : 1);
				echo input_hidden('id_' . $book->get_id(), 'yes');

				echo table_row(input_checkbox('del_' . $book->get_id(), null), 
						input_checkbox('suspend_' . $book->get_id(), $status, 'yes', 'field', 'status'), 
						input_text('vak_' . $book->get_id(), $book->data, 'field', 'vak'),
						input_text('titel_' . $book->get_id(), $book->data, 'field', 'titel'),
						input_text('auteur_' . $book->get_id(), $book->data, 'field', 'auteur'),
						input_text('prijs_' . $book->get_id(), $book->data, 'field', 'prijs', 'class', 'currency'));
			}
		
			$configuratie = get_model('DataModelConfiguratie');
			$bestellen = $configuratie->get_value('boeken_bestellen');

			echo '<tr><td colspan="6" class="text_right">' . input_checkbox('vastzetten', array('vastzetten' => !$bestellen)) . ' <span class="bold">' . __('Bestellingen vastzetten') . '<span></td></tr>
			<tr class="submit"><td class="text_center"><a href="javascript:select_all(\'del\');">' . image('up_small.png', __('Alles'), __('Alles selecteren om te verwijderen')) . '</a></td>
			<td class="text_center"><a href="javascript:select_all(\'suspend\');">' . image('up_small.png', __('Alles'), __('Alles selecteren om te vast te zetten')) . '</a></td>
			<td colspan="4" class="submit">' . input_submit('subm', __('Wijzigen')) . '</td></tr>
			</table>
			</form>
		
			<script type="text/javascript">
				function select_children(parent, name) {
					var child = parent.firstChild;
				
					while (child) {
						select_children(child, name);
					
						if (child.nodeName.toLowerCase() == "input") {
							cname = child.getAttribute("name");
				
							if (cname && cname.indexOf(name + "_") == 0)
								child.setAttribute("checked", "checked");
						}

						child = child.nextSibling;
					}
				}

				function select_all(name) {
					parent = document.getElementById("lijst_boeken");
					select_children(parent, name);
				}
			</script>';
		}
	
		function view_not_found($model, $iter, $params = null) {
			echo '<h2>' . __('Niet gevonden') . '</h2>
			<div class="error_message">' . __('Niet gevonden') . '</div>';
		}
		
		function bestellingen_print_header() {
			header('Content-type: text/html; charset=ISO-8859-15');
			echo '<html>
				<head>
					<title>' . __('Lijst met bestelde boeken') . '</title>
					<link rel="stylesheet" href="themes/default/print.css" type="text/css">
					<link rel="stylesheet" href="themes/default/print.css" type="text/css" media="print">
					<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-15">
				</head>
				<body onLoad="setTimeout(\'window.print()\', 100);">';	
		}
	
		function bestellingen_print_footer() {
			echo '</body></html>';	
		}

		function view_bestellingen_csv_by_book($model, $iters, $params = null) {
			$csv = csv_row(array(__('Boek'), __('Aantal'))) . "\n";

			foreach ($iters as $iter)
				$csv .= csv_row(array($iter->get('titel'), $iter->get('aantal'))) . "\n";
		
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header('Content-Disposition: attachment; filename="bestellingen_per_boek.csv"');
		
			echo $csv;
			exit();
		}

		function view_bestellingen_print_by_book($model, $iters, $params = null) {
			$this->bestellingen_print_header();

			echo '<h1>' . __('Aantal bestellingen per boek') . '</h1>
			<table class="boeken">
			<tr>
				<th>' . __('Boek') . '</th>
				<th width="20px">' . __('Aantal') . '</th>
			</tr>';
		
			$i = true;
		
			foreach ($iters as $iter) {
				$class = 'r' . ($i ? '0' : '1');
			
				echo '<tr class="' . $class . '">
					<td>' . htmlspecialchars($iter->get('titel')) . '</td>';
			
				echo '<td class="text_right">' . $iter->get('aantal') . '</td>
				</tr>';
			
				$i = !$i;
			}
		
			echo '</table>';

			$this->bestellingen_print_footer();
		}

		function totaalprijs($model, $memberid, &$nnb) {
			$bestellingen = $model->get_from_member($memberid);
			$total = 0;
			$nnb = false;
		
			foreach ($bestellingen as $bestelling) {
				if ($bestelling->get('prijs') == 0)
					$nnb = true;
			
				$total += $bestelling->get('prijs');
			}
		
			return $total;
		}

		function view_bestellingen_csv_by_member($model, $iters, $params = null) {
			$csv = csv_row(array(__('Lid'), __('Aantal'), __('Totaalprijs'))) . "\n";

			foreach ($iters as $iter) {
				$total = money_format('%.2n', $this->totaalprijs($model, $iter->get('id'), $nnb));
				$csv .= csv_row(array(member_full_name($iter), $iter->get('aantal_bestellingen'), $total . ($nnb ? '*' : ''))) . "\n";
			}
		
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header('Content-Disposition: attachment; filename="bestellingen_per_lid.csv"');
		
			echo $csv;
			exit();
		}

		function view_bestellingen_print_by_member($model, $iters, $params = null) {
			$this->bestellingen_print_header();

			echo '<h1>' . __('Aantal bestellingen per lid') . '</h1>
			<table class="boeken">
			<tr>
				<th>' . __('Lid') . '</th>
				<th width="20px">' . __('Aantal') . '</th>
				<th width="50px">' . __('Totaalprijs') . '</th>
			</tr>';
		
			$i = true;
			$star = false;

			foreach ($iters as $iter) {
				$class = 'r' . ($i ? '0' : '1');
			
				echo '<tr class="' . $class . '">
					<td>' . htmlspecialchars(member_full_name($iter)) . '</td>';
			
				echo '<td class="text_right">' . $iter->get('aantal_bestellingen') . '</td>';
			
				$total = money_format('%.2n', $this->totaalprijs($model, $iter->get('id'), $nnb));
			
				if ($nnb)
					$star = true;
			
				echo '<td class="text_right">&euro; ' . $total . ($nnb ? '*' : '') . '</td>
				</tr>';
			
				$i = !$i;
			}

			if ($star)
				echo '<tr class="submit"><td colspan="3"><span class="italic">* ' . __('De prijzen zijn niet van alle boeken al bekend, het totaalbedrag valt daarom lager uit dan het daadwerkelijke totaalbedrag') . '</span></td></tr>';
			
			echo '</table>';
				
			$this->bestellingen_print_footer();
		}

		function view_bestellingen_print($model, $iters, $params = null) {
			$this->bestellingen_print_header();

			echo '<h1>' . __('Bestellingen') . '</h1>
			<table class="boeken">
			<tr>
				<th>' . __('Boek') . '</th>
				<th>' . __('Lid') . '</th>
				<th width="20px">' . __('Prijs') . '</th>
			</tr>';
		
			$i = true;
		
			foreach ($iters as $iter) {
				$class = 'r' . ($i ? '0' : '1');
			
				echo '<tr class="' . $class . '">
					<td>' . htmlspecialchars($iter->get('titel')) . '</td>';
			
				echo '<td>' . htmlspecialchars(member_full_name($iter)) . '</td>
				<td class="text_right">'. ($iter->get('prijs') == 0 ? 'n.n.b.' : ('&euro; ' . money_format('%.2n', $iter->get('prijs')))) . '</td>
				</tr>';
			
				$i = !$i;
			}
		
			echo '</table>';
			$this->bestellingen_print_footer();
		}
	
		function view_bestellingen_csv($model, $iters, $params = null) {
			$csv = csv_row(array(__('Boek'), __('Lid'), __('Prijs'))) . "\n";

			foreach ($iters as $iter)			
				$csv .= csv_row(array($iter->get('titel'), member_full_name($iter), $iter->get('prijs') == 0 ? 'n.n.b.' : money_format('%.2n', $iter->get('prijs')))) . "\n";
		
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header('Content-Disposition: attachment; filename="bestellingen.csv"');
		
			echo $csv;
			exit();
		}
	
	}
?>
