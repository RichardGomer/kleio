<?php

/**
 * Some shell helper functions
 */

function commandExists($cmd)
{
    $r = shell_exec("which $cmd");
    
    return empty($r) ? false : true;
}