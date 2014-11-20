<?php

/**
 * KLEIOStore Web API
 */

namespace kleiostore;

require(dirname(__FILE__).'/../quapi/api.lib.php');
require 'setup.php';

use QuickAPI as API;

$api = new API\API(array_merge($_GET, $_POST), 'op');

abstract class KleioAPIHandler implements API\APIHandler
{
    protected $kleio;
    public function __construct(KleioAsync $kleio)
    {
        $this->kleio = $kleio;
    }
}

/**
 * Start a new process using the command line client and return an ID that can be
 * used to check the progress
 */
class QueueHandler extends KleioAPIHandler
{
    public function handleCall($args)
    {
        $url = $args['url'];
        
        $this->kleio->enqueue($url);
        
        return true;
    }
}

$api->addOperation(false, array('store', 'url'), new QueueHandler($kleio));

/**
 * Check on URL status
 * Returns an array containing:
 *      queued (bool) whether the URL is queued for archival
 *      stored (bool) whether the URL has actual (non-queue) representations
 */
class StatusHandler extends KleioAPIHandler
{
    public function handleCall($args)
    {
        $url = $args['url'];
        
        $res = array('url'=>$url);
        
        if($this->kleio->isQueued($url))
        {
            $res['queued'] = true;
        }
        
        try
        {
            $ob = $this->kleio->get($url); // KleioAsync flters out control representations for us :)
            
            $res['reps'] = $ob->getReps();
        }
        catch (kleiostore\NoStoredRepresentationException $e)
        {
            $res[]['stored'] = false;
        }
        
        return $res;
    }
}

$api->addOperation(false, array('get', 'url'), new StatusHandler($kleio));


class BlobHandler extends KleioAPIHandler
{
    public function handleCall($args)
    {
        $id = $args['blob'];
        
        try
        {
            $r = $this->kleio->getRepresentation($id);
            $blob = $r->getBlob($this->kleio);
            header("Content-Type: ".$r->getType());
            echo $blob->getBinaryData();
            exit;
        }
        catch (Exception $e)
        {
            header("Content-Type: text/plain");
            echo "ERROR: ".$e->getMessage();
        }
    }
}

$api->addOperation(false, array('blob'), new BlobHandler($kleio));

class RepConverter implements API\APIResultHandler
{
    public function prepareResult($rep)
    {
        $out = array(
            'type' => $rep->getType(),
            'time' => $rep->getTime(),
            'url' => $rep->getURL(),
        );
        
        if($rep->getBlob() instanceof PersistentBlob)
        {
            $out['blob'] = (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?blob='.$rep->getID();
        }
        
        return $out;
    }
}

/**
 * Handler to return queue status information
 */
class QueueStatusHandler extends KleioAPIHandler
{
    public function handleCall($args)
    {
        $oldest = time();
        
        $q = $this->kleio->getQueue();
        
        foreach($q as $r)
        {
            if($r->getTime() < $oldest)
                $oldest = $r->getTime();
        }
        
        $out = array(
            'length' => count($q),
            'oldest' => $oldest,
            'items' => $q
        );
        
        return $out;
    }
}

$api->addOperation(false, array('queue'), new QueueStatusHandler($kleio));


$api->registerResultHandler('kleiostore\Representation',  new RepConverter());

$api->handle();

?>