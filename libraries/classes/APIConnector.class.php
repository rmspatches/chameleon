<?php

require_once(__ROOT__ . 'config/apiconfig.inc.php');

class APIConnector
{
    private $serviceCalls;

    private $auditUserId;
    private $advertiserId;
    private $companyId;

    public function __construct()
    {
        $this->serviceCalls = array();
        $this->serviceCalls['getTemplates']          = 'advertiser/{advertiserId}/bannerTemplates';
        $this->serviceCalls['getTemplatesByGroup']   = 'advertiser/{advertiserId}/group/{groupId}/bannerTemplates';
        $this->serviceCalls['postTemplate']          = 'bannerTemplate';
        $this->serviceCalls['deleteTemplate']        = 'bannerTemplate/{templateId}';
        $this->serviceCalls['getTemplateById']       = 'bannerTemplate/{templateId}';
        $this->serviceCalls['getProductsByCategory'] = 'company/{companyId}/category/{categoryId}/products';
        $this->serviceCalls['sendCreative']          = 'creativeImage';
        $this->serviceCalls['getEnums']              = 'enums';
        $this->serviceCalls['getCategories']         = 'company/{companyId}/categories';
    }

    /**
     * getMethodList
     *
     * @access public
     * @return methodList a list containing all currently available REST calls
     */
    public function getMethodList()
    {
        $methodList = array_keys($this->serviceCalls);
        return $methodList;
    }


    /**
     * get
     *
     * @param $path
     * @return string
     */
    public function get($path)
    {
        $restCall = $path;
        $response = file_get_contents($restCall);
        return $response;
    }


    /**
     * getUserStatusValues
     *
     * get all possible user status values via REST API
     * (crrently: ACTIVE, PAUSED, DELETED)
     *
     * @access public
     * @return $userStatusValues a list containing all defined user status values
     */
    public function getUserStatusValues()
    {
        $enums = $this->getEnums();
        $userStatusValues = $enums->userStatusValues;
        return $userStatusValues;
    }


    /**
     * getEnums
     *
     * method to retrieve ALL enums from REST API. While currently there are only the userStatusValues,
     * there will most likely more than that later
     *
     * @access private
     * @return $enums list of ALL enums returned by REST API call
     */
    private function getEnums()
    {
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['getEnums'];
        $curl = $this->getCurl($resource, 'GET');
        $curlResponse = curl_exec($curl);

        if(curl_getinfo($curl)['http_code'] != 204)
        {
            $logfile = fopen('log.txt', 'w');
            fwrite($logfile, $curlResponse . "\n");
            fclose($logfile);
        }
        curl_close($curl);
        $enums = json_decode($curlResponse);
        return $enums;

    }


    /**
     * sendCreatives
     *
     * send a list of creatives via REST API including the files themselves as base64 encoded binaries
     *
     * @param mixed $creatives
     * @param mixed $feedId
     * @param mixed $categoryId
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function sendCreatives($creatives, $feedId, $categoryId, $groupId=null)
    {
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['sendCreative'];
        $curl = $this->getCurl($resource, 'POST');

        $param = new StdClass();
        $param->creativeImageModels = $creatives;
        $param->idAdvertiser        = $this->getAdvertiserId();
        $param->idAuditUser         = $this->getAuditUserId();
        $param->idCategory          = $categoryId;
        $param->idFeed              = $feedId;
        $param->idGroup             = $groupId;

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        // $curl_response = curl_exec($curl);
        $curlResponse = curl_exec($curl);

        if(curl_getinfo($curl)['http_code'] != 204)
        {
            $logfile = fopen('log.txt', 'w');
            fwrite($logfile, $curlResponse . "\n" . json_encode($param));
            fclose($logfile);
        }

        curl_close($curl);
    }


    /**
     * sendCreative
     *
     * simple wrapper for sending only one creative
     *
     * @param mixed $creative
     * @param mixed $feedId
     * @param mixed $categoryId
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function sendCreative($creative, $feedId, $categoryId, $groupId=null)
    {
        $this->sendCreatives(array($creative), $feedId, $categoryId, $groupId);
    }


    /**
     * getProductsByCategory
     *
     * returns all products for a given category for the currently set company and advertiser
     *
     * @param mixed $categoryId
     * @access public
     * @return void
     */
    public function getProductsByCategory($categoryId)
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{categoryId}', $categoryId, $this->serviceCalls['getProductsByCategory']);
        $resource = str_replace('{companyId}', $this->companyId, $resource);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $productList = json_decode($curlResponse)->products;

        $products = array();

        foreach($productList AS $product)
        {
            $products[] = $this->populateProduct($product);
        }

        return $products;

    }


    public function getCategories()
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{companyId}', $this->companyId, $this->serviceCalls['getCategories']);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $categories = json_decode($curlResponse)->categories;
        $categoriesProcessed = array();

        foreach($categories AS $category)
        {
            $curCategory           = new StdClass();
            $curCategory->id       = $category->idCategory;
            $curCategory->status   = $category->idStatusType;
            $curCategory->name     = $category->categoryName;
            $curCategory->url      = $category->categoryUrl;
            $curCategory->number   = $category->categoryNumber;
            $categoriesProcessed[] = $curCategory;
        }

        return $categoriesProcessed;

    }



    /**
     * getNumTemplates
     *
     * @return int
     */
    public function getNumTemplates()
    {
        $templateCount = count($this->getTemplates());
        return $templateCount;
    }


    /**
     * getTemplatesByGroup
     *
     *
     *
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function getTemplatesByGroupId($groupId)
    {
        return $this->getTemplates($groupId);
    }


    /**
     * getTemplates
     *
     * @return array
     * @throws Exception
     */
    public function getTemplates($groupId=null)
    {
        if(!isset($this->advertiserId))
        {
            throw new Exception('advertiserId not set');
        }

        if(null === $groupId)
        {
            $resource = REST_API_SERVICE_URL . '/' . str_replace('{advertiserId}', $this->advertiserId, $this->serviceCalls['getTemplates']);
        }
        else
        {
            $call = $this->serviceCalls['getTemplatesByGroup'];
            $call = str_replace('{advertiserId}', $this->advertiserId, $call);
            $call = str_replace('{groupId}', $groupId, $call);
            $resource = REST_API_SERVICE_URL . '/' . $call;
        }
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);

        curl_close($curl);

        $templateList = json_decode($curlResponse)->bannerTemplateModels;

        $templates = array();

        foreach($templateList AS $template)
        {
            $templates[] = $this->populateBannerTemplate($template);
        }

        return $templates;
    }


    public function getTemplateById($templateId)
    {
        if(!isset($templateId))
        {
            throw new Exception('bannerTemplateId not set');
        }

        $resource = REST_API_SERVICE_URL . '/' . str_replace('{templateId}', $templateId, $this->serviceCalls['getTemplateById']);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        return $this->populateBannerTemplate(json_decode($curlResponse));
    }

    /**
     * @param $template
     * @return mixed
     */
    public function sendBannerTemplate(BannerTemplateModel $template)
    {
        $template = json_encode($template->jsonSerialize());
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['postTemplate'];
        $curl = $this->getCurl($resource, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $template);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        return $curlResponse;
    }

    /**
     * @param $templateId
     * @return mixed
     */
    public function deleteBannerTemplate($templateId)
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{templateId}', $templateId, $this->serviceCalls['deleteTemplate']);
        $curl = $this->getCurl($resource, 'DELETE');

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        return $curlResponse;
    }

    /**
     * @param $serviceUrl
     * @param $method
     * @return resource
     */
    private function getCurl($serviceUrl, $method)
    {
        $curl = curl_init($serviceUrl);
        $baseAuthUserPwd = (REST_API_USERNAME . ':' . REST_API_PASSWORD);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $baseAuthUserPwd);
        if($method === 'GET')
        {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        else if($method === 'PUT')
        {
            curl_setopt($curl, CURLOPT_PUT, true);
        }
        else if ($method === 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        else if ($method === 'DELETE')
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        return $curl;
    }

    /**
     * populateBannerTemplate
     *
     * @param mixed $template
     * @access private
     * @return void
     */
    private function populateBannerTemplate($template)
    {
        $bannerTemplateModel = new BannerTemplateModel();
        $bannerTemplateModel->setAdvertiserId($this->advertiserId);
        $bannerTemplateModel->setAuditUserId((int) $template->idAuditUser);
        $bannerTemplateModel->setDescription((string) $template->description);
        $bannerTemplateModel->setName((string) $template->name);
        $bannerTemplateModel->setBannerTemplateId((int) $template->idBannerTemplate);

        // idParentBanner can be null and php casts null to 0!
        $bannerTemplateModel->setParentBannerTemplateId($template->idParentBannerTemplate);
        $bannerTemplateModel->setSvgContent($template->svgContent);
        $bannerTemplateModel->setDimX((int) $template->dimX);
        $bannerTemplateModel->setDimY((int) $template->dimY);
        $bannerTemplateModel->setGroupId((int) $template->idGroup);

        return $bannerTemplateModel;
    }

    /**
     * populateProduct
     *
     * @param mixed $product
     * @access private
     * @return void
     */
    private function populateProduct($product)
    {
        $productModel = new ProductModel();

        $productModel->setProductId($product->idProduct);
        $productModel->setFeedId($product->idFeed);
        $productModel->setCategoryId($product->idCategory);
        $productModel->setCurrencyId($product->idCurrency);

        $productModel->setEan($product->productNumberIsbn);
        $productModel->setIsbn($product->productNumberEan);

        $productModel->setName($product->productName);
        $productModel->setProductUrl($product->productUrl);
        $productModel->setImageUrl($product->productUrlImage);
        $productModel->setDescription($product->productDescription);
        $productModel->setPrice($product->productPrice);
        $productModel->setPriceOld($product->productPriceOld);

        $productModel->setAggregationNumber($product->productNumberAggregation);

        $productModel->setShipping($product->productPriceShipping);
        $productModel->setPromotionStartDate($product->datePromotionStart);
        $productModel->setPromotionEndDate($product->datePromotionEnd);

        $productModel->setProductSize($product->productPropertySize);
        $productModel->setGender($product->idGender);
        $productModel->setColor($product->colour);

        return $productModel;
    }

    /**
     * Get advertiserId.
     *
     * @return advertiserId.
     */
    public function getAdvertiserId()
    {
        return $this->advertiserId;
    }

    /**
     * Set advertiserId.
     *
     * @param advertiserId the value to set.
     */
    public function setAdvertiserId($advertiserId)
    {
        $this->advertiserId = $advertiserId;
    }

    /**
     * Get companyId.
     *
     * @return companyId.
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * Set companyId.
     *
     * @param companyId the value to set.
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
    }



    /**
     * Get auditUserId.
     *
     * @return auditUserId.
     */
    public function getAuditUserId()
    {
        if(!isset($this->auditUserId))
        {
            throw new Exception('AuditUserId not provided!');
        }
        else
        {
            return $this->auditUserId;
        }
    }

    /**
     * Set auditUserId.
     *
     * @param auditUserId the value to set.
     */
    public function setAuditUserId($auditUserId)
    {
        $this->auditUserId = $auditUserId;
    }
}

