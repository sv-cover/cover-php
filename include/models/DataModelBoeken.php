<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing boeken data
	  */
	class DataModelBoeken extends DataModel {
		function DataModelBoeken($db) {
			parent::DataModel($db, 'boeken');
		}
		
		function get() {
			if (!$this->db)
				return Array();

			$rows = $this->db->query('SELECT * FROM 
					boeken
					ORDER BY titel');
			
			return $this->_rows_to_iters($rows);	
		}
		
		/**
		  * Get all the available categories. If memberid is specified
		  * the function returns only those categories in which
		  * lidid is able to order books from (all the categories
		  * having books and where lidid hasn't already ordered
		  * all the books)
		  * @memberid optional; the member id to get the categories for
		  *
		  * @result an associative array where the key is the category
		  * id and the value is the category name
		  */		
		function get_categories($memberid = null) {
			if ($memberid !== null)
				$rows = $this->db->query('SELECT boeken_categorie.* 
					FROM boeken_categorie, boeken 
					LEFT JOIN bestellingen ON (boeken.id = bestellingen.boekid AND
					bestellingen.lidid = ' . $memberid . ')
					WHERE bestellingen.boekid IS NULL AND
					boeken_categorie.id = boeken.categorie AND
					boeken.status = 1
					ORDER BY id');
			else 
				$rows = $this->db->query('SELECT * 
						FROM boeken_categorie
						ORDER BY id');
			
			if (!$rows)
				return array();
			
			$result = array();
			
			foreach ($rows as $row)
				$result[$row['id']] = $row['categorie'];
		
			return $result;
		}
		
		/**
		  * Get all books for a certain member
		  * @id the member id to get books for
		  *
		  * @result an array of book #DataIter
		  */
		function get_from_member($id) {
			$rows = $this->db->query("SELECT boeken.* 
					FROM boeken, bestellingen 
					WHERE boeken.id = bestellingen.boekid AND 
					bestellingen.lidid = " . intval($id) . '
					ORDER BY boeken.vak, boeken.titel, boeken.auteur');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get all non-suspended books in a certain category
		  * @id the category id to get books for
		  *
		  * @result an array of book #DataIter
		  */
		function get_from_category($id) {
			$rows = $this->db->query("SELECT * 
					FROM boeken
					WHERE boeken.categorie = " . intval($id) . ' AND 
					boeken.status = 1');
			
			return $this->_rows_to_iters($rows);		
		}
		
		/**
		  * Get the number of orders done for a book
		  * @iter a book #DataIter
		  *
		  * @result the number of placed orders for the book
		  */
		function num_bestellingen($iter) {
			$num = $this->db->query_value("SELECT COUNT (*)
					FROM bestellingen
					WHERE boekid = " . $iter->get_id());
			
			return $num;
		}
		
		/**
		  * Insert a book #DataIter
		  * @iter the book #DataIter to insert
		  *
		  * @result true if the insert was successful, false otherwise
		  */
		function insert($iter) {
			$id = $this->db->query_value("SELECT MAX(id) + 1
					FROM boeken");
			
			
			if ($id === null)
				$id = 1;

			$iter->set('id', intval($id));
			return parent::insert($iter);
		}
		
		/**
		  * Get an order #DataIter
		  * @iter a book #DataIter to get the order for
		  * @memberid the member to get the order for
		  *
		  * @result an order #DataIter
		  */
		function get_bestelling($iter, $memberid) {
			$row = $this->db->query_first("SELECT * 
					FROM bestellingen
					WHERE boekid = " . $iter->get_id() . " AND 
					lidid = " . intval($memberid));
			
			return $this->_row_to_iter($row);
					
		}
		
		/**
		  * Get all orders in a certain order
		  * @order optional; the order in which to return the orders
		  * (boek, lid or prijs). Defaults to boek
		  *
		  * @result an array of #DataIter
		  */
		function get_bestellingen($order = 'boek') {
			$order_mapping = array('boek' => 'boeken.titel',
					'lid' => 'leden.voornaam, leden.tussenvoegsel, leden.achternaam',
					'prijs' => 'boeken.prijs');

			if (!isset($order_mapping[$order]))
				$order = 'boek';
			
			$order = $order_mapping[$order];

			$rows = $this->db->query("SELECT boeken.titel, 
					leden.voornaam, 
					leden.tussenvoegsel, 
					leden.achternaam, 
					boeken.prijs
					FROM leden, boeken, bestellingen
					WHERE bestellingen.lidid = leden.id AND
					bestellingen.boekid = boeken.id
					ORDER BY " . $order);
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the number of orders grouped by book
		  *
		  * @result an array of #DataIter
		  */
		function get_by_book() {
			$rows = $this->db->query("SELECT boeken.titel,
					COUNT(boeken.id) AS aantal
					FROM boeken, bestellingen
					WHERE bestellingen.boekid = boeken.id
					GROUP BY boeken.id, boeken.titel
					ORDER BY boeken.titel");
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the number of oders grouped by member
		  *
		  * @result an array of #DataIter
		  */
		function get_by_member() {
			$rows = $this->db->query("SELECT leden.id,
					leden.voornaam, 
					leden.tussenvoegsel, 
					leden.achternaam,
					COUNT(leden.id) AS aantal_bestellingen
					FROM leden, bestellingen
					WHERE bestellingen.lidid = leden.id
					GROUP BY leden.id, leden.voornaam, leden.tussenvoegsel, leden.achternaam
					ORDER BY leden.voornaam, leden.tussenvoegsel, leden.achternaam");
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Delete a book. This will also delete any orders for this
		  * book, so be careful
		  * @iter a book #DataIter to delete
		  *
		  * @result true if the delete was successful, false otherwise
		  */
		function delete($iter) {
			parent::delete($iter);
			
			/* Also delete all the bestellingen of this book */
			return $this->db->delete('bestellingen', 'boekid = ' . $iter->get('id'));
		}
		
		/**
		  * Delete an order
		  * @iter an order #DataIter to delete
		  *
		  * @result true if the deletion was successful, false otherwise
		  */
		function delete_bestelling($iter) {
			return $this->db->delete('bestellingen', 'boekid = ' . $iter->get('boekid') . ' AND lidid = ' . $iter->get('lidid'));
		}
		
		/**
		  * Delete all orders
		  *
		  * @result true if the deletion was successful, false otherwise
		  */
		function delete_bestellingen() {
			return $this->db->delete('bestellingen');
		}
		
		/**
		  * Insert an order
		  * @iter an order #DataIter to insert
		  *
		  * @result true if the insert was successful, false otherwise
		  */
		function insert_bestelling($iter) {
			return $this->db->insert('bestellingen', $iter->data, 
					$iter->get_literals());
		}
	}
?>
