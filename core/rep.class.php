<?php

namespace kleiostore;

/**
 * A representation is an instance of a stored URL - It has a URL, type, timestamp and blob
 * 
 * Blob can be set directly to a PersistentBlob object, or set to a blob ID, in which case
 * it will be loaded on demand from the main Kleio storage module
 */

class Representation
{
    private $url, $type, $time, $id;
    private $blob = null;
    private $blobid = false;
    
    const PENDING = 1;
    const FAILED = 2;
    const STORED = 4;
    
    public function __construct($url, $type, $title, $time, $status=self::PENDING, $id=false)
    {
        if(!preg_match('@.+/.*@', $type))
        {
            throw new InvalidTypeException("'$type' is not a valid mimetype");
        }
        
        $this->url = $url;
        $this->time = $time;
        $this->title = $title;
        $this->type = $type;
        $this->id = $id;
        $this->status = $status;
    }
    
    /**
     * The ID of the representation uniquely identifies the representation record itself.
     * This is DIFFERENT to the blob ID, which is maintained by the blob storage module.
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }
    
    public function setID($id)
    {
        if($this->getID() === false)
        {
            $this->id = $id;
        }
        else
        {
            throw new CannotSetRepresentationIDException("Cannot set the ID on a representation that already has one.");
        }
    }
    
    public function getURL()
    {
        return $this->url;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }
    
    public function getTime()
    {
        return $this->time;
    }
    
    public function setTime($time)
    {
        $this->time = $time;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setBlob(Blob $blob)
    {
        $this->blob = $blob;
        $this->blobid = false;
    }
    
    public function setBlobID($blobID)
    {
        $this->blobid = $blobID;
        $this->blob = null;
        
        // When a persistent blob is added, this representation is ready :)
        if($blobID !== null && $blobID !== false)
        {
            $this->status = self::STORED;
        }
    }
    
    public function getBlob(Kleio $store=null)
    {
        if($this->blob instanceof Blob)
        {
            return $this->blob;
        }
        elseif($this->blobid !== false)
        {
            if(!$store instanceof Kleio)
            {
                throw new CannotRetrieveBlobException("Blob ID is set but no instance of Kleio was passed to retrieve it from");
            }
            
            return $store->getStorage()->get($this->blobid);
        }
        else
        {
            return null;
        }
    }
}


class CannotSetRepresentationIDException extends \Exception {}
class InvalidTypeException extends \Exception {}
class CannotRetrieveBlobException extends \Exception {}
