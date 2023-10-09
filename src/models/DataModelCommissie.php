<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';
require_once 'src/models/DataModelMember.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterCommissie extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
			'type',
			'naam',
			'login',
			'website',
			'page_id',
			'hidden',
			'vacancies',
		];
	}

	public function get_page()
	{
		return get_model('DataModelEditable')->get_iter($this['page_id']);
	}

	public function get_member_ids()
	{
		return $this->model->get_member_ids($this);
	}

	public function get_members()
	{
		return $this->model->get_members($this);
	}

	public function set_members(array $members)
	{
		return $this->model->set_members($this, $members);
	}

	public function get_mascots()
	{
		return get_model('DataModelCommitteeMascot')->find_for_committee($this);
	}

	public function get_summary()
	{
		$editable_model = get_model('DataModelEditable');
		return $editable_model->get_summary($this['page_id']);
	}

	public function get_search_relevance()
	{
		return floatval($this->data['search_relevance']);
	}

	public function get_search_type()
	{
		return 'committee';
	}

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('committees', ['commissie' => $this->get('login')], $reference_type);
	}

	public function has_vacancy_deadline()
	{
		return strtotime($this->get('vacancies')) < strtotime('+1 year');
	}

	public function has_vacancy()
	{
		return $this['vacancies'] && strtotime($this['vacancies']) > time();
	}

	public function get_email()
	{
		return strstr($this['login'], '@') ? $this['login'] : $this['login'] . '@svcover.nl';
	}

	public function get_email_addresses()
	{
		return get_model('DataModelCommissie')->get_email_addresses($this);
	}

	public function is_type($type)
	{
		if ($type === 'committee')
			return $this['type'] === DataModelCommissie::TYPE_COMMITTEE;
		elseif ($type === 'working_group')
			return $this['type'] === DataModelCommissie::TYPE_WORKING_GROUP;
		else
			return $this['type'] === DataModelCommissie::TYPE_OTHER;
	}
}

/**
 * A class implementing the Commissie data
 */
class DataModelCommissie extends DataModel implements SearchProvider
{
	const TYPE_COMMITTEE = 1;
	const TYPE_WORKING_GROUP = 2;
	const TYPE_OTHER = 3;

	public $dataiter = 'DataIterCommissie';

	public function __construct($db)
	{
		parent::__construct($db, 'commissies');
	}

	/**
	 * Get all commissies (optionally leaving out bestuur)
	 * @include_bestuur optional; whether or not to include
	 * bestuur
	 *
	 * @result an array of #DataIter
	 */
	public function get($type = null, $include_hidden = false)
	{
		$conditions = [];

		if (!$include_hidden)
			$conditions['hidden__ne'] = 1;

		if ($type !== null)
			$conditions['type'] = $type;

		return $this->find($conditions);
	}

	protected function _generate_query($where)
	{
		if (is_array($where))
			$where = $this->_generate_conditions_from_array($where);

		return "SELECT
				{$this->table}.*,
				COUNT(DISTINCT c_m.member_id) as member_count
			FROM
				{$this->table}
				LEFT JOIN committee_members c_m ON
					c_m.committee_id = {$this->table}.id"
			. ($where ? " WHERE {$where}" : "")
			. " GROUP BY {$this->table}.id"
			. " ORDER BY naam ASC";
	}

	public function insert(DataIter $iter)
	{
		if ($iter['vacancies'] === '')
			$iter['vacancies'] = null;
		
		$iter['login'] = preg_replace('[^a-z0-9]', '', strtolower($iter['naam']));

		$committee_id = parent::insert($iter);

		// Create the page for this committee
		$editable_model = get_model('DataModelEditable');

		$page_data = [
			'committee_id' => $committee_id,
			'titel' => $iter['naam']
		];

		$page = $editable_model->new_iter($page_data);

		$page_id = $editable_model->insert($page);

		$this->db->update($this->table, array('page_id' => $page_id), $this->_id_string($committee_id), array());

		return $committee_id;
	}

	public function update(DataIter $iter)
	{
		if ($iter->has_value('vacancies') && !$iter->get('vacancies'))
			$iter->set('vacancies', null);

		if ($iter->has_value('hidden'))
			$iter->set('hidden', (int) $iter->get('hidden'));
		
		return parent::update($iter);
	}

	public function delete(DataIter $iter)
	{
		get_db()->beginTransaction();

		try {
			// Save a reference to the page because I'm going to change page_id
			$page = $iter['page'];

			// Unset the page to prevent foreign key constraints
			$iter['page_id'] = null;
			parent::update($iter);

			// Remove committee page
			$editable_model = get_model('DataModelEditable');
			$editable_model->delete($page);
	
			// Remove members from committee
			$this->set_members($iter, array());

			$result = parent::delete($iter);

			get_db()->commit();
		} catch (Exception $e) {
			get_db()->rollback();
			throw $e;
		}

		return $result;
	}
	
