<?php
namespace gzh;
use MemCache;
class MemcacheClass {
    public function getStatus()
    {
        return $this->objMemcache->getStats();
    }
    public function set($key,$val,$flag = false, $expire = 60)
    {
        return $this->objMemcache->set($this->keyPrefix.$key,$val,$flag,$expire);
    }
    public function get($key)
    {
        return $this->objMemcache->get($this->keyPrefix.$key);
    }
    public function __construct()
    {
        if (extension_loaded('memcache')) {
            if (!$this->objMemcache) {
                $this->objMemcache = new MemCache();
            }
        }
    }
    public function connect($conf)
    {
        $this->keyPrefix = $conf['key_prefix'];
        $this->objMemcache->connect($conf['host'],$conf['port']);
    }
    public $objMemcache;
    private $keyPrefix;
}