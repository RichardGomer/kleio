<?php

/**
 * The raw handler just downloads the URL as-is
 */

namespace kleiostore;

class RawHandler implements RetModule
{
    public function retrieve($url)
    {
        $r = new CurlRequest($url);
        
        $type = $r->getContentType();
        
        $rep = new Representation($url, $type, 'Raw Download', \time());
        
        $rep->setBlob(new Blob_Memory($r->getBody()));
        
        return $rep;
    }
}

