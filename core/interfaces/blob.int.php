<?php

/* 
 * Data inside KleioStore is stored as blobs.  A blob is largely abstract, it pulls together
 * a piece of data in the storage subsystem with some metadata from the metadata system
 * 
 * Blobs come either from Storage or from a retrieval module.  They could be implemented as
 * in-memory objects or attached to a file on disk.
 * 
 * A persistent blob has an ID that can be used to identify it.  Asking the storage module
 * for that ID should return the same blob.
 */

namespace kleiostore;

interface Blob
{
    public function getBinaryData();
    
    public function getBase64Data();
}

interface PersistentBlob extends Blob
{
    public function getID();
}