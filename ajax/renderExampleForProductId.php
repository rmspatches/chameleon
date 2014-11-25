<?php
include('../config/pathconfig.inc.php');
require_once('../Bootstrap.php');

if(!defined('__ROOT__'))
{
    define('__ROOT__', '../');
}

require_once(__ROOT__ . 'libraries/functions.inc.php');

$connector = new APIConnector();

$auditUserId    = getRequestVar('auditUserId');;
$companyId      = getRequestVar('companyId');
$advertiserId   = getRequestVar('advertiserId');
$templateId     = getRequestVar('templateId');
$productId      = getRequestVar('productId');

$connector->setCompanyId($companyId);
$connector->setAdvertiserId($advertiserId);
$connector->setAuditUserId($auditUserId);

$targetPath = (string) $companyId . '/' . (string) $advertiserId . '/preview/' . $templateId;
$dir = '../output/' . $targetPath;

if(!file_exists($dir))
{
    // set the current umask to 0777
    $old = umask(0);
    if(!mkdir($dir, 0777, true))
    {
        throw new Exception('Could not create directory ' . $dir);
    }
    // reset umask
    umask($old);
}

$product = $connector->getProductDataByProductId($productId);

$categoryId = $product->getCategoryId();

$argv = array(null, $companyId, $advertiserId, null, $auditUserId);
$generator = new CMEOGenerator($argv);
$generator->setTemplates(array($templateId));
$generator->setCategories($categoryId);
$generator->prepareLogfile($categoryId);
$generator->getContainer()->setCategoryId($categoryId);

$sourcePath = (string) $companyId . '/' . (string) $advertiserId . '/' . $categoryId;

$template = $connector->getTemplateById($templateId);

$generator->getContainer()->setSource($template->getSvgContent());
$generator->getContainer()->setId($template->getBannerTemplateId());

try
{
    $generator->getContainer()->parse();
}
catch(Exception $e)
{
    $message = $generator->logMessage('An error occured: ' . $e->getMessage() . "\n");
}

$generator->render($product, 'GIF');

// move file ...
$sourceName = '../output/' . $sourcePath . '/' . $generator->getContainer()->getOutputFilename() . '.gif';
$targetName = '../output/' . $targetPath . '/' . $generator->getContainer()->getOutputFilename() . '.gif';
$fileName = 'output/' . $targetPath . '/' . $generator->getContainer()->getOutputFilename() . '.gif';

rename($sourceName, $targetName);

echo json_encode($fileName);

