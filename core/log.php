<?php

namespace kleiostore;

class KLog
{
    private static $log = array();
    
    public static function log($string)
    {
        $trace = debug_backtrace();
        
        $class = count($trace) > 2 ? $trace[1]['class'] : '(main)';
        
        $class = str_replace('kleiostore\\', '', $class);
        
        $line = '['.time().'] '.$class.': '.$string;
        self::$log[] = $line;
        
        if(self::$print)
        {
            echo $line."\n";
        }
    }
    
    private static $print = false;
    public static function enablePrint($e=true)
    {
        self::$print = (bool) $e;
    }
    
    public static function get()
    {
        return self::$log;
    }
    
    public static function clear()
    {
        self::$log = array();
    }
}

