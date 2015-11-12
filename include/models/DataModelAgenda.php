<?php
	require_once 'include/data/DataModel.php';
	require_once 'include/search.php';
	
	class DataIterAgenda extends DataIter implements SearchResult
	{
		public function get_search_relevance()
		{
			return normalize_search_rank($this->get('search_relevance'));
		}

		public function get_search_type()
		{
			return 'agendapunt';
		}

		public function get_absolute_url()
		{
			return sprintf('agenda.php?agenda_id=%d', $this->get_id());
		}

		public function is_proposal()
		{
			return $this->get('replacement_for') !== null;
		}
	}

	class DataModelAgenda extends DataModel implements SearchProvider
	{
		public $include_private = false;

		public $dataiter = 'DataIterAgenda';

		public $fields = [
			'id',
			'kop',
			'beschrijving',
			'commissie',
			'van',
			'tot',
			'locatie',
			'private',
			'lustrum',
			'extern',
			'facebook_id',
			'replacement_for'
		];

		public function __construct($db)
		{
			parent::__construct($db, 'agenda');

			$this->include_private = logged_in();
		}
		
		public function get($from = null, $till = null, $confirmed_only = false)
		{
			$conditions = array();

			if ($from !== null)
				$conditions[] = "agenda.tot >= date '$from'";

			if ($till !== null)
				$conditions[] = "agenda.tot < date '$till'";

			if ($confirmed_only)
				$conditions[] = "agenda.replacement_for IS NULL";

			$where_clause = implode(' AND ', $conditions);

			return $this->find($where_clause);
		}
		
		protected function _generate_query($where)
		{
			return "
				SELECT
					{$this->table}.*,
					DATE_PART('dow', {$this->table}.van) AS vandagnaam, 
					DATE_PART('day', {$this->table}.van) AS vandatum, 
					DATE_PART('year', {$this->table}.van) AS vanjaar,
					DATE_PART('month', {$this->table}.van) AS vanmaand, 
					DATE_PART('hours', {$this->table}.van) AS vanuur, 
					DATE_PART('minutes', {$this->table}.van) AS vanminuut, 
					DATE_PART('dow', {$this->table}.tot) AS totdagnaam, 
					DATE_PART('year', {$this->table}.tot) AS totjaar, 
					DATE_PART('day', {$this->table}.tot) AS totdatum, 
					DATE_PART('month', {$this->table}.tot) AS totmaand, 
					DATE_PART('hours', {$this->table}.tot) AS totuur, 
					DATE_PART('minutes', {$this->table}.tot) AS totminuut,
					commissies.naam as commissie__naam,
					commissies.page as commissie__page
				FROM
					{$this->table}
				LEFT JOIN commissies ON
					commissies.id = agenda.commissie"
				. ($where ? " WHERE {$where}" : "")
				. " ORDER BY {$this->table}.van ASC";
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

			$conditions = "
				(
					(agenda.tot > CURRENT_TIMESTAMP)
					OR (CURRENT_TIMESTAMP < agenda.van + interval '1 day')
					OR  (
							DATE_PART('hours', agenda.van) = 0
							AND CURRENT_TIMESTAMP < agenda.van + interval '1 day'
						)
				)
				AND 
					agenda.replacement_for IS NULL";

			if (!$include_prive)
				$conditions .= ' AND agenda.private = 0';

			$punten = $this->find($conditions);
			
			if ($include_prive)
				return $agendapunten_prive = $punten;
			else
				return $agendapunten = $punten;
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
					agenda.replacement_for IS NULL AND 
					commissie = " . $id . 
					(!$include_prive ? ' AND private = 0 ' : '') . "
					ORDER BY van ASC");
		}

		public function search($keywords, $limit = null)
		{
			$ts_query = implode(' & ', parse_search_query($keywords));

			$query = "
				SELECT
					agenda.*,
					{$this->table}.*,
					DATE_PART('dow', {$this->table}.van) AS vandagnaam, 
					DATE_PART('day', {$this->table}.van) AS vandatum, 
					DATE_PART('year', {$this->table}.van) AS vanjaar,
					DATE_PART('month', {$this->table}.van) AS vanmaand, 
					DATE_PART('hours', {$this->table}.van) AS vanuur, 
					DATE_PART('minutes', {$this->table}.van) AS vanminuut, 
					DATE_PART('dow', {$this->table}.tot) AS totdagnaam, 
					DATE_PART('year', {$this->table}.tot) AS totjaar, 
					DATE_PART('day', {$this->table}.tot) AS totdatum, 
					DATE_PART('month', {$this->table}.tot) AS totmaand, 
					DATE_PART('hours', {$this->table}.tot) AS totuur, 
					DATE_PART('minutes', {$this->table}.tot) AS totminuut,
					commissies.naam as commissie__naam,
					commissies.page as commissie__page,
					ts_rank_cd(
						setweight(to_tsvector(agenda.kop), 'A') || setweight(to_tsvector(agenda.beschrijving), 'B'),
						to_tsquery('" . $this->db->escape_string($ts_query) . "')
					) as search_relevance
				FROM
					agenda
				LEFT JOIN commissies ON
					commissies.id = agenda.commissie
				WHERE
					agenda.replacement_for IS NULL
					" . (!$this->include_private ? ' AND agenda.private = 0 ' : '') . "
					AND (setweight(to_tsvector(agenda.kop), 'A') || setweight(to_tsvector(agenda.beschrijving), 'B')) @@ to_tsquery('" . $this->db->escape_string($ts_query) . "')
				ORDER BY
					agenda.van DESC
				" . ($limit !== null ? " LIMIT " . intval($limit) : "");

			$rows = $this->db->query($query);

			return $this->_rows_to_iters($rows);
		}

		public function delete(DataIter $iter)
		{
			/* Remove the possible moderation */
			foreach ($this->get_proposed() as $proposed_update)
				if ($proposed_update->get('replacement_for') == $iter->get_id())
					$this->reject_proposal($proposed_update);
			
			/* Chain up */
			parent::delete($iter);
		}

		public function propose_insert(DataIterAgenda $new_item)
		{
			if ($new_item->has_id())
				throw new InvalidArgumentException('How come the proposed insert already has an id?');
			
			$new_item->set('replacement_for', 0);
			return $this->insert($new_item, true);
		}
		
		public function propose_update(DataIterAgenda $replacement, DataIterAgenda $current)
		{
			if (!$current->has_id())
				throw new InvalidArgumentException('The item to replace has no id');

			if ($replacement->has_id())
				throw new InvalidArgumentException('How come the proposed replacement already has an id?');
			
			$replacement->set('replacement_for', $current->get_id());
			return $this->insert($replacement, true);
		}

		public function accept_proposal(DataIterAgenda $proposal)
		{
			if (!$proposal->is_proposal())
				throw new InvalidArgumentException('Given agenda item iter is not a proposed update');

			// It is not a replacement, just a proposal for an insert
			if ($proposal->get('replacement_for') == 0)
			{
				$proposal->set('replacement_for', null);
				$proposal->update();
			}
			// It is an update: replace the contents of the old item (to preserve the id)
			// and throw away the proposal afterwards.
			else
			{
				$current = $this->get_iter($proposal->get('replacement_for'));

				// Copy everything but the item id and its update proposal data
				foreach (array_diff($this->fields, ['id', 'replacement_for']) as $field)
					$current->set($field, $proposal->get($field));

				$this->update($current);

				$this->delete($proposal);
			}
		}

		public function reject_proposal(DataIterAgenda $proposal)
		{
			$this->delete($proposal);
		}

		public function get_proposed()
		{
			return $this->find("{$this->table}.replacement_for IS NOT NULL");
		}
	}
