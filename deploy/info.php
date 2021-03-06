<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'dns';
$app['version'] = '2.3.24';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('dns_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('dns_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['dns']['title'] = lang('dns_app_name');
$app['controllers']['domains']['title'] = lang('dns_active_directory_domains');
$app['controllers']['entries']['title'] = lang('dns_dns_entries');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network >= 1:1.4.11',
);

$app['core_requires'] = array(
    'app-network-core >= 1:1.4.13',
    'dnsmasq >= 2.48',
    'initscripts >= 9.03.31-3',
    'net-tools',
);

$app['core_file_manifest'] = array(
    'dnsmasq.php'=> array('target' => '/var/clearos/base/daemon/dnsmasq.php'),
);
