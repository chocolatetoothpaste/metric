#!/bin/bash

PAGE="<?php\n
class Template extends \Metric\Page\Page {}\n
?>";

CONFIG="<?php\n\n
\$config->define( 'DIE_BEFORE_REDIRECT',	false );\n\n
\$config->define( 'PATH_ROOT',		dirname( \$_SERVER['DOCUMENT_ROOT'] ) );\n
\$config->define( 'PATH_LIB',		\$config->PATH_ROOT . '/${PWD##*/}' );\n
\$config->define( 'PATH_PAGE',		\$config->PATH_ROOT . '/page');\n
\$config->define( 'PATH_CONTROLLER',	\$config->PATH_PAGE . '/controller' );\n
\$config->define( 'PATH_VIEW',		\$config->PATH_PAGE . '/view' );\n
\$config->define( 'PATH_TEMPLATE',	\$config->PATH_PAGE . '/template' );\n\n
\$config->classes = array(\n
	'Template'	=>	\$config->PATH_TEMPLATE . '/template.class.php'\n
);\n\n

\$config->alias = array(\n
	'/'	=>	\$config->PATH_CONTROLLER . '/index.php'\n
);\n\n

\$config->template = 'Template';\n
?>";

cd ..

echo -e $CONFIG > config.inc.php

mkdir -p page/template
mkdir page/controller
mkdir page/view

echo -e $PAGE > page/template/template.class.php
touch page/controller/index.php
echo '<p>Help! I\'m alive, my heat is beating like hammer</p><p><em>&#968; Metric</em></p>' > page/view/index.phtml

mkdir public
echo "<?php include( '../lib/bootstrap.php' ); ?>" > public/index.php
cp lib/htaccess public/.htaccess
