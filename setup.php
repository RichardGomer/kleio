<?php

/**
 * This file sets up the KLEIO environment
 */

namespace kleiostore;

require('core/log.php');

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

$bucket = 'kleiotest';
$access = 'AKIAIYNQW4BI5HBK4OMA';
$secret = '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';

$store = new StorageS3($bucket, $access, $secret);
$meta = new MetadataStore_Mysql(new \PDO("mysql:host=10.0.0.1;dbname=kleiotest;charset=utf8", 'kleiotest', '5QrrGXYz2pTBGuB9'));

$kleio = new Kleio($store, $meta);

// Default handler
$kleio->addHandlerByPattern('/.*/', new RawHandler());

// HTML to PDF renderer
$kleio->addHandlerByType(array('text/html', 'application/xhtml+xml'), new wkhtmltopdf());