	public function get_functies()
	{
		static $functies = array(
			'Chairman' => 6,
			'Chairwoman' => 6,
			'Chairperson' => 6,
			'Chair' => 6,
			'President' => 6,
			'Praeses' => 6,
			'Voorzitter' => 6,
			'Secretary' => 5,
			'Ab-actis' => 5,
			'Secretaris' => 5,
			'Treasurer' => 4,
			'Penningmeester' => 4,
			'Quaestor' => 4,
			'Fiscus' => 4,
			'Commissioner of Internal Affairs' => 3,
			'Commissioner of External Affairs' => 2,
			'Commissioner of Educational Affairs' => 1,
			'Vice-chairman' => 0,
			'Vice-chairwoman' => 0,
			'Vice-chairperson' => 0,
			'Vice-chair' => 0,
			'Vice-president' => 0,
			'Vice-praeses' => 0,
			'General Member' => -1);
		
		return $functies;
	}

	protected function _get_functie($functie)
	{
		$functies = array_combine(
			array_map('strtolower', array_keys($this->get_functies())),
			array_values($this->get_functies()));

		$functie = strtolower($functie);
		
		return isset($functies[$functie]) ? $functies[$functie] : 0;
	}

	protected function _split_functie($functie)
	{
		$pattern = '/\s*[,\/&]|and\s*/';
		return preg_split($pattern, $functie);
	}
	
	protected function _sort_leden($a, $b)
	{
		$afunctie = max(array_map(array($this, '_get_functie'), $this->_split_functie($a['functie'])));
		$bfunctie = max(array_map(array($this, '_get_functie'), $this->_split_functie($b['functie'])));
		
		return $bfunctie <=> $afunctie;
	}
	
	private function _get_members(DataIterCommissie $committee)
	{
		$rows = $this->db->query('SELECT member_id, functie FROM committee_members WHERE committee_id = ' . $committee->get_id());

		return array_combine(
			array_map(function($row) { return $row['member_id']; }, $rows),
			array_map(function($row) { return $row['functie']; }, $rows));
	}
	
	public function get_member_ids(DataIterCommissie $committee)
	{
		return array_keys($this->_get_members($committee));
	}

	/**
	 * Get all members of a specific commissie
	 * @id the commissie id
	 *
	 * @result an array of #DataIter
	 */
	public function get_members(DataIterCommissie $committee)
	{
		if (!$committee->has_id())
			return [];

		$member_model = get_model('DataModelMember');

		$positions = $this->_get_members($committee);

		if (count($positions) === 0)
			return [];

		$ids = array_keys($positions);

		$members = $member_model->find('leden.id IN (' . implode(', ', $ids) . ')');
		
		// Attach the committee positions to all its members
		// Not using 'set' here because that would mess up the DataIter::changed_fields()
		foreach ($members as $member)
			$member->data['functie'] = $positions[$member['id']];

		/* Sort by function */
		usort($members, array(&$this, '_sort_leden'));

		return $members;
	}

	public function get_lid_for_functie($commissie_id, $functie)
	{
		$committee = $this->get_iter($commissie_id);

		$leden = $this->get_members($committee);

		foreach ($leden as $lid)
			foreach ($this->_split_functie($lid->get('functie')) as $f)
				if (strcasecmp(trim($f), $functie) === 0)
					return $lid;

		return null;
	}

	public function set_members(DataIterCommissie $committee, array $members)
	{
		$this->db->delete('committee_members', sprintf('committee_id = %d', $committee->get_id()));

		foreach ($members as $member)
			$this->db->insert('committee_members', array(
				'committee_id' => $committee->get_id(),
				'member_id' => intval($member['member_id']),
				'functie' => $member['functie']));
	}

