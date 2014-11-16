<?php

/**
 * Handler module that uses phantomjs to capture HTML pages as PDF and image
 */

namespace kleiostore;

class phantomjs implements RetModule
{
    public function retrieve($url)
    {
        $name = \sys_get_temp_dir().'/phantomjs_'.\hash('sha256', $url);
        
        $name_png = $name.'.png';
        $name_pdf = $name.'.pdf';
        
        if(!\commandExists('phantomjs'))
        {
            throw new PhantomJSNotInstalledException("phantomjs is not available");
        }
        
        $script = dirname(__FILE__).'/phantom_render.js';
        
        $cmd = 'phantomjs --ignore-ssl-errors=yes --ssl-protocol=any '.$script.' '.\escapeshellarg($url).' '.$name_pdf.' 1920px';
        KLog::log($cmd);
        KLog::log(exec($cmd));
        
        $cmd = 'phantomjs --ignore-ssl-errors=yes --ssl-protocol=any '.$script.' '.\escapeshellarg($url).' '.$name_png.' 1920px';
        KLog::log($cmd);
        KLog::log(exec($cmd));
        
        $rep_pdf = new Representation($url, 'application/pdf', \time());
        $rep_pdf->setBlob(new Blob_Disk($name_pdf));
        
        $rep_png = new Representation($url, 'image/png', \time());
        $rep_png->setBlob(new Blob_Disk($name_png));
        
        return array($rep_pdf, $rep_png);
    }
}

class PhantomJSNotInstalledException extends \Exception {}