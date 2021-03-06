<?php
set_time_limit(0);
/**
 * Handles the creation of rectangles while rendering GIF and SWF
 *
 * User: thomas.hummel@mediadecision.com
 * Date: 17/07/2014
 * Time: 09:01
 */

class GfxRectangle extends GfxShape
{
    public function __construct(GfxContainer $container)
    {
        parent::__construct($container);
    }


    public function updateData()
    {
        parent::updateData();

        if($this->getContainer()->getProductData())
        {
            if(!empty($this->getLinkUrl()))
            {
                echo "\n[" . $this->getLinkUrl() . "]\n";
            }
        }
    }


    /**
     * renderSWF
     *
     * renders itself inside of the swf canvas and passes the modified canvas back;
     *
     * @param mixed $canvas
     * @access public
     * @return void
     */
    public function renderSWF($canvas)
    {
        $rect = new SWFShape();

        $sprite = new SWFSprite();
        $sprite->setFrames($this->getContainer()->getFramerate());

        if($this->shadowEnabled() && $this->getShadow()->getColor() instanceof GfxColor)
        {
            $shadow = new SWFShape();
            $shadowX1 = $this->getX() + $this->getShadow()->getDist();
            $shadowY1 = $this->getY() + $this->getShadow()->getDist();
            $shadowX2 = $shadowX1 + $this->getWidth();
            $shadowY2 = $shadowY1 + $this->getHeight();

            $shadowX1 = -($this->getWidth() /  2) + $this->getShadow()->getDist();
            $shadowY1 = -($this->getHeight() / 2) + $this->getShadow()->getDist();
            $shadowX2 = ($this->getWidth() /   2) + $this->getShadow()->getDist();
            $shadowY2 = ($this->getHeight() /  2) + $this->getShadow()->getDist();

            $shadowColor = $this->getShadow()->getColor();
            $shadowFill = $shadow->addFill($shadowColor->getR(), $shadowColor->getG(), $shadowColor->getB(), 128);
            $shadow->setRightFill($shadowFill);

            $shadow->movePenTo($shadowX1, $shadowY1);
            $shadow->drawLineTo($shadowX1, $shadowY2);
            $shadow->drawLineTo($shadowX2, $shadowY2);
            $shadow->drawLineTo($shadowX2, $shadowY1);
            $shadow->drawLineTo($shadowX1, $shadowY1);

            $shandle = $sprite->add($shadow);

            $shandle->moveTo($this->getX() + $this->getWidth() / 2, $this->getY() + $this->getHeight() / 2);
        }

        $r = $this->getFill()->getR();
        $g = $this->getFill()->getG();
        $b = $this->getFill()->getB();
        $a = $this->getFill()->getAlpha();

        $fill = $rect->addFill($r, $g, $b, $a);
        $rect->setRightFill($fill);

        $x1 = $this->getX();
        $y1 = $this->getY();
        $x2 = $this->getX() + $this->getWidth();
        $y2 = $this->getY() + $this->getHeight();

        $x1 = -($this->getWidth() / 2);
        $y1 = -($this->getHeight() / 2);
        $x2 = ($this->getWidth() / 2);
        $y2 = ($this->getHeight() / 2);

        if($this->strokeEnabled() && $this->getStroke() instanceof GfxStroke)
        {
            $rect->setLine(1, 0, 0, 0);
        }

        $rect->movePenTo($x1, $y1);
        $rect->drawLineTo($x1, $y2);
        $rect->drawLineTo($x2, $y2);
        $rect->drawLineTo($x2, $y1);
        $rect->drawLineTo($x1, $y1);

        $handle = $sprite->add($rect);

        if($this->drawCenter)
        {
            $chandle = $this->drawCenter($sprite);
        }

        $handle->moveTo($this->getX() + $this->getWidth() / 2, $this->getY() + $this->getHeight() / 2);


        /**
         *  Prepare actual animation
        **/
        if(count($this->getAnimations()) > 0)
        {
            $handleList = array();
            if(isset($chandle))
            {
                $handleList['centerHandle'] = $chandle;
            }
            if(isset($shandle))
            {
                $handleList['shadowHandle'] = $shandle;
            }
            $handleList['handle'] = $handle;
            $sprite = $this->swfAnimate($handleList, $sprite);
        }
        /**
         *  Animation done!
        **/

        $handle = $canvas->add($sprite);

        // absolutely required, otherwise nothing will be displayed
        $sprite->nextFrame();

        return $canvas;
    }



    public function renderGIF($transformationList = null)
    {
        if(!isset($this->gifParams))
        {
            $this->gifParams = new GifAnimationContainer($this);
        }
        //set the color for the layer
        $transparent = new ImagickPixel("rgba(127,127,127,0)");

        $imageWidth  = $this->getContainer()->getCanvasWidth();
        $imageHeight = $this->getContainer()->getCanvasHeight();

        foreach($transformationList AS $attribute => $stepsize)
        {
            $stepsize = $stepsize;
            switch($attribute)
            {
                case 'x':
                    $this->gifParams->x += $stepsize;
                    break;
                case 'y':
                    $this->gifParams->y += $stepsize;
                    break;
                case 'w':
                    $this->gifParams->width *= $stepsize;
                    break;
                case 'h':
                    $this->gifParams->height *= $stepsize;
                    break;
                case 'r':
                    $this->gifParams->rotation += $stepsize;
                    break;
                default:
                    break;
            }
        }
        $x        = $this->gifParams->x;
        $y        = $this->gifParams->y;
        $width    = $this->gifParams->width;
        $height   = $this->gifParams->height;
        $rotation = $this->gifParams->rotation;

        $rectangle = new ImagickDraw();
        $rectangle->setGravity(Imagick::GRAVITY_CENTER);

        $frame = new Imagick();
        $frame->newimage($imageWidth, $imageHeight, $transparent);

        if($this->getFill()->getR() !== null)
        {
            $color = new ImagickPixel($this->getFill()->getHex());
            $rectangle->setFillColor($color);
        }
        else
        {
            $color = new ImagickPixel($this->getStroke()->getColor()->getHex());
            $rectangle->setFillcolor($color);
        }


        if($this->shadowEnabled() && $this->hasShadow())
        {
            $shadow = $this->createShadow();
            $frame->drawImage($shadow);
        }

        if($this->strokeEnabled() && $this->hasStroke())
        {
            $this->createStroke($rectangle);
        }

        $x1 = -$this->gifParams->width / 2;
        $y1 = -$this->gifParams->height / 2;
        $x2 = $this->gifParams->width;
        $y2 = $this->gifParams->height;

        $targetX = ($x + $width  / 2) + (($this->getWidth()  - $this->gifParams->width) /  2);
        $targetY = ($y + $height / 2) + (($this->getHeight() - $this->gifParams->height) / 2);

        $rectangle->rectangle($x1, $y1, $x2, $y2);
        $frame->drawImage($rectangle);
        $distort = array($width/2, $height/2, 1, -$rotation, $targetX, $targetY);
        $frame->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
        // TODO: this is most likely the most performance smashing line of code in the
        // entire GIF rendering process ... try to optimize or even get rid of it eventually?!
        $frame->distortImage(imagick::DISTORTION_SCALEROTATETRANSLATE, $distort, false);

        return $frame;
    }

    // TODO: rename those functions in order to reflect the fact that they will only work for GIF!!
    public function createShadow()
    {
        $color = new ImagickPixel($this->getShadow()->getColor()->getHex());

        $dist = $this->getShadow()->getDist();

        $x1 = $dist;
        $y1 = $dist;
        $x2 = $this->gifParams->width + $dist;
        $y2 = $this->gifParams->height + $dist;

        $shadow = new ImagickDraw();
        $shadow->setFillColor($color);
        $shadow->setFillOpacity(0.5);
        $shadow->rectangle($x1, $y1, $x2, $y2);

        return $shadow;
    }

    public function createStroke($rectangle)
    {
        $color = new ImagickPixel($this->getStroke()->getColor()->getHex());
        $rectangle->setStrokeWidth($this->getStroke()->getWidth());
        $rectangle->setStrokeColor($color);
    }

    public function getSvg()
    {
        $stroke = $this->getStroke();
        $shadow = $this->getShadow();

        $svg = '';

        $svg .= "\r\n" . '<rect';
        $svg .= "\r\n" . ' cmeo:link="' . $this->getLinkUrl() . '"';
        $svg .= "\r\n" . ' cmeo:editGroup="' . $this->getEditGroup(). '"';
        $svg .= "\r\n" . ' fill="' . $this->getFill()->getHex() . '"';

        if(isset($stroke))
        {
            $svg .= "\r\n" . ' stroke="' . $stroke->getColor()->getHex() . '"';
            $svg .= "\r\n" . ' stroke-width="' . $stroke->getWidth() . '"';
        }

        if(isset($shadow))
        {
            $svg .= "\r\n" . ' style="shadow:' . $shadow->getColor()->getHex() . ';shadow-dist:' . $shadow->getDist() . 'px;"';
        }

        if(count($this->getAnimations()) > 0)
        {
            $aniString  = ' cmeo:animation="';
            $aniString .= $this->serializeAnimations();
            $aniString .= '"';
            $svg .= $aniString;
        }

        $svg .= "\r\n" . ' x="' . $this->getX() . '"';
        $svg .= "\r\n" . ' y="' . $this->getY() . '"';
        $svg .= "\r\n" . ' width="' . $this->getWidth() . '"';
        $svg .= "\r\n" . ' height="' . $this->getHeight() . '"';
        $svg .= "\r\n" . ' id="' . $this->getId() . '"';
        $svg .= "\r\n" . '/>';
        return $svg;
    }
}
