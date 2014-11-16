<?php

namespace kleiostore;

class KLog
{
    public static function log($string)
    {
        $trace = debug_backtrace();
        $class = $trace[1]['class'];
        
        if(self::$print)
        {
            echo $class.': '.$string."\n";
        }
    }
    
    private static $print = false;
    public static function enablePrint($e=true)
    {
        self::$print = (bool) $e;
    }
}

