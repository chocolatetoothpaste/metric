#!/bin/bash

# I hate putting all this crap into variables, but I want to keep the root folder clean

# [public]/.htaccess
HTACCESS="
#RewriteEngine on\n
#RewriteCond %{REQUEST_URI} !/maintenance.html$\n
#RewriteCond %{REMOTE_HOST} !^0\.0\.0\.0\n
#RewriteRule $ /maintenance.html [R=302,L]\n\n

RewriteEngine on\n
RewriteCond %{REQUEST_URI} /maintenance.html\n
RewriteRule $ / [R=302,L]\n\n

<IfModule mod_deflate.c>\n
\t# Insert filter\n
\tSetOutputFilter DEFLATE\n\n

\t# Netscape 4.x has some problems...\n
\tBrowserMatch ^Mozilla/4 gzip-only-text/html\n\n

\t# Netscape 4.06-4.08 have some more problems\n
\tBrowserMatch ^Mozilla/4\.0[678] no-gzip\n\n

\t# MSIE masquerades as Netscape, but it is fine\n
\t# BrowserMatch \bMSIE !no-gzip !gzip-only-text/html\n\n

\t# NOTE: Due to a bug in mod_setenvif up to Apache 2.0.48\n
\t# the above regex won't work. You can use the following\n
\t# workaround to get the desired effect:\n
\tBrowserMatch \bMSI[E] !no-gzip !gzip-only-text/html\n\n

\t# Don't compress images\n
\tSetEnvIfNoCase Request_URI \\\\\\n
\t\t\.(?:gif|jpe?g|png)$ no-gzip dont-vary\n\n

\t<IfModule mod_headers.c>\n
\t\t# Make sure proxies don't deliver the wrong content\n
\t\tHeader append Vary User-Agent env=!dont-vary\n
\t</IfModule>\n
</IfModule>\n\n

ErrorDocument 404 /404\n\n

<IfModule mod_rewrite.c>\n
\tRewriteEngine On\n
\t# apply rule to the site root\n
\tRewriteBase /\n
\t # if request is not a file...\n
\tRewriteCond %{REQUEST_FILENAME} !-f\n
\t # ...and if is not a directory...\n
\tRewriteCond %{REQUEST_FILENAME} !-d\n
\t # ...route request to index.php\n
\tRewriteRule . /index.php [L]\n\n

\t# Make sure the HTTP_AUTHORIZATION header gets passed for API requests\n
\tRewriteRule .\052 - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]\n
</IfModule>\n\c";

# main page class
PAGE="<!doctype html>\n
<html>\n
\t<head>\n
\t\t<title><?=( \$config->DEV ? 'DEV: ' : '' ) . \$this->title?></title>\n
\t</head>\n
\t<body><?=\$this->body?></body>\n
</html>";

# main config file
CONFIG="<?php\n
\$config->define( 'DEV',					true );\n\n

\$config->define( 'DIE_BEFORE_REDIRECT',	false );\n\n

\$config->define( 'PATH_ROOT',		dirname( \$_SERVER['DOCUMENT_ROOT'] ) );\n
\$config->define( 'PATH_LIB',		\$config->PATH_ROOT . '/${PWD##*/}' );\n
\$config->define( 'PATH_PAGE',		\$config->PATH_ROOT . '/page');\n
\$config->define( 'PATH_CONTROLLER',	\$config->PATH_PAGE . '/controller' );\n
\$config->define( 'PATH_VIEW',		\$config->PATH_PAGE . '/view' );\n
\$config->define( 'PATH_FRAG',		\$config->PATH_PAGE . '/frag' );\n
\$config->define( 'PATH_CACHE',		\$config->PATH_PAGE . '/cache' );\n
\$config->define( 'PATH_TEMPLATE',	\$config->PATH_PAGE . '/template' );\n\n

\$config->classes = array(\n
\t'Template'	=>	\$config->PATH_TEMPLATE . '/template.class.php'\n
);\n\n

\$config->alias = array(\n
\t'/'	=>	\$config->PATH_CONTROLLER . '/index.php'\n
);\n\n

\$config->template = 'Template';\n
?>\c";

# main file "welcome" message
MAIN="<p>Help! I'm alive, my heart keeps beating like a hammer</p><p><em>&#968; Metric</em></p>\c"

ERR="<?php\n
header( 'HTTP/1.1 404 Not Found' );\n
\$this->title = '404 Not Found';\n
?>\n
\t<h1>Page Not Found</h1>\n
\t<p>The request <?php echo \$this->request; ?> was not found.</p>\n
\t<p><em>&#968; Metric</em></p>\n"

# go up one level and start deploying stuff
cd ..

echo 'Creating config...'
# trim stupid leading spaces
echo -e $CONFIG | sed 's/^ *//g' > config.inc.php

echo 'Creating directories...'
# create page directory hierarchy
mkdir -p page/template
mkdir page/controller
mkdir page/view
mkdir page/frag
mkdir page/cache

echo 'Creating initial scripts...'
# trim stupid leading spaces
echo -e $PAGE | sed 's/^ *//g' > page/template/template.php

# views won't load without a controller present, so create an empty one
touch page/controller/index.php
echo -e $MAIN > page/view/index.phtml
echo -e $ERR | sed 's/^ *//g'  > page/controller/404.php

# create public dir to point web server to and inject bootstrap into index file
mkdir public
echo "<?php include( '../lib/bootstrap.php' ); ?>" > public/index.php
echo -e $HTACCESS | sed 's/^ *//g' > public/.htaccess

echo -e '\n...done!'