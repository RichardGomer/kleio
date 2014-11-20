<?php

/**
 * The metadata subsystem stores information about resources and the blobs that have
 * been stored about each
 */

namespace kleiostore;

interface MetadataStore
{
    /**
     * See if the given URL is archived in the store
     */
    public function URLexists($url);
    
    /**
     * Get the metadata records for the given URL
     */
    public function getRepresentations($url);
    
    /**
     * Get metadata records by content type
     * 
     * Typically used to retrieve control representations like enqueued URLs
     */
    public function getRepresentationsByType($type);
    
    /**
     * Create a new record for the given URL
     */
    public function store(Representation $r);
}