<?php

/**
 * Download slides from SlideShare and convert to PDF
 */

namespace kleiostore;

class slidesharedl implements RetModule
{
    public function retrieve($url)
    {
        $name = \sys_get_temp_dir().'/kleiossdl_'.\hash('sha256', $url).'.pdf';

        // Remove query string from URL, it confuses the downloader!
        list($surl) = explode('?', $url);
        
        $wd = getcwd();
        chdir(dirname(__FILE__));
        $cmd = './slidesharedl '.\escapeshellarg($surl).' -o '.$name;
        KLog::log($cmd);
        exec($cmd);
        chdir($wd);
        
        $rep = new Representation($url, 'application/pdf', 'Slides', \time());
        $rep->setBlob(new Blob_Disk($name));
        
        return array($rep);
    }
}
