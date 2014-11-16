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

require('modules/metadata_mysql.class.php');
require('modules/storage_s3.class.php');

require('handlers/raw.php');
require('handlers/wkhtmltopdf.php');
require('handlers/phantomjs.php');
require('handlers/youtubedl.php');

$store = new StorageS3($bucket, $access, $secret);
$meta = new MetadataStore_Mysql(new \PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass));

$kleio = new Kleio($store, $meta);

// Default handler
$kleio->addHandlerByPattern('/.*/', new RawHandler());

// HTML to PDF renderer
//$kleio->addHandlerByType(array('text/html', 'application/xhtml+xml'), new wkhtmltopdf());
$kleio->addHandlerByType(array('text/html', 'application/xhtml+xml'), new phantomjs());

// YouTube (and other video) handler provides a helper function to register supported URL patterns
youtubedl::registerHandler(new youtubedl(), $kleio);