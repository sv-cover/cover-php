<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing gastenboek data
	  */
	class DataModelGastenboek extends DataModel {
		var $posts_per_page = 15;
		var $current_page = 0;
		var $condition = '';

		public function __construct($db)
		{
			parent::__construct($db, 'gastenboek');
		}
		
		function _get_condition() {
			if (!$this->condition)
				return '';
			
			return " AND message ILIKE '%" . $this->db->escape_string($this->condition) . "%'";
		}
		
		/**
		  * Get a certain page. This function will automatically
		  * clip the page number if it exceeds the minimum or maximum
		  * page. It will also use the condition object variable to
		  * return a page with matched messages
		  * @page the page to get
		  *
		  * @result an array of #DataIter
		  */
		function _get_page($page) {
			$max = $this->get_max_pages();

			$page = min($max, max(0, intval($page)));
			$this->current_page = $page;
			
			$rows = $this->db->query("SELECT *, 
					DATE_PART('dow', date) AS dagnaam, 
					DATE_PART('day', date) AS datum, 
					DATE_PART('month', date) AS maand, 
					DATE_PART('hours',date) AS uur, 
					DATE_PART('minutes',date) AS minuut
					FROM gastenboek WHERE spam = 0 " . $this->_get_condition() . '
					ORDER BY id DESC LIMIT ' . $this->posts_per_page . ' OFFSET ' . ($page * $this->posts_per_page));

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Returns gastenboek messages that are 
		  * matched against a certain condition at a certain page
		  * @condition the condition to match entries against
		  * @page the page number to retrieve
		  *
		  * @result an array of #DataIter
		  */		
		function search($condition, $page) {
			$this->condition = $condition;

			return $this->_get_page($page);
		}
		
		/**
		  * Return gastenboek messages at a certain page
		  * @page the page to return message for
		  *
		  * @result 
		  */
		function get($page) {
			$this->condition = '';			
			
			return $this->_get_page($page);
		}
		
		/**  
		  * Get total number of gastenboek posts
		  *
		  * @result integer with number of gastenboek posts
		  */
		function get_total_posts() {
			static $total = null;
			if ($total == null)
				$total = $this->db->query_value('SELECT COUNT(*) AS num FROM gastenboek WHERE spam = 0 ' . $this->_get_condition());
				
			return $total;
		}		
		
		/**
		  * Get the maximum number of gastenboek pages
		  *
		  * @result the number of gastenboek pages
		  */
		function get_max_pages() {
			static $max = null;
			
			if ($max !== null)
				return $max;
			
			$num = $this->get_total_posts();
			$max = intval(ceil($num / floatval($this->posts_per_page)));
			
			return $max;
		}
		
		/**
		  * Get the number of spam messages
		  *
		  * @result the number of spam messages
		  */
		function get_spam_count() {
			$model = get_model('DataModelConfiguratie');
			return intval($model->get_value("spam_count"));
		}
		
		/**
		 * Increases the spam counter by 1
		 *
		 *
		 * @result void
		 * @author Pieter de Bie
		 **/
		function increase_spam_count() {
			$model = get_model('DataModelConfiguratie');
			$count = $model->get_value("spam_count");
			if (is_numeric($count))
				$count = intval($count);
			else
				$count = 0;

			$count++;
			$model->set_value("spam_count", $count);
		}
		/**
		  * Get the anti spam hash
		  *
		  * @result the anti spam hash
		  */
		function anti_spam_hash() {
			return md5($_SERVER['REMOTE_ADDR'] . 'stommespam!');
		}
	}
?>
