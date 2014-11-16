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
    
    public function get($id)
    {
        $ob = $this->s3->getObject($this->bucket, $id);
        
        if($ob->error !== false)
        {
            throw new S3RetrievalException("Could not retrieve $id from S3");
        }
        
        return new BlobS3($id, $ob->body);
    }

    public function store($id, Blob $data)
    {
        $res = $this->s3->putObject($data->getBinaryData(), $this->bucket, $id);

        if(!$res)
        {
            throw new S3StorageException("Could not store blob as $id in S3");
        }
        
        Klog::log("Stored blob $id in S3[{$this->bucket}]");
        
        return new BlobS3($id, $data->getBinaryData());
    }

}

class BlobS3 implements PersistentBlob
{
    public function __construct($id, $data)
    {
        $this->data = $data;
        $this->id = $id;
    }
    
    public function getBase64Data()
    {
        return \base64_encode($this->getBinaryData());
    }

    public function getBinaryData()
    {
        return $this->data;
    }

    public function getID()
    {
        return $this->id;
    }
}

class S3RetrievalException extends \Exception {}
class S3StorageException extends \Exception {}