	public function get_for_member(DataIterMember $member)
	{
		$rows = $this->db->query("
			SELECT
				c.*,
				c_m.functie
			FROM
				committee_members c_m
			RIGHT JOIN commissies c ON
				c_m.committee_id = c.id
			WHERE
				c_m.member_id = " . $member->get_id() ."
				AND c.hidden <> 1
			GROUP by
				c.id,
				c_m.functie
			ORDER BY
				c.naam ASC");

		return $this->_rows_to_iters($rows);
	}

	public function get_email_addresses(DataIterCommissie $committee)
	{
		$aliasses = $this->db->query_column(
			"SELECT email FROM committee_email WHERE committee_id = :committee_id",
			'email',
			[':committee_id' => $committee->get_id()]);

		return array_merge([$committee['email']], $aliasses);
	}

	/**
	 * Get the login name of a specific commissie
	 * @id the commissie id
	 *
	 * @result the login name
	 */
	public function get_login($id) {
		return $this->db->query_value('SELECT login 
				FROM commissies 
				WHERE id = ' . intval($id));
	}

	public function get_from_email($email)
	{
		if (substr($email, -11) == '@svcover.nl')
			$email = substr($email, 0, -11);

		$row = $this->db->query_first("SELECT * FROM commissies WHERE login = '" . $this->db->escape_string(strtolower($email)) . "'");

		return $this->_row_to_iter($row);
	}

	/**
	 * Get the email address of a commissie (composed of the
	 * login name (see #DataModelCommissie::get_login))
	 * @id the commissie id
	 *
	 * @result the commissie email address
	 */
	public function get_email($id)
	{
		return $this->get_iter($id)->get('email');
	}
	
	/**
	 * Get commissie name 
	 * @id the commissie id 
	 *
	 * @result the commissie name
	 */
	public function get_naam($id)
	{
		$value = $this->db->query_value('SELECT naam 
				FROM commissies 
				WHERE id = ' . intval($id));
		
		if (!$value)
			return '';
		else
			return $value;
	}
	
	/**
	 * Get commissie page id
	 * @id the commissie id
	 *
	 * @result the commissie page id
	 */
	public function get_page($id)
	{
		return $this->db->query_value('SELECT page 
				FROM commissies 
				WHERE id = ' . intval($id));
	}
	
	/**
	 * Gets a commissie from name
	 * @name the commissie name
	 *
	 * @result a #DataIter or null if not found
	 */
	public function get_from_name($name)
	{
		$row = $this->db->query_first("SELECT * 
				FROM commissies
				WHERE '" . $this->db->escape_string($name) . "' IN (naam, login)");

		if ($row === null)
			throw new DataIterNotFoundException($name, $this);
		
		return $this->_row_to_iter($row);
	}

	public function search($query, $limit = null)
	{
		$privacy_fields = get_model('DataModelMember')->get_privacy();
		$privacy_bit = $privacy_fields['naam'];
		$current_privacy_setting = get_auth()->logged_in()
			? DataModelMember::VISIBLE_TO_MEMBERS
			: DataModelMember::VISIBLE_TO_EVERYONE;

		$query = sprintf("
			SELECT
				c.*,
				1 as search_relevance,
				'committee_name_match' as search_match_reason,
				NULL as search_match_committee_member_id
			FROM
				commissies c
			WHERE
				c.naam ILIKE '%%%s%%'
			UNION
			SELECT
				c.*,
				-1 as search_relevance,
				'committee_member_match' as search_match_reason,
				l.id as search_match_committee_member_id
			FROM
				committee_members c_m
			INNER JOIN leden l ON
				l.id = c_m.member_id
			INNER JOIN commissies c ON
				c.id = c_m.committee_id
			WHERE
				c.hidden <> 1
				AND (((l.privacy >> ($privacy_bit * 3)) & 7) & $current_privacy_setting) <> 0
				AND (CASE
					WHEN coalesce(tussenvoegsel, '') = '' THEN
						voornaam || ' ' || achternaam
					ELSE
						voornaam || ' ' || tussenvoegsel || ' ' || achternaam
				END ILIKE '%%%1\$s%%')",
			$this->db->escape_string($query));

		return $this->_rows_to_iters($this->db->query($query));
	}

	public function get_random($type = null, $include_board = false)
	{
		$conditions = "c.hidden <> 1";

		if ($type !== null)
			$conditions .= sprintf(" AND c.type = %d", $type);

		if ($include_board)
			$conditions = sprintf("(%s) OR c.id = %d", $conditions, COMMISSIE_BESTUUR);

		$row = $this->db->query_first("SELECT c.* 
				FROM commissies c
				LEFT JOIN committee_members c_m ON
					c_m.committee_id = c.id
				WHERE $conditions
				GROUP BY c.id
				HAVING COUNT(c_m.id) > 0 -- non-empty committees only
				ORDER BY RANDOM()
				LIMIT 1");
				
		return $this->_row_to_iter($row);
	}

	public function get_from_page($page_id)
	{
		$row = $this->db->query_first(sprintf("SELECT * 
				FROM commissies
				WHERE page_id = %d", $page_id));
		
		return $this->_row_to_iter($row);
	}

	public function get_committee_choices($show_own = true)
	{
		$is_admin = (
			\get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| \get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| \get_identity()->member_in_committee(COMMISSIE_EASY)
		);

		$iters = $this->get(null, true);

		$options = [
			'member' => [],
			'committees' => [],
			'working_groups' => [],
			'other' => [],
			'archived' => [],
		];

		foreach ($iters as $iter) {
			if ($show_own && $is_admin && \get_identity()->member_in_committee($iter->get_id()))
				$options['member'][$iter->get('naam')] = $iter->get_id();
			if ($iter['hidden'])
				$options['archived'][$iter->get('naam')] = $iter->get_id();
			elseif ($iter['type'] === \DataModelCommissie::TYPE_COMMITTEE)
				$options['committees'][$iter->get('naam')] = $iter->get_id();
			elseif ($iter['type'] === \DataModelCommissie::TYPE_WORKING_GROUP)
				$options['working_groups'][$iter->get('naam')] = $iter->get_id();
			else
				$options['other'][$iter->get('naam')] = $iter->get_id();
		}

		return [
			__('Your committees') => $show_own && $is_admin ? $options['member'] : [],
			__('Committees') => $options['committees'],
			__('Working groups') => $options['working_groups'],
			__('Others') => $options['other'],
			__('Archived') => $options['archived'],
		];
	}
}
