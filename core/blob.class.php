<?php

/* 
 * Default blob implementations - For memory-backed or disk-backed blobs
 * 
 * Blobs come out of the collection modules and into/out of the storage modules
 */

namespace kleiostore;

abstract class Blob_Basic implements Blob
{
    public function getBase64Data()
    {
        return \base64_encode($this->getBinaryData());
    }
}

class Blob_Memory extends Blob_Basic
{
    private $data = null;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function getBinaryData()
    {
        return $this->data;
    }
    
    public function setBinaryData($data)
    {
        $this->data = $data;
    }
}

class Blob_Disk extends Blob_Basic
{
    public function __construct($path)
    {
        $this->path = $path;
        
        if(!\file_exists($path))
        {
            throw new BadPathException("$path does not exist");
        }
    }
    
    public function getBinaryData()
    {
        return \file_get_contents($this->path);
    }
}

class BadPathException extends \Exception {}