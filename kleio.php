#!/usr/bin/php
<?php

/**
 * KLEIO Store command line interface
 */

require('setup.php');

kleiostore\KLog::enablePrint();

$usage = "USAGE: php kleio.php <action> <url/id>\nActions: store, retrieve, getblob\n";

if($argc < 3)
{
    echo $usage;
    exit;
}

$action = $argv[1];
$url = $argv[2];

switch($action)
{
    case 'store':
        $kleio->store($url);
        break;
    
    case 'retrieve':
        try
        {
            $ret = $kleio->get($url);
            
            $reps = $ret->getReps();
            $n = count($reps);
            
            echo "Found $n representations:\n";
            
            foreach($reps as $r)
            {
                $type = $r->getType();
                $time = date('Y-m-d H:i', $r->getTime());
                $id = $r->getID();
                
                echo "  $time \t$type \t$id\n";
            }
        }
        catch(\kleiostore\NoStoredRepresentationException $e)
        {
            echo "$url was not found in the store\n";
        }
        break;
        
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