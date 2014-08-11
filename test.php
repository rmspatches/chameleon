<?php
require_once('Bootstrap.php');
include('config/pathconfig.inc.php');

if(!defined('__ROOT__'))
{
    define('__ROOT__', './');
}

$advertiserId = 122;
$companyId = 170;
$userId = 14;

$connector = new APIConnector();
$container = new GfxContainer();

$container->setAdvertiserId($advertiserId);
$container->setCompanyId($companyId);

$connector->setAdvertiserId($advertiserId);
$connector->setCompanyId($companyId);

// fetch all templates for given advertiser
$templates = $connector->getTemplates($advertiserId);

foreach($templates AS $template)
{
    // for now, we stick to the "old" process - reading the svg from a file - in order to prevent more merge
    // conflicts than necessary; changing the process will be very easy and done after thomas hummel's changes
    // have been merged
    $filename = 'rtest_' . $template->getBannerTemplateId() . '.svg';

    // write the temporary file
    $fh = fopen(SVG_DIR . $filename, 'w');
    fwrite($fh, $template->getSvgContent());
    fclose($fh);

    $container->setSource($filename);
    $container->setOutputName('output_' . $template->getBannerTemplateId());
    $container->parse();
    $container->setTarget('SWF');
    $container->render();
    $container->setTarget('GIF');
    $container->render();

    unlink(SVG_DIR . $filename);
}

echo 'Advertiser ' . $advertiserId . ' has ' . $connector->getNumTemplates($advertiserId) . ' templates.' . "\n";

exit(0);

for($i=1; $i<6;$i++)
{
    $myContainer = new GfxContainer();
    $myContainer->setCompanyId(4);
    $myContainer->setAdvertiserId(122);
    $myContainer->setId($i);
    $myContainer->setSource('ttest_' . $i . '.svg');
    // $myContainer->setOutputName('output_' . $i);
    $myContainer->parse();
    $myContainer->setTarget('SWF');
    $myContainer->render();
    $myContainer->setTarget('GIF');
    $myContainer->render();
}

