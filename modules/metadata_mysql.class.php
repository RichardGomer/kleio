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
              `Status` tinyint(2) NOT NULL,
              `URLHash` varchar(64) NOT NULL,
              `Type` varchar(30) NOT NULL,
              `Title` varchar(60) NOT NULL,
              `Time` int(11) NOT NULL,
              `BlobID` varchar(255) NULL,
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
    
    public function getRepresentationsByType($type)
    {
        $res = $this->conn->query("SELECT * FROM `kleiometa` WHERE `Type`=\"$type\" ORDER BY Time DESC");
        
        $out = array();
        while(($row = $res->fetch(\PDO::FETCH_ASSOC)) !== false)
        {
            $out[] = $this->row2rep($row);
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
        $rep = new Representation($row['URL'], $row['Type'], $row['Title'], $row['Time'], $row['Status'], $row['RepresentationID']);
        
        if($row['BlobID'] !== null)
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
        $b = $r->getBlob();

        if($b == null)
        {
            $bid = null;
        }
        else
        {
            if(!$b instanceof PersistentBlob)
            {
                throw new CannotStoreRepresentationException("Cannot store representation unless blob is persistent");
            }
            
            $bid = $b->getID();
        }
        
        $hash = \hash('sha256', $r->getURL());
        
        // New representations
        if($r->getID() === false)
        {
            $q = $this->conn->prepare('INSERT INTO `kleiometa`(URLHash, Status, Type, Title, Time, BlobID, URL) VALUES(:hash, :status, :type, :title, :time, :blobid, :url)');

            $fields = array(
                ':hash' => $hash,
                ':status' => $r->getStatus(),
                ':type' => $r->getType(),
                ':title' => $r->getTitle(),
                ':time' => $r->getTime(),
                ':blobid' => $bid,
                ':url' => $r->getURL()
            );

            KLog::log("Store new metadata ".implode(' ', $fields));

            $q->execute($fields);
            
            $rid = $this->conn->lastInsertID();
            $r->setID($rid);
        }
        // Updates
        else
        {
            $q = $this->conn->prepare('UPDATE `kleiometa` SET URLHash=:hash, Status=:status, Type=:type, Title=:title, Time=:time, BlobID=:blobid, URL=:url WHERE RepresentationID=:id');

            $fields = array(
                ':hash' => $hash,
                ':status' => $r->getStatus(),
                ':type' => $r->getType(),
                ':title' => $r->getTitle(),
                ':time' => $r->getTime(),
                ':blobid' => $bid,
                ':url' => $r->getURL(),
                ':id' => $r->getID()
            );

            KLog::log("Store updated metadata ".implode(' ', $fields));

            $q->execute($fields);
        }
        
        // Clear the URL from the cache if necessary, since it now has a new representation!
        if(\array_key_exists($hash, $this->urlcache))
        {
            unset($this->urlcache[$hash]);
        }
        
        return $r;
    }
}

class CannotStoreRepresentationException extends \Exception {}