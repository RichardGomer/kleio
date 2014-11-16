<?php

/* 
 * KleioStore needs a storage subsystem, somewhere to actually put blobs
 */

namespace kleiostore;

interface Storage
{
    public function store($id, Blob $data);
    
    public function get($id);
}
