<?PHP

class DataIterBanner extends DataIter
{
    static public function fields()
    {
        return [
            'filename',
            'url',
            'type'
        ];
    }
}

class DataModelBanner extends DataModel
{
    const TYPE_MAIN_SPONSOR = 'main-sponsor';

    public $dataiter = 'DataIterBanner';

    private $_dir;

    private $_banners = null;
    
    public function __construct()
    {
        $this->_dir = get_config_value('path_to_banners', 'images/banners/');
    }
    
    public function get($n = null)
    {
        if ($this->_banners === null)
        {
            $this->_banners = array();

            //check if path is directory and if dir can be opened
            if (is_dir($this->_dir) and $resource = opendir($this->_dir))
            {
                /* read json file */
                $data = file_get_contents($this->_dir. 'data.json');
                $banners = json_decode($data, true, 10);
                $this->_banners = $this->_rows_to_iters($banners);
            }
        
            shuffle($this->_banners);
        }

        if ($n !== null && $n < count($this->_banners))
            return array_slice($this->_banners, 0, $n);
        else
            return $this->_banners;
    }

    public function main_sponsors()
    {
        return array_filter($this->get(), function($banner) {
            return $banner['type'] == DataModelBanner::TYPE_MAIN_SPONSOR;
        });
    }

    public function partners()
    {
        return array_filter($this->get(), function($banner) {
            return $banner['type'] != DataModelBanner::TYPE_MAIN_SPONSOR;
        });   
    }
}