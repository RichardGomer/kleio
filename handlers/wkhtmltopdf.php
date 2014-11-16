<?php

/**
 * Handler module that uses the wkhtlptopdf utility to capture HTML pages as PDF or image
 */

namespace kleiostore;

class wkhtmltopdf implements RetModule
{
    public function retrieve($url)
    {
        $name = \sys_get_temp_dir().'/kleiowkhtmltopdf_'.\hash('sha256', $url);
        
        $cmd = 'wkhtmltopdf '.\escapeshellarg($url).' '.$name;
        KLog::log($cmd);
        shell_exec("( cmdpid=\$BASHPID; (sleep 15; kill \$cmdpid) & exec $cmd )");
        
        $rep = new Representation($url, 'application/pdf', \time());
        $rep->setBlob(new Blob_Disk($name));
        
        return $rep;
    }
}


?>