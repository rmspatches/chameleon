<?php
/**
 * Created by IntelliJ IDEA.
 * User: thomas
 * Date: 29.07.14
 * Time: 07:21
 */

include('../config/pathconfig.inc.php');
require_once('../Bootstrap.php');


if(!defined('__ROOT__'))
{
    define('__ROOT__', '../');
}

require_once(__ROOT__ . 'libraries/functions.inc.php');

$container = new GfxContainer();
$svgHandler = new SvgFileHandler();

// for now ...
$auditUserId = 14;

$companyId    = getRequestVar('companyId');
$advertiserId = getRequestVar('advertiserId');
$templateId   = getRequestVar('templateId');

$container->setCompanyId($companyId);
$container->setAdvertiserId($advertiserId);

$basePath = (string) $companyId . '/' . (string) $advertiserId . '/';

if(!empty($_FILES))
{
    foreach($_FILES as $singleFile)
    {
        $filename = ASSET_DIR . $singleFile['name'];
        move_uploaded_file($singleFile['tmp_name'], $filename);
    }
}

// TODO: The template should be known and kept locally right now ...
// $template = $connector->getTemplateById($templateId);

//set file name
$baseFilename = 'rtest_' . $templateId;
$filename = $baseFilename . '.svg';
$container->setOutputName($baseFilename);
$svgHandler->setFilename($basePath . $filename);

//parse the svg
$container->setSource($basePath . $filename);
$container->parse();

//create a new svg with the given request parameters
if(null !== $_FILES && count($_FILES) > 0)
{
    //iterate all svg elements
    foreach($container->getElements() as $element)
    {
        foreach($_FILES as $key => $singleFile)
        {
            if($key === $element->getId())
            {
                $element->setImageUrl("assets/" . $singleFile['name']);
            }
        }
    }
}
else
{
    $container->changeElementValue($_POST);
}

$svgContent = $container->createSvg();

$container->setTarget('GIF');
$container->render();

// write the temporary file
// TODO: integrate this into Container!
$svgHandler->setSvgContent($svgContent);
$svgHandler->save();

if(array_key_exists('action', $_REQUEST) && 'save' === $_REQUEST['action'])
{
    $connector = new APIConnector();
    $connector->setCompanyId(getRequestVar('companyId'));
    $connector->setAdvertiserId(getRequestVar('advertiserId'));

    //update template in the data base
    $bannerTemplateModel = new BannerTemplateModel();
    $bannerTemplateModel->setSvgContent($svgContent);
    $bannerTemplateModel->setBannerTemplateId($_REQUEST['templateId']);
    $bannerTemplateModel->setAuditUserId($auditUserId);
    $bannerTemplateModel->setAdvertiserId($container->getAdvertiserId());
    $bannerTemplateModel->setDescription('testing');
    $bannerTemplateModel->setName('bumblebee testing');

    $response = $connector->sendBannerTemplate($bannerTemplateModel);
}

$response = array();

// $imgsrc = $container->getOutputDir() . '/' . $container->getOutputName() . '.gif';
// TODO: improve this path handling, too
$imgsrc = 'output/' . $basePath . '0/' . $container->getOutputName() . '.gif';
$response['imgsrc'] = $imgsrc;

echo json_encode($response);

