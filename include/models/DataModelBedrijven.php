<?php
require_once 'data/DataModel.php';

/**
  * A class implementing bedrijven data
  */
class DataModelBedrijven extends DataModel
{
	public function __construct($db)
	{
		parent::DataModel($db, 'bedrijven');
	}
	
	protected function _generate_query($where)
	{
		return "SELECT " . $this->_generate_select() . " FROM {$this->table}" . ($where ? " WHERE {$where}" : "");
	}

	protected function _generate_select()
	{
		return "id, naam, slug, website, page, hidden, logo_mtime";
	}

	protected function _generate_slug(DataIter $bedrijf)
	{
		return trim(preg_replace('/[^a-z0-9]/', '-', strtolower($bedrijf->get('naam'))), '- ');
	}

	protected function _generate_page(DataIter $bedrijf)
	{
		$editable_model = get_model('DataModelEditable');

		$page = new DataIter($editable_model, -1, array(
			'owner' => COMMISSIE_PRCIE,
			'titel' => $bedrijf->get('naam')));
		
		return $editable_model->insert($page, true);
	}

	public function insert(DataIter $bedrijf, $get_id = false)
	{
		$bedrijf->data['slug'] = $this->_generate_slug($bedrijf);

		$bedrijf->data['page'] = $this->_generate_page($bedrijf);

		return parent::insert($bedrijf, $get_id);
	}

	public function get($show_hidden = false)
	{
		return $this->find($show_hidden ? '' : 'hidden = 0');
	}

	public function get_from_name($name)
	{
		return $this->find_one(sprintf("'%s' IN (naam, slug)", $this->db->escape_string($name)));
	}

	public function set_logo(DataIter $bedrijf, $fh)
	{
		$this->db->query(sprintf("UPDATE {$this->table} SET logo = '%s', logo_mtime = NOW() WHERE %s", 
			pg_escape_bytea(stream_get_contents($fh)),
			$this->_id_string($bedrijf->get_id())));
	}

	public function get_logo(DataIter $bedrijf, $if_before = 0)
	{
		$logo = $this->db->query_first(sprintf("
			SELECT
				logo
			FROM
				{$this->table}
			WHERE %s AND logo_mtime < to_timestamp(%d)",
				$this->_id_string($bedrijf->get_id()),
				$if_before));

		return $logo ? pg_unescape_bytea($logo['logo']) : null;
	}
}
