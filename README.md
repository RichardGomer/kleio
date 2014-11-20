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

###Command Line

```
# Store a copy of a YouTube video
php kleio.php store https://www.youtube.com/watch?v=1NjTWvl8x-U

# OR: Queue it up for later...
php kleio.php enqueue https://www.youtube.com/watch?v=1NjTWvl8x-U

# ...and then later archive everything in the queue
php kleio.php dequeue

# Get a list of all the stored representations of the video
php kleio.php get https://www.youtube.com/watch?v=1NjTWvl8x-U

# Get one of the representations (by ID number) into out.mp4
php kleio.php getblob 12 > out.mp4
```

###Over HTTP

Queue http://www.bbc.co.uk/ for archival: `http://server/kleio/index.php?store&url=http://www.bbc.co.uk/`

Retrieve a list of representations of bbc.co.uk: `http://server/kleio/index.php?get&url=http://www.bbc.co.uk` (includes links to the blobs themselves)

Check queue statistics: `http://server/kleio/index.php?queue`

NB: You need to run the the dequeue operation from a CRON job or similar to process items added to the queue by the API.  
Synchronous archival is not supported via the API because it typically takes too long.


## Development

New retrieval modules just need to implement the RetHandler interface - Have a look at the existing
modules to see what they need to do.  New modules are loaded and registered in setup.php

It should also be straightforward to port away from MySQL/S3 - See the interfaces implemented by the
existing storage/metadata classes and alter setup.php appropriately.

