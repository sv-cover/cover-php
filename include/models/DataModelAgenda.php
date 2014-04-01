<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing the Agenda data
	  */
	class DataModelAgenda extends DataModel {
		function DataModelAgenda($db) {
			parent::DataModel($db, 'agenda');
		}
		
		function get($from = null, $till = null, $confirmed_only = false) {

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
		
		function _generate_select() {
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
		
		function get_iter($id, $include_prive = true) {
			$row = $this->db->query_first("SELECT *, " . 
					$this->_generate_select() . "
					FROM agenda
					WHERE id = " . intval($id) . 
					(!$include_prive ? ' AND private = 0 ' : ''));
			
			if (!$row)
				return $row;
			
			$moderate = $this->db->query_first('SELECT *
					FROM agenda_moderate
					WHERE agendaid = ' . intval($id));
			
			if ($moderate !== null && $moderate !== false) {
				$row['moderate'] = $moderate['agendaid'];
				$row['overrideid'] = $moderate['overrideid'];
			}
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get the currently relevant agendapunten
		  * @include_prive optional; whether to also get the private
		  * agendapunten
		  * @result an array of #DataIter with the currently
		  * relevant agendapunten
		  */
		function get_agendapunten($include_prive = false) {
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
		
		function _update_moderate($iter, $override) {
			return $this->db->update('agenda_moderate', 
					array('overrideid' => intval($override)), 
					'agendaid = ' . $iter->get_id());
		}
		
		function _insert_moderate($iter, $override) {
			return $this->db->insert(
					'agenda_moderate', 
					array('agendaid' => $iter->get_id(),
						'overrideid' => intval($override)));
		}
		
		function _delete_moderate($iter) {
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
		function set_moderate($id, $override, $moderate) {
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
		
		function delete($iter) {
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
		function has_moderate() {
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
		function get_moderates() {
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
		function get_for_commissie($id, $include_priv = false) {
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
	}
?>
