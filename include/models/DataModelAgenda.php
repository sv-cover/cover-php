<?php
	require_once 'include/data/DataModel.php';
	require_once 'include/search.php';
	
	class DataIterAgenda extends DataIter implements SearchResult
	{
		public function get_search_type()
		{
			return 'agendapunt';
		}
	}

	class DataModelAgenda extends DataModel implements SearchProvider
	{
		public $include_private = false;

		public $dataiter = 'DataIterAgenda';

		public function __construct($db)
		{
			parent::__construct($db, 'agenda');

			$this->include_private = logged_in();
		}
		
		public function get($from = null, $till = null, $confirmed_only = false) {

			$conditions = array();

			if ($from !== null)
				$conditions[] = "agenda.tot >= date '$from'";

			if ($till !== null)
				$conditions[] = "agenda.tot < date '$till'";

			if ($confirmed_only)
				$conditions[] = "agenda.id NOT IN (SELECT a_m.agendaid FROM agenda_moderate a_m)";

			$where_clause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

			$rows = $this->db->query('
					SELECT
						agenda.*, ' . $this->_generate_select() . '
					FROM
						agenda
					' . $where_clause . '
					ORDER BY
						van ASC');
			
			return $this->_rows_to_iters($rows);
		}
		
		protected function _generate_select() {
			return "DATE_PART('dow', agenda.van) AS vandagnaam, 
				DATE_PART('day', agenda.van) AS vandatum, 
				DATE_PART('year', agenda.van) AS vanjaar,
				DATE_PART('month', agenda.van) AS vanmaand, 
				DATE_PART('hours', agenda.van) AS vanuur, 
				DATE_PART('minutes', agenda.van) AS vanminuut, 
				DATE_PART('dow', agenda.tot) AS totdagnaam, 
				DATE_PART('year', agenda.tot) AS totjaar, 
				DATE_PART('day', agenda.tot) AS totdatum, 
				DATE_PART('month', agenda.tot) AS totmaand, 
				DATE_PART('hours', agenda.tot) AS totuur, 
				DATE_PART('minutes', agenda.tot) AS totminuut";
		}
		
		public function get_iter($id, $include_prive = true)
		{
			$row = $this->db->query_first("SELECT *, " . 
					$this->_generate_select() . ",
					a_m.agendaid as moderate,
					a_m.overrideid as overrideid
					FROM agenda
					LEFT JOIN agenda_moderate a_m ON
						a_m.agendaid = agenda.id
					WHERE id = " . intval($id) . 
					(!$include_prive ? ' AND private = 0 ' : '')
					. " GROUP BY agenda.id, a_m.agendaid, a_m.overrideid");
			
			if (!$row)
				throw new DataIterNotFoundException($id);

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get the currently relevant agendapunten
		  * @include_prive optional; whether to also get the private
		  * agendapunten
		  * @result an array of #DataIter with the currently
		  * relevant agendapunten
		  */
		public function get_agendapunten($include_prive = false)
		{
			static $agendapunten = null;
			static $agendapunten_prive = null;
			
			if (!$include_prive && $agendapunten != null)
				return $agendapunten;
			elseif ($include_prive && $agendapunten_prive != null)
				return $agendapunten_prive;
			
			$punten = $this->db->query("SELECT
					agenda.*,
					c.naam as commissie__naam,
					c.page as commissie__page,
					" . $this->_generate_select() . "
					FROM agenda
					LEFT JOIN commissies c ON c.id = agenda.commissie
					WHERE (agenda.tot > CURRENT_TIMESTAMP OR (CURRENT_TIMESTAMP < agenda.van + interval '1 day') OR 
					(DATE_PART('hours', agenda.van) = 0 AND CURRENT_TIMESTAMP < agenda.van + interval '1 day')) AND 
					agenda.id NOT IN (SELECT agendaid FROM agenda_moderate) " .
					(!$include_prive ? ' AND agenda.private = 0 ' : '') . "
					ORDER BY agenda.van ASC");

			if ($include_prive) {
				$agendapunten_prive = $this->_rows_to_iters($punten);
				return $agendapunten_prive;
			} else {
				$agendapunten = $this->_rows_to_iters($punten);
				return $agendapunten;
			}
		}
		
		protected function _update_moderate(DataIter $iter, $override)
		{
			return $this->db->update('agenda_moderate', 
					array('overrideid' => intval($override)), 
					'agendaid = ' . $iter->get_id());
		}
		
		protected function _insert_moderate(DataIter $iter, $override)
		{
			return $this->db->insert(
					'agenda_moderate', 
					array('agendaid' => $iter->get_id(),
						'overrideid' => intval($override)));
		}
		
		protected function _delete_moderate(DataIter $iter)
		{
			return $this->db->delete('agenda_moderate',
					'agendaid = ' . $iter->get_id());
		}
		
		/**
		  * Set/unset an agendapunt to/from the need-moderation state
		  * @id the id of the agendapunt
		  * @moderate whether to set or unset
		  *
		  * @result true if setting the need-moderation was successul
		  */
		public function set_moderate($id, $override, $moderate)
		{
			$iter = $this->get_iter($id);
			
			if (!$iter)
				return false;
			
			/* Check if the moderate state is already what
			 * is being requested
			 */
			if ($iter->get('moderate') == $moderate && $iter->get('override') == $override)
				return true;
			
			/* Update the moderate state */
			if ($iter->get('moderate') == $moderate)
				return $this->_update_moderate($iter, $override);
			elseif ($moderate)
				return $this->_insert_moderate($iter, $override);
			else
				return $this->_delete_moderate($iter);
		}
		
		public function delete(DataIter $iter)
		{
			/* Remove the possible moderation */
			$this->set_moderate($iter->get_id(), 0, false);
			
			/* Chain up */
			parent::delete($iter);
		}
		
		/**
		  * Returns whether there are agendapunten that need moderation
		  *
		  * @result false if there are no agendapunten that need 
		  * moderation and the number of agendapunten that need
		  * moderation otherwise
		  */
		public function has_moderate()
		{
			$rows = $this->db->query('SELECT * FROM agenda_moderate');
			
			if (!$rows || count($rows) == 0)
				return false;
			else
				return count($rows);
		}
		
		/**
		  * Get all the agendapunten that need moderation
		  *
		  * @result an array of #DataIter
		  */
		public function get_moderates()
		{
			$rows = $this->db->query("SELECT agenda.*, agenda_moderate.overrideid, " .
				$this->_generate_select() . " 
				FROM agenda, agenda_moderate
				WHERE agenda.id = agenda_moderate.agendaid
				ORDER BY agenda.van ASC");

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Gets agendapunten of a specific commissie
		  * @id the commissie id
		  * @include_priv optional; whether or not include private
		  * agendapunten
		  *
		  * @result an array of #DataIter
		  */
		public function get_for_commissie($id, $include_prive = false)
		{
			$rows = $this->db->query("SELECT *, " .
					$this->_generate_select() . "
					FROM agenda 
					WHERE (tot > CURRENT_TIMESTAMP OR 
					(DATE_PART('hours', van) = 0 AND 
					CURRENT_TIMESTAMP < van + interval '1 day')) AND 
					id NOT IN (SELECT agendaid FROM agenda_moderate) AND 
					commissie = " . $id . 
					(!$include_prive ? ' AND private = 0 ' : '') . "
					ORDER BY van ASC");
		}

		public function search($keywords, $limit = null)
		{
			$keywords = parse_search_query($keywords);

			$search_atoms = array_map(function($keyword) {
				return sprintf("(agenda.kop ILIKE '%%%s%%' OR agenda.beschrijving ILIKE '%%%1\$s%%')",
					$this->db->escape_string($keyword));
			}, $keywords);

			$query = "
				SELECT
					agenda.*,
					c.naam as commissie__naam,
					c.page as commissie__page,
					" . $this->_generate_select() . "
				FROM
					agenda
				LEFT JOIN commissies c ON
					c.id = agenda.commissie
				WHERE
					agenda.id NOT IN (SELECT agendaid FROM agenda_moderate)
					" . (!$this->include_private ? ' AND agenda.private = 0 ' : '') . "
					AND " . implode(' AND ', $search_atoms) . "
				ORDER BY
					agenda.van DESC
				" . ($limit !== null ? " LIMIT " . intval($limit) : "");

			$rows = $this->db->query($query);

			return $this->_rows_to_iters($rows);
		}
	}
