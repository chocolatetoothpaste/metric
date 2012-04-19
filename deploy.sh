#!/bin/bash

HT="#RewriteEngine on
#RewriteCond %{REQUEST_URI} !/maintenance.html$
#RewriteCond %{REMOTE_HOST} !^67\.177\.16\.192
#RewriteCond %{REMOTE_HOST} !^205\.186\.160\.50

#RewriteRule $ /maintenance.html [R=302,L]

<IfModule mod_deflate.c>
        # Insert filter
        SetOutputFilter DEFLATE

        # Netscape 4.x has some problems...
        BrowserMatch ^Mozilla/4 gzip-only-text/html

        # Netscape 4.06-4.08 have some more problems
        BrowserMatch ^Mozilla/4\.0[678] no-gzip

        # MSIE masquerades as Netscape, but it is fine
        # BrowserMatch \bMSIE !no-gzip !gzip-only-text/html

        # NOTE: Due to a bug in mod_setenvif up to Apache 2.0.48
        # the above regex won't work. You can use the following
        # workaround to get the desired effect:
        BrowserMatch \bMSI[E] !no-gzip !gzip-only-text/html

        # Don't compress images
        SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary

        <IfModule mod_headers.c>
                # Make sure proxies don't deliver the wrong content
                Header append Vary User-Agent env=!dont-vary
        </IfModule>
</IfModule>

ErrorDocument 404 /404

<IfModule mod_rewrite.c>

        RewriteEngine On
        # apply rule to the site root
        RewriteBase /
         # if request is not a file...
        RewriteCond %{REQUEST_FILENAME} !-f
         # ...and if is not a directory...
        RewriteCond %{REQUEST_FILENAME} !-d
         # ...route request to index.php
        RewriteRule . /index.php [L]

        RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
</IfModule>"

cd ..
mkdir -p page/template
mkdir page/controller
mkdir page/view

mkdir public
echo "<?php include( '../lib/bootstrap.php' ); ?>" > public/index.php

