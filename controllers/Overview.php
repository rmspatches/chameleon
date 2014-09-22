<?php
/**
 * Created by IntelliJ IDEA.
 * User: thomas
 * Date: 23.07.14
 * Time: 11:38
 */

class Overview extends Controller
{
    private $advertiserId;
    private $companyId;
    private $view;

    /**
     * @return TemplateEngine|void
     * @throws Exception
     */
    public function create()
    {
        // create required objects
        $container = new GfxContainer();
        $connector = new APIConnector();
        $templates = array();
        $previews  = array();
        $loadError = false;

        $connector->setAdvertiserId($this->getAdvertiserId());
        $connector->setCompanyId($this->getCompanyId());

        $container->setAdvertiserId($this->getAdvertiserId());
        $container->setCompanyId($this->getCompanyId());
        $container->setCategoryId(0);
        $container->setPreviewMode(true);

        $this->view = $this->setLayout('views/overview.phtml')->getView();

        // get all templates for company / advertiser
        try
        {
            $templates = $connector->getTemplates();
        }
        catch(Exception $e)
        {
            $this->view->message = 'An error occured: ' . $e->getMessage();
            $loadError = true;
        }

        if(!$loadError)
        {
            if(count($templates) == 0)
            {
                $this->view->message = 'No templates found!';
            }
            else
            {
                foreach($templates as $template)
                {
                    $baseFilename = 'rtest_' . $template->getBannerTemplateId();
                    $filename = $baseFilename . '.svg';
                    $container->setOutputName($baseFilename);

                    $container->setSource($template->getSvgContent());
                    $container->setId($template->getBannerTemplateId());
                    $container->parse();
                    $container->saveSvg();

                    $container->setTarget('GIF');
                    $container->render();

                    $file = BASE_DIR . "/output/" . $container->getOutputDir() . '/' . $baseFilename . '.gif';

                    $preview = new StdClass();
                    $preview->filePath = $file;
                    $preview->width = $container->getCanvasWidth() / 2 > 300 ? 300 : $container->getCanvasWidth() / 2;
                    $preview->height = $container->getCanvasHeight();
                    $preview->templateId = $template->getBannerTemplateId();
                    $preview->templateName = $filename;
                    $preview->templateId = $template->getBannerTemplateId();
                    $preview->advertiserId = $this->getAdvertiserId();
                    $preview->companyId = $this->getCompanyId();
                    $preview->fileSize = $this->getRemoteFileSize($file);
                    $preview->fileDateCreated = $this->getRemoteFileDate($file);  //replace with database timestamp
                    $preview->fileDateGenerated = $this->getRemoteFileDate($file);

                    if($container->getCanvasWidth() >= $container->getCanvasHeight())
                    {
                        $newHeight = $container->getCanvasHeight() * (281 / $container->getCanvasWidth());
                        $preview->marginTop = (481 - intval($newHeight)) / 4;
                    }
                    else
                    {
                        $preview->marginTop = 4;
                    }

                    $previews[] = $preview;
                }
            }
        }

        $this->view->templates = $templates;
        $this->view->previews = $previews;

        return $this->view;
    }

    public function display()
    {
        echo $this->view;
    }

    public function getRemoteFileSize($url)
    {
        static $regex = '/^Content-Length: *+\K\d++$/im';
        if (!$fp = @fopen($url, 'rb')) {
            return false;
        }
        if (
            isset($http_response_header) &&
            preg_match($regex, implode("\n", $http_response_header), $matches)
        ) {
            return number_format((int)$matches[0] / 1000, 2);
        }

        $fileSize = strlen(stream_get_contents($fp)) / 1000;

        return $fileSize;
    }

    public function getRemoteFileDate($url)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL,$url);
        curl_setopt($c, CURLOPT_HEADER,1);//Include Header In Output
        curl_setopt($c, CURLOPT_NOBODY,1);//Set to HEAD & Exclude body
        curl_setopt($c, CURLOPT_RETURNTRANSFER,1);//No Echo/Print
        curl_setopt($c, CURLOPT_TIMEOUT,5);//5 seconds max, to get the HEAD header.
        curl_setopt($c, CURLOPT_FILETIME, true);
        $cURL_RESULT = curl_exec($c);

        if($cURL_RESULT !== FALSE)
        {
            return date("Y-m-d H:i:s", curl_getinfo($c, CURLINFO_FILETIME));
        }
    }

//    private function clearOutputDirectory($path)
//    {
//        $files = glob($path . '*.*');
//
//        foreach ($files as $file)
//        {
//            if (is_file($file))
//            {
//                unlink($file);
//            }
//        }
//    }

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


}
