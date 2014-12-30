<?php

/**
 * KLEIOStore Web API
 */

namespace kleiostore;

require(dirname(__FILE__).'/../quapi/api.lib.php');
require 'setup.php';

use QuickAPI as API;

$api = new API\API(array_merge($_GET, $_POST), 'op');

/**
 * The security model allows anyone to query for a particular URL, but requires
 * a valid secret for any other operation (queue status, enqueing).  See
 * the $apisecrets setting in config.php to set up secrets.  The user argument is
 * ignored (but must be set to something!) and the pass must be a valid secret
 */
class KleioAPIAuth implements API\APIAuth
{
    public function __construct($secrets)
    {
        $this->secrets = $secrets;
    }
    
    public function checkCredentials($un, $pass, API\APIHandler $handler)
    {
        if($handler instanceof StatusHandler || $handler instanceof BlobHandler)
        {
            return true;
        }
        else
        {
            $hash = hash('sha256', $pass);
            foreach($this->secrets as $s)
            {
                if($s === $hash)
                    return true;
            }
            
            return false;
        }
    }
}

if($apisecrets !== false)
    $api->addAuth(new KleioAPIAuth($apisecrets));


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
        else
        {
            $res['queued'] = false;
        }
        
        try
        {
            $ob = $this->kleio->get($url); // KleioAsync flters out control representations for us :)
            
            $reps = $ob->getReps();
            
            if(count($reps) > 0)
            {
                $res['stored'] = true;
                $res['reps'] = $reps;
            }
            else
            {
                $res['stored'] = false;
            }
        }
        catch (NoStoredRepresentationException $e)
        {
            $res['stored'] = false;
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
    public function __construct(Kleio $kleio)
    {
        $this->kleio = $kleio;
    }
    
    public function prepareResult($rep)
    {
        $out = array(
            'type' => $rep->getType(),
            'time' => $rep->getTime(),
            'url' => $rep->getURL(),
        );
        
        if($rep->getBlob($this->kleio) instanceof PersistentBlob)
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


$api->registerResultHandler('kleiostore\Representation',  new RepConverter($kleio));

$api->handle();

?>