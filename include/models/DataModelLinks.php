<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing links data
	  */
	class DataModelLinks extends DataModel {
		function DataModelLinks($db) {
			parent::DataModel($db, 'links');
		}
		
		/**
		  * Get all the link categories
		  *
		  * @result an array of #DataIter
		  */
		function get_categories() {
			$rows = $this->db->query('SELECT * 
					FROM links_categorie
					ORDER BY "order"');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get all the links which need moderation
		  *
		  * @result an array of #DataIter
		  */
		function get_moderates() {
			$rows = $this->db->query('SELECT links.*, links_categorie.titel AS cattitel
					FROM links, links_categorie
					WHERE moderated = 0 AND
					links.categorie = links_categorie.id
					ORDER BY links_categorie.titel, links.titel');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get all links in a certain category
		  * @category the id of the category to get the links for
		  * @moderated optional; whether or not to only get links that
		  * are moderated
		  *
		  * @result an array of #DataIter
		  */
		function get_links($category, $moderated = true) {
			$rows = $this->db->query('SELECT *
					FROM links
					WHERE categorie = ' . intval($category) . 
					($moderated ? ' AND moderated = 1 ' : '') . '
					ORDER BY titel');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the name of a category
		  * @category the category id
		  *
		  * @result the name of the category
		  */
		function get_categorie_naam($category) {
			return $this->db->query_value('SELECT titel
					FROM links_categorie
					WHERE id = ' . intval($category));
		}
		
		/**
		  * Update a link category
		  * @iter the #DataIter representing the category changes
		  *
		  * @result true if the update was successful, false otherwise
		  */
		function update_category($iter) {
			return $this->db->update('links_categorie', 
					$iter->get_changed_values(), 
					$this->_id_string($iter->get_id()), 
					$iter->get_literals());
		}
	
		/**
		  * Delete a link category
		  * @iter the #DataIter representing the category to delete
		  *
		  * @result true if the delete was successful, false otherwise
		  */
		function delete_category($iter) {
			return $this->db->delete('links_categorie', $this->_id_string($iter->get_id()));
		}

		/**
		  * Insert a link category
		  * @iter the #DataIter representing the category to insert
		  *
		  * @result true if the insertion was successful, false otherwise
		  */
		function insert_category($iter) {
			return $this->db->insert('links_categorie', $iter->data, 
					$iter->get_literals());
		}
		
		/**
		  * Returns whether a category exists or not
		  * @id the id of the category to check
		  *
		  * @result true if the category exists, false otherwise
		  */
		function category_exists($id) {
			$row = $this->db->query_first('SELECT id 
					FROM links_categorie
					WHERE id = ' . intval($id));
			
			return ($row !== null);
		}
	}
?>
