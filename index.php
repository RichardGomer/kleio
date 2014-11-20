<?php

/**
 * KLEIOStore Web API
 */

require(dirname(__FILE__).'/../quapi/api.lib.php');

use QuickAPI as API;

$api = new API\API(array_merge($_GET, $_POST), 'op');

abstract class KleioAPIHandler implements API\APIHandler
{
    protected $kleio;
    public function __construct(Kleio $kleio)
    {
        $this->kleio = $kleio;
    }
}

/**
 * Start a new process using the command line client and return an ID that can be
 * used to check the progress
 */
class StoreHandler extends KleioAPIHandler
{
    public function handleCall($args)
    {
        $url = $args['url'];
        
        $this->kleio->store();
    }
}


?>