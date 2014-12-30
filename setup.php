<?php

/**
 * This file sets up the KLEIO environment
 */

namespace kleiostore;

if(!file_exists('config.php'))
{
    file_put_contents('config.php', <<<END
<?php

// Amazon S3 credentials
\$bucket = 'kleio';
\$access = 'S3_ACCESS_KEY';
\$secret = 'S3_SECRET_KEY';

// MySQL credentials
\$dbhost = '127.0.0.1';
\$dbname = 'kleio';
\$dbuser = 'kleio';
\$dbpass = 'CHANGEME';
            
// API secrets
// A list of secrets that can be used to access the API
// The values here are the sha256 hashes of the actual secrets
\$apisecrets = array(
'3617113835cc4f0d9b19ca51196117706c5f4c24068c6663d67599d985c41ff6' // = xyQK24wJoKPlC0SWAc Change this!
);
            
END
            );
    
    echo "A new config.php file has been created - You should set your\ndatabase/S3 credentials and then run kleio again.\n\n";
    exit;
}



require 'config.php';

require('core/log.php');
require('core/shell.php');

require('core/interfaces/blob.int.php');
require('core/interfaces/metadata.int.php');
require('core/interfaces/retmodule.int.php');
require('core/interfaces/storage.int.php');

require('core/blob.class.php');
require('core/rep.class.php');
require('core/storedobject.class.php');
require('handlers/curlbasic.php'); // This is used by core
require('core/core.class.php');
require('core/async.class.php');

require('modules/metadata_mysql.class.php');
require('modules/storage_s3.class.php');

require('handlers/raw.php');
require('handlers/wkhtmltopdf.php');
require('handlers/phantomjs.php');
require('handlers/youtubedl.php');
require('handlers/slidesharedl.php');

$store = new StorageS3($bucket, $access, $secret);
$meta = new MetadataStore_Mysql(new \PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass));

$kleio = new KleioAsync($store, $meta);

// Default handler
$kleio->addHandlerByPattern('/.*/', new RawHandler());

// HTML to PDF renderer
//$kleio->addHandlerByType(array('text/html', 'application/xhtml+xml'), new wkhtmltopdf());
$kleio->addHandlerByType(array('text/html', 'application/xhtml+xml'), new phantomjs());

// YouTube (and other video) handler provides a helper function to register supported URL patterns
youtubedl::registerHandler(new youtubedl(), $kleio);

// Slideshare to PDF converter
$kleio->addHandlerByPattern('@https?://(www.)?slideshare\.net/.+/.+@i', new slidesharedl());


