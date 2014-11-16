<?php

/**
 * Utility class containing basic CURL methods
 */

namespace kleiostore;

class CurlRequest
{
    public function __construct($url)
    {
        $this->url = $url;
    }
    
    private $info = false;
    public function getInfo()
    {
        if($this->info !== false)
        {
            return $this->info;
        }
        
        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_URL, $this->url);
        \curl_setopt($curl, CURLOPT_FILETIME, true);
        \curl_setopt($curl, CURLOPT_NOBODY, true);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        \curl_exec($curl);
        $this->info = \curl_getinfo($curl);
        \curl_close($curl);
        
        return $this->info;
    }
    
    private $body = false;
    public function getBody()
    {
        if($this->body !== false)
        {
            return $this->body;
        }

        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_URL, $this->url);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($curl, CURLOPT_HEADER, false);
        \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $this->body = \curl_exec($curl);
        $this->info = \curl_getinfo($curl);
        \curl_close($curl);

        return $this->body;
    }
    
    public function getContentType()
    {
        $info = $this->getInfo();
        return $info['content_type'];
    }
}


?>