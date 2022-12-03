<?php
	require_once 'src/framework/data/DataModel.php';
	require_once 'src/framework/search.php';
	require_once 'src/framework/router.php';

	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
	
	class DataIterAgenda extends DataIter implements SearchResult
	{
		static public function fields()
		{
			return [
				'id',
				'kop',
				'beschrijving',
				'committee_id',
				'van',
				'tot',
				'locatie',
				'image_url',
				'private',
				'extern',
				'facebook_id',
				'replacement_for'
			];
		}

		public function get_search_relevance()
		{
			return normalize_search_rank($this->data['search_relevance']);
		}

		public function get_search_type()
		{
			return 'agendapunt';
		}

		public function get_absolute_path($url = false)
		{
			$router = get_router();
			$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
			return $router->generate('calendar', ['agenda_id' => $this->get_id()], $reference_type);
		}

		public function get_van_datetime()
		{
			return $this['van'] ? new DateTime($this['van']) : null;
		}

		public function get_tot_datetime()
		{
			return $this['tot'] ? new DateTime($this['tot']) : null;
		}

		public function is_proposal()
		{
			return $this->get('replacement_for') !== null;
		}

		public function get_proposals()
		{
			return $this->model->get_proposed($this);
		}

		public function get_use_tot()
		{
			return $this['van'] != $this['tot'];
		}

		public function get_use_locatie()
		{
			return $this['locatie'];
		}

		public function get_image($width=null)
		{
			return get_filemanager_url($this['image_url'], $width);
		}

		public function get_committee()
		{
			return get_model('DataModelCommissie')->get_iter($this->data['committee_id']);
		}

		public function get_signup_forms()
		{
			return get_model('DataModelSignUpForm')->find(['agenda_id' => $this['id']]);
		}

		public function new_signup_form()
		{
			return get_model('DataModelSignUpForm')->new_iter([
				'committee_id' => $this['committee_id'],
				'agenda_id' => $this['id']
			]);
		}
	}

	class DataModelAgenda extends DataModel implements SearchProvider
	{
		public $include_private = false;

		public $dataiter = 'DataIterAgenda';

		public function __construct($db)
		{
			parent::__construct($db, 'agenda');

			$this->include_private = get_identity()->is_member() || get_identity()->is_donor();
		}
		
		public function get($from = null, $till = null, $confirmed_only = false)
		{
			$conditions = array();

			if ($from !== null)
				$conditions['van__gte'] = $from;

			if ($till !== null)
				$conditions['tot__lt'] = $till;

			if ($confirmed_only)
				$conditions['replacement_for__isnull'] = true;

			return $this->find($conditions);
		}
		
		protected function _generate_query($where)
		{
			if (is_array($where))
				$where = $this->_generate_conditions_from_array($where);
			
			return "
				SELECT
					{$this->table}.*,
					commissies.naam as committee__naam,
					commissies.login as committee__login,
					commissies.page_id as committee__page_id
				FROM
					{$this->table}
				LEFT JOIN commissies ON
					commissies.id = agenda.committee_id"
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
		public function get_agendapunten()
		{
			return $this->find("
				CAST(agenda.van as DATE) >= CURRENT_DATE -- activities in the future
				OR CAST(agenda.tot as DATE) >= CURRENT_DATE -- activities currently ongoing
			");
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
			$fields = implode(', ', array_map(function ($field) { return "a.$field"; }, call_user_func([$this->dataiter, 'fields'])));

			$query = "
				WITH
					search_items AS (
						SELECT
							id,
							setweight(to_tsvector(kop), 'A') || setweight(to_tsvector(beschrijving), 'B') body
						FROM
							{$this->table}
						WHERE
							replacement_for IS NULL
							" . (!$this->include_private ? ' AND private = 0 ' : '') . "
					),
					matching_items AS (
						SELECT
							id,
							body,
							ts_rank_cd(body, query) as search_relevance
						FROM
							search_items,
							plainto_tsquery('english', :keywords) query
						WHERE
							body @@ query
					)
				SELECT
					a.*,
					c.naam as committee__naam,
					c.page_id as committee__page_id,
					m.search_relevance
				FROM
					matching_items m
				LEFT JOIN {$this->table} a ON
					a.id = m.id
				LEFT JOIN commissies c ON
					c.id = a.committee_id
				ORDER BY
					a.van DESC
				" . ($limit !== null ? " LIMIT " . intval($limit) : "");

			$rows = $this->db->query($query, false, [':keywords' => $keywords]);

			return $this->_rows_to_iters($rows);
		}

		public function delete(DataIter $iter)
		{
			/* Remove the possible moderation */
			foreach ($this->get_proposed() as $proposed_update)
				if ($proposed_update->get('replacement_for') == $iter->get_id())
					$this->reject_proposal($proposed_update);
			
			/* Chain up */
			return parent::delete($iter);
		}

		public function propose_insert(DataIterAgenda $new_item)
		{
			if ($new_item->has_id())
				throw new InvalidArgumentException('How come the proposed insert already has an id?');
			
			$new_item->set('replacement_for', 0);
			return $this->insert($new_item);
		}
		
		public function propose_update(DataIterAgenda $iter)
		{
			if (!$iter->has_id())
				throw new InvalidArgumentException('The item to replace has no id');
			
			$iter->set('replacement_for', $iter->get_id());
			$iter->set_id(null);
			return $this->insert($iter);
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
				foreach (array_diff(DataIterAgenda::fields(), ['id', 'replacement_for']) as $field)
					$current->set($field, $proposal->get($field));

				$this->update($current);

				$this->delete($proposal);
			}
		}

		public function reject_proposal(DataIterAgenda $proposal)
		{
			$this->delete($proposal);
		}

		public function get_proposed(DataIterAgenda $replacements_for = null)
		{
			return $replacements_for === null
				? $this->find("{$this->table}.replacement_for IS NOT NULL")
				: $this->find(sprintf("{$this->table}.replacement_for = %d", $replacements_for['id']));
		}

		public function find_locations($query, $limit = null)
		{
			$sql_term = $this->db->escape_string($query);

			$rows = $this->db->query("
				SELECT locatie
				FROM {$this->table}
				WHERE locatie ILIKE '%{$sql_term}%'
				  AND van > (CURRENT_TIMESTAMP - INTERVAL '2 year')
				GROUP BY locatie
				ORDER BY COUNT(id) DESC"
				. ($limit !== null
					? sprintf(' LIMIT %d', $limit)
					: ''));

			return array_select($rows, 'locatie');
		}
	}
