<?php

/**
 * An asynchornous analogue of Kleio.  Adds queuing support so that items do not have to be
 * retrieved immediately.
 */

namespace kleiostore;

class KleioAsync extends Kleio
{
    /**
     * Enqueue a URL for processing
     * 
     * This is sort of like the asynchoronous version of store.  A stub representation with type x-kleio/queue
     * is created that can be picked up later
     */
    public function enqueue($url)
    {
        if($this->isQueued($url))
            return;
        
        $r = new Representation($url, 'x-kleio/queue', "Queue Record", time());
        $this->getMetadata()->store($r);
    }
    
    /**
     * Check if the given URL is already queued (or in progress)
     */
    public function isQueued($url)
    {
        try
        {
            $ob = $this->get($url, false);
        }
        catch(NoStoredRepresentationException $e)
        {
            Klog::log("$url is not queued (no representations)");
            return false;
        }
        
        $reps = $ob->getReps();
        
        foreach($reps as $r)
        {
            Klog::log("Found rep with type {$r->getType()}");
            
            if($r->getType() === 'x-kleio/queue' || $r->getType() === 'x-kleio/queue-in-progress')
            {
                Klog::log("$url is already queued");
                return true;
            }
        }
        
        Klog::log("$url is not queued");
        return false;
    }
    
    /**
     * Get the current queue (returns an array of x-kleio/queue Representations)
     */
    public function getQueue()
    {
        return $this->getMetadata()->getRepresentationsByType('x-kleio/queue');
    }
    
    /**
     * Check if there are queued URLs
     */
    public function hasQueue()
    {
        return \count($this->getQueue()) > 0;
    }
    
    /**
     * Dequeue the next queued URL and store it
     */
    public function dequeue()
    {
        $queue = $this->getQueue();
        
        $next = $queue[0];
        
        // Update the representation
        $next->setType('x-kleio/queue-in-progress');
        $this->getMetadata()->store($next);
        
        // Do the storage
        $this->store($next->getURL());
        
        // Update the representation
        $next->setType('x-kleio/queue-complete');
        $this->getMetadata()->store($next);
    }
    
    /**
     * Filter queue representations from the list of stored reps!
     */
    public function get($url, $filter=true)
    {
        $res = parent::get($url);
        
        if(!$filter)
        {
            return $res;
        }
        
        $ob = new StoredObject($url);
        
        foreach($res->getReps() as $r)
        {
            if(!preg_match('@^x-kleio/@i', $r->getType()))
            {
                $ob->addRep($r);
            }
        }
        
        return $ob;
    }
}
