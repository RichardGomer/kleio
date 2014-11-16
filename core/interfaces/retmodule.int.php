<?php

/**
 * KleioStore Retrieval Module Interface
 * 
 * Retrieval modules convert a type 
 */

namespace kleiostore;

interface RetModule
{
    public function retrieve($url);
}