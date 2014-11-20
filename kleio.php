#!/usr/bin/php
<?php

/**
 * KLEIO Store command line interface
 */

require('setup.php');

kleiostore\KLog::enablePrint();

$usage = "USAGE: php kleio.php <action> [url/id]\nActions: enqueue, dequeue*, store, get, blob\n* does not require url/ID";

$action = $argv[1];

if($argc < 2)
{
    echo $usage;
    exit;
}

if($action != 'dequeue')
{
    if($argc < 3)
    {
        echo $usage;
        exit;
    }
    
    $url = $argv[2];
}
else // Dequeue XD
{
    kleiostore\KLog::log("Begin dequeue");
    
    while($kleio->hasQueue())
    {
        $kleio->dequeue();
    }
    
    kleiostore\KLog::log("Queue is empty");
    exit;
}

switch($action)
{
    case 'enqueue':
        $kleio->enqueue($url);
        break;
    
    case 'store':
        $kleio->store($url);
        break;
    
    case 'get':
    case 'retrieve':
        try
        {
            $ret = $kleio->get($url);
            
            $reps = $ret->getReps();
            $n = count($reps);
            
            echo "Found $n representations:\n";
            
            $lasttime = 0;
            foreach($reps as $r)
            {
                $time = $r->getTime();
                if($lasttime != $time)
                {
                    $time = str_pad(date('Y-m-d H:i', $r->getTime()), 20);
                }
                else
                {
                    $time = str_pad("", 20);
                }
                
                $lasttime = $r->getTime();
                
                $type = str_pad($r->getType(), 25);
                $title = str_pad($r->getTitle(), 35);
                $id = $r->getID();
                
                echo "  $time $title $type $id\n";
            }
        }
        catch(\kleiostore\NoStoredRepresentationException $e)
        {
            echo "$url was not found in the store\n";
        }
        break;
        
    case 'blob':
    case 'getblob':
        try
        {
            $r = $kleio->getRepresentation($url);
            $blob = $r->getBlob($kleio);
            echo $blob->getBinaryData();
            
        } catch (Exception $e) {
            echo "ERROR: ".$e->getMessage();
        }
        break;
    
    default:
        echo $usage;
        break;
}

?>