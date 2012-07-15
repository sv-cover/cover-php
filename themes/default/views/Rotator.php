<?PHP

class Rotator
{
    private $dir;
    
    public function __construct($dir)
    {
        $this -> dir = $dir;
    }
    
    public function get($n)
    {
        $banners = array();
        $return = array();
        //check if path is directory and if dir can be opened
        if (is_dir($this -> dir) and $resource = opendir($this -> dir))
        {
            /* read json file */
            $data = file_get_contents($this -> dir. 'data.json');
            $banners = json_decode($data, true, 10);

        }
        
        if (count($banners) == 1)
            return $banners;
        else if ($n > count($banners))
            $n = count($banners);

        shuffle($banners);
        return array_slice($banners, 0, $n);
    }
    
    public function set($filename, $url)
    {
        $banners = array();
        
        $newdata = array('filename' => $filename, 'url' => $url);
        if (($data = @file_get_contents($this -> dir . 'data.json')) != false)
        {
            $banners = json_decode($data, true, 10);
        }
        array_push($banners, array('filename' => $filename, 'url' => $this -> dir . $url));
        
        file_put_contents($this -> dir . 'data.json', json_encode($banners), LOCK_EX);
    }
}