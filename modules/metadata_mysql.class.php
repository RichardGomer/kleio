<?php

/**
 * MySQL implementation of metadata store
 */

namespace kleiostore;

class MetadataStore_Mysql implements MetadataStore
{
    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
        
        // Create the table if necessary
        $this->conn->query(
<<<END
        CREATE TABLE IF NOT EXISTS `kleiometa` (
              `RepresentationID` int(11) NOT NULL,
              `URLHash` varchar(64) NOT NULL,
              `Type` varchar(30) NOT NULL,
              `Time` int(11) NOT NULL,
              `BlobID` varchar(255) NOT NULL,
              `URL` text NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; 
                
        ALTER TABLE `kleiometa`
            ADD PRIMARY KEY (`RepresentationID`), ADD KEY `URLHash` (`URLHash`);
                
        ALTER TABLE `kleiometa`
            MODIFY `RepresentationID` int(11) NOT NULL AUTO_INCREMENT;
END
       );
    }
    
    public function URLexists($url)
    {
        return count($this->getRepresentations($url)) > 0;
    }
    
    private $urlcache = array();

    public function getRepresentations($url)
    {
        $hash = \hash('sha256', $url);
        
        if(\array_key_exists($hash, $this->urlcache))
        {
            return $this->urlcache[$hash];
        }
        
        $res = $this->conn->query("SELECT * FROM `kleiometa` WHERE `URLHash`=\"$hash\" ORDER BY Time DESC");
        
        $out = array();
        while(($row = $res->fetch(\PDO::FETCH_ASSOC)) !== false)
        {
            $out[] = $this->row2rep($row);
        }
        
        $this->urlcache[$hash] = $out;
        
        // Remove older cache entries
        while(\count($this->urlcache) > 5)
        {
            $keys = \array_keys($this->urlcache);
            unset($this->urlcache[$keys[0]]);
        }
        
        return $out;
    }
    
    /**
     * Convert a row from a database query into a Representation object
     * @param type $row
     * @return \kleiostore\Representation
     */
    private function row2rep($row)
    {
        $rep = new Representation($row['URL'], $row['Type'], $row['Time'], $row['RepresentationID']);
        $rep->setBlobID($row['BlobID']);
        
        return $rep;
    }
    
    public function getRepresentation($repID)
    {
        $repID = (int) $repID;
        $res = $this->conn->query("SELECT * FROM `kleiometa` WHERE `RepresentationID`=\"$repID\"");
        
        if($res->rowCount() < 1)
            throw new NoSuchRepresentationException("Representation ID $repID does not exist");
        
        return $this->row2rep($res->fetch());
    }

    public function store(Representation $r)
    {
        if($r->getID() !== false)
        {
            throw new CannotStoreRepresentationException("Cannot store a representation that has already been stored, ID MUST be unset");
        }
        
        $b = $r->getBlob();
        
        if(!$b instanceof PersistentBlob)
        {
            throw new CannotStoreRepresentationException("Cannot store representation unless blob is persistent");
        }
        
        $bid = $b->getID();
        
        $hash = \hash('sha256', $r->getURL());
        
        $q = $this->conn->prepare('INSERT INTO `kleiometa`(URLHash, Type, Time, BlobID, URL) VALUES(:hash, :type, :time, :blobid, :url)');
        
        $fields = array(
            ':hash' => $hash,
            ':type' => $r->getType(),
            ':time' => $r->getTime(),
            ':blobid' => $bid,
            ':url' => $r->getURL()
        );
        
        KLog::log("Store metadata ".implode(' ', $fields));
        
        $q->execute($fields);
        
        $rid = $this->conn->lastInsertID();
        $r->setID($rid);
        
        // Clear the URL from the cache if necessary, since it now has a new representation!
        if(\array_key_exists($hash, $this->urlcache))
        {
            unset($this->urlcache[$hash]);
        }
        
        return $r;
    }
}

class CannotStoreRepresentationException extends \Exception {}