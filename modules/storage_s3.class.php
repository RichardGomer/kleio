<?php


namespace kleiostore;

require(dirname(__FILE__).'/s3/S3.php');

class StorageS3 implements Storage
{
    public function __construct($bucket, $accessKey, $secretKey)
    {
        $this->bucket = $bucket;
        
        $this->s3 = new \S3($accessKey, $secretKey);
        $this->s3->setExceptions(true);
    }
    
    /**
     * Returns an S3 blob that will retrieve data on-demand
     */
    public function get($id)
    {
        return new BlobS3($id, $this);
    }
    
    public function getData($id)
    {
        $ob = $this->s3->getObject($this->bucket, $id);
        
        if($ob->error !== false)
        {
            throw new S3RetrievalException("Could not retrieve $id from S3");
        }
        
        return $ob->body;
    }

    /**
     * Returns a pre-filled S3 blob
     */
    public function store($id, Blob $data)
    {
        $res = $this->s3->putObject($data->getBinaryData(), $this->bucket, $id);

        if(!$res)
        {
            throw new S3StorageException("Could not store blob as $id in S3");
        }
        
        Klog::log("Stored blob $id in S3[{$this->bucket}]");
        
        return new BlobS3($id, $this, $data->getBinaryData());
    }

}

/**
 * This blob only actually gets the data from S3 when it's requested, so just instantiating
 * instances is not very resource intensive
 */
class BlobS3 implements PersistentBlob
{
    public function __construct($id, StorageS3 $store, $data=false)
    {
        $this->data = $data;
        $this->store = $store;
        $this->id = $id;
    }
    
    public function getBase64Data()
    {
        return \base64_encode($this->getBinaryData());
    }

    public function getBinaryData()
    {
        if($this->data !== false)
        {
            return $this->data;
        }
        
        $this->data = $this->store->getData($this->id);
        
        return $this->data;
    }

    public function getID()
    {
        return $this->id;
    }
}

class S3RetrievalException extends \Exception {}
class S3StorageException extends \Exception {}
