# KleioStore

Kleio Store is a system for archiving web content.  Kleio Store, when passed a URL, attemps to store
a sensible representation of that URL for later viewing.

KleioStore is NOT a crawler - it only stores a representation of the URL that it's given, and does
not follow links.  

KleioStore DOES try to make smart decisions about how to store a URL.  For instance, HTML pages are
stored as PDF and PNG images (as well as raw HTML), YouTube videos are extracted from the page and
archived as mp4.


## Dependencies

KleioStore is written PHP, and requires the curl module.

The HTML to PDF/PNG converter requires *phantomjs* to be installed (http://www.phantomjs.org/)
The YouTube (And other video site) archiver requires *youtube-dl* to be installed (rg3.github.io/youtube-dl/)


## Setup

Kleio stores objects in Amazon S3 and metadata in MySQL.  The first time that Kleio is run, it creates a config
file called config.php and then exits.  

Create a new S3 bucket and a blank MySQL database and then fill in S3/MySQL credentials in config.php


## Usage

At the moment, only the command line interface is implemented (an HTTP API is planned).

```
# Store a copy of a YouTube video
php kleio.php store http://www.google.com/

php kleio.php retrieve http://


## Development

New retrieval modules just need to implement the RetHandler interface - Have a look at the existing
modules to see what they need to do.  New modules are loaded and registered in setup.php

It should also be straightforward to port away from MySQL/S3 - See the interfaces implemented by the
existing storage/metadata classes and alter setup.php appropriately.

