<?php

/**
 * Main Kleio logic
 */
namespace kleiostore;

class Kleio
{    
    private $storage, $metadata;
    
    private $handlersByType = array();
    private $handlersByPattern = array();
    
    public function __construct(Storage $storage, MetadataStore $metadata)
    {
        // Init storage module
        $this->storage = $storage;
        
        // Init metadata module
        $this->metadata = $metadata;
    }
    
    /**
     * Get the storage module
     */
    public function getStorage()
    {
        return $this->storage;
    }
    
    /**
     * Get the metadata module
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
    
    /**
     * Register a retrieval module for a particular content type
     * 
     * @param type $type
     * @param \kleiostore\RetModule $module
     */
    public function addHandlerByType($type, RetModule $module)
    {
        if(is_array($type))
        {
            foreach($type as $t)
            {
                $this->addHandlerByType($t, $module);
            }
            
            return;
        }
        
        $this->handlersByType[] = array('type'=>$type, 'mod'=>$module);
    }
    
    /**
     * Register a retrieval module for URLs matching a particular regex
     * 
     * @param type $type
     * @param \kleiostore\RetModule $module
     */
    public function addHandlerByPattern($pattern, RetModule $module)
    {
        $this->handlersByPattern[] = array('pattern'=>$pattern, 'mod'=>$module);
    }
    
    
    public function getHandlers($url)
    {
        $handlers = array();
        
        // 1: Try to find handlers by pattern
        foreach($this->handlersByPattern as $h)
        {
            if(preg_match($h['pattern'], $url))
            {
                KLog::log('Use '.get_class($h['mod']).' for pattern '.$h['pattern']);
                $handlers[] = $h['mod'];
            }
            else
            {
                //KLog::log('Pattern '.$h['pattern'].' does not match '.$url);
            }
        }
        
        // 2: Send a HEAD request to find the type and try finding handlers by type
        $cr = new CurlRequest($url);
        $ct = \strtolower($cr->getContentType());
        
        foreach($this->handlersByType as $h)
        {
            if(\strtolower($h['type']) == $ct)
            {
                KLog::log('Use '.get_class($h['mod']).' for type '.$ct);
                $handlers[] = $h['mod'];
            }
        }
        
        return $handlers;
    }
    
    /**
     * Store representations of the given URL
     * 
     * @param URL $url
     * @throws NoHandlerException
     */
    public function store($url)
    {
        Klog::clear();
        
        $handlers = $this->getHandlers($url);
        
        // Run each handler to return Representation objects
        $reps = array();
        foreach($handlers as $i=>$h)
        {
            try
            {
                $ret = $h->retrieve($url);

                // Handlers may return multiple representations
                if(!is_array($ret))
                    $reps[] = $ret;
                else
                    $reps = \array_merge($reps, $ret);
            }
            catch(\Exception $e)
            {
                KLog::log("Retrieval handler $i ".get_class($h)." failed: ".$e->getMessage());
            }
        }
        
        Klog::log("Retrieved ".count($reps)." representations");
        
        // Make the blob on each Representation persistent by putting it into the storage module
        // and replacing the temporary blob on the Representation
        foreach($reps as $i=>$r)
        {
            if(!$r instanceof Representation)
            {
                KLog::log("Not a valid representation object :(");
                continue;
            }
            
            Klog::log("Process representation $i ".$r->getType());

            try
            {
                // Store the blob
                $id = \uniqid(date('YmdHis_'), true);
                $blob = $this->getStorage()->store($id, $r->getBlob());

                // Put the new blob on to the representation
                $r->setBlob($blob);

                // Store the representation
                $this->getMetadata()->store($r);
            }
            catch(\Exception $e)
            {
                KLog::log("Failed to store representation $i ".$r->getType());
            }
        }
        
        try
        {
            KLog::log("Storing retrieval log");
            $logrep = new Representation($url, 'text/x-kleiolog', time());
            $logrep->setBlob(new Blob_Memory(implode("\n", KLog::get())));
            
            $id = \uniqid(date('YmdHis_'), true);
            $blob = $this->getStorage()->store($id, $logrep->getBlob());
            
            $logrep->setBlob($blob);
            
            $this->getMetadata()->store($logrep);
        }
        catch (Exception $e)
        {
            Klog::log("Failed to store retrieval log: ".$e->getMessage());
        }

        return $reps;
    }

    /**
     * Get the storage record for the given URL - throws an exception if URL is not stored
     * 
     * @param URL $url
     * @throws NoStoredRepresentationException
     */
    public function get($url)
    {
        // See if the metadata storage has any recorded representations of the URL
        if(!$this->getMetadata()->URLexists($url))
        {
            throw new NoStoredRepresentationException("Metadata store reported no stored representations for $url");
        }
        
        $reps = $this->getMetadata()->getRepresentations($url);
        
        if(count($reps) < 1)
        {
            throw new NoStoredRepresentationException("Metadata store reported no stored representations for $url (even though ::URLExists() returned true!)");
        }
        
        // If so, create a StoredObject object
        $ob = new StoredObject($url);
        
        // Add a Representation object for each representation
        // The actual retrieval of the blob is triggered on-deman by Representation
        foreach($reps as $r)
        {
            $ob->addRep($r);
        }
        
        return $ob;
    }
    
    public function getRepresentation($id)
    {
        return $this->getMetadata()->getRepresentation($id);
    }
}

class NoHandlerException extends \Exception {};
class NoStoredRepresentationException extends \Exception {};
