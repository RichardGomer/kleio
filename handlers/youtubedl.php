<?php

/**
 * YouTubeDL wrapper
 * 
 * Allows download of YouTube videos, plus some others
 * 
 * The list of supported domains changes, but the static helper function will register
 * a handler with an instance of Kleio for known working sites :)
 */

namespace kleiostore;

class youtubedl implements RetModule
{
    public function retrieve($url)
    {
        if(!\commandExists('youtube-dl'))
        {
            throw new YouTubeDLNotInstalledException("youtube-dl is not installed - see http://rg3.github.io/youtube-dl/download.html");
        }
        
        $name = \sys_get_temp_dir().'/kleioytdl_'.\hash('sha256', $url).'.mp4';

        $script = dirname(__FILE__).'/phantom_render.js';
        
        $cmd = 'youtube-dl '.\escapeshellarg($url).' -o '.$name.' -f mp4 --write-thumbnail';
        KLog::log($cmd);
        exec($cmd);
        
        $rep = new Representation($url, 'video/mp4', 'Video', \time());
        $rep->setBlob(new Blob_Disk($name));
        
        return array($rep);
    }

    public static function registerHandler(youtubedl $handler, Kleio $kleio)
    {
        $sites = array('youtube.com', 'vimeo.com');
        
        foreach($sites as $s)
        {
            $kleio->addHandlerByPattern('@https?://(www.)?'.str_replace('.', '\.', $s).'/@i', $handler);
        }
        
        
    }
}

class YouTubeDLNotInstalledException extends \Exception {}