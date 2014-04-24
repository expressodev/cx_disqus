<?php

if ( ! defined('CX_DISQUS_NAME'))
{
	define('CX_DISQUS_NAME', 'CX Disqus Comments');
	define('CX_DISQUS_CLASS', 'Cx_disqus');
	define('CX_DISQUS_VERSION', '1.2.3');
	define('CX_DISQUS_DOCS', 'https://github.com/expressodev/cx_disqus');
}

$config['name'] = CX_DISQUS_NAME;
$config['version'] = CX_DISQUS_VERSION;
$config['nsm_addon_updater']['versions_xml'] = 'http://exp-resso.com/rss/cx-disqus-comments/versions.rss';
