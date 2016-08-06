<?PHP

class DataIterBanner extends DataIter
{
    //
}

class DataModelBanner extends DataModel
{
    private $dir;
    
    public function __construct()
    {
        $this->dir = get_config_value('path_to_banners', 'images/banners/');
    }
    
    public function get($n = null)
    {
        $banners = array();
        $return = array();

        //check if path is directory and if dir can be opened
        if (is_dir($this->dir) and $resource = opendir($this->dir))
        {
            /* read json file */
            $data = file_get_contents($this->dir. 'data.json');
            $banners = json_decode($data, true, 10);
        }
        
        shuffle($banners);

        if ($n !== null && $n < count($banners))
            $banners = array_slice($banners, 0, $n);

        return $this->_rows_to_iters($banners, 'DataIterBanner');
    }
}