<?php

namespace kleiostore;

class StoredObject
{
    private $url;
    private $reps = array();
    
    public function __construct($url)
    {
        $this->url = $url;
    }
    
    /**
     * Get the URL of the archived resource
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Add a new representation
     */
    public function addRep(Representation $rep)
    {
        $this->reps[] = $rep;
    }
    
    /**
     * Get an array containing the Representations that are stored for this resource
     */
    public function getReps()
    {
        return $this->reps;
    }
    
}


