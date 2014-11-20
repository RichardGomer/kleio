#!/bin/bash

if [ -z $1 ]
then
        echo "Usage: ./slidesharedl.sh URL"
        exit 1
fi

$n = `echo "$1" | sha1 -`
savepath="/tmp/slideshare/{$n}"

if [ ! -d "$savepath" ]
then
        mkdir $savepath
fi

name=`echo $1 | grep -Eoi '/[a-zA-Z0-9-]+$' | cut -d/ -f 2`

##### Create image url
listurl=`curl $1 | grep -Eoi 'data-full.*http://image.slidesharecdn.com/.*1024.jpg\?cb=[0-9]+' | cut -d\" -f 2`

cd $savepath

##### Loop Download List Picture
for i in $listurl
do
        `wget $i`
done

#### Convert all picture to pdf
convert `ls -v` $2 
rm $savepath -Rf

