<?php

    /**
     * Created by PhpStorm.
     * User: moritz
     * Date: 10.03.17
     * Time: 16:07
     */

    class Image
    {
        /**
         * @function public __construct()
         * @param string $url
         * @return self
         */
        public function __construct($url)
        {
            $info = getimagesize($url);
            $mime = $info['mime'];
            list($resourceWidth, $resourceHeight) = $info;

            // Set image create function
            switch ($mime) {
                case "image/png":
                    $create_func = "imagecreatefrompng";
                    break;
                case "image/jpeg":
                    $create_func = "imagecreatefromjpeg";
                    break;
                default:
                    $create_func = false;
                    break;
            }

            // Define Class Attributes

            $this->url = $url;
            $this->resource = $create_func($this->url);
            $this->resourceWidth = $resourceWidth;
            $this->resourceHeight = $resourceHeight;


            // Predefine
            $this->containerSize = [];

            return self::class;
        }

        /**
         * @function public render()
         * @param string $type
         * @param boolean $header
         *        Set the image-specific content-type or
         *        return the image-stream as string
         * @param resource $resource
         * @return string|resource
         */
        public function render($resource=null, $type='png', $header=true)
        {

            $resource = ($resource === null || !is_resource($resource)) ? $this->resource : $resource;

            if(is_resource($resource))
            {
                switch($type) {
                    case "png":
                        $mime = "png";
                        $save = "imagepng";
                        break;
                    case "jpg"||"jpeg":
                        $mime = "jpg";
                        $save = "imagejpeg";
                        break;
                    default:
                        $mime = "png";
                        $save = "imagepng";
                        break;
                }

                if($header)
                    header('Content-Type: image/'.$mime);

                return $save($resource, null);
            } else {
                return false;
            }
        }

        /**
         * @function public addContainer()
         * @param integer $width
         * @param integer $height
         * @return bool|resource $container
         */
        public function addContainer($width=1200, $height=600)
        {
            if($width > 0 && $height > 0) {
                $this->containerSize['width'] = $width;
                $this->containerSize['height'] = $height;
                $container = imagecreatetruecolor($width, $height);
                return $container;
            } else {
                return false;
            }
        }

        /**
         * @function public setBackgroundColor()
         * @param resource $container
         * @param string $hexColor
         * @return bool|resource $container
         */
        public function setBackgroundColor($container, $hexColor)
        {
            if(is_resource($container) && strlen($hexColor) == 6 && preg_match('/[AaBbCcDdEeFf0-9]/', $hexColor)) {
                $r = hexdec(substr($hexColor, 0, 2));
                $g = hexdec(substr($hexColor, 2, 2));
                $b = hexdec(substr($hexColor, 4, 2));
                $bgcolor = imagecolorallocate($container, $r, $g, $b);
                imagefill($container, 0, 0, $bgcolor);
                return $container;
            } else {
                return false;
            }
        }

        /**
         * @function public resize()
         * @param integer $newWidth
         * @param integer $newHeight
         * @param boolean $aspectRatio
         * @param string $by
         * @return resource $image
         */
        public function resize($newWidth, $newHeight, $aspectRatio=false, $by=null)
        {

            $tmp = $this->resource;

            // In case any unexpected errors occur while processing
            $image = $tmp;

            if($aspectRatio && ($by == 'height' || $by == 'width')) {
                switch($by) {
                    case 'height':
                        $ratio = $newHeight / $this->resourceHeight;
                        $image = imagescale($tmp, $this->resourceWidth*$ratio, $newHeight);
                        break;
                    case 'width':
                        $image = imagescale($tmp, $newWidth);
                        break;
                }
            } else {
                $image = imagescale($tmp, $newWidth, $newHeight);
            }

            return $image;
        }

        /**
         * @function public dynamicAlignSourceToContainer()
         * @param resource $container
         * @param float $margin
         * @return bool|resource $img
         *
         * @description Function to dynamically align the src image to the given container
         *              - Auto resize
         *              - Auto margin of 10%
         *              - Auto center
         *              - Works vertically, horizontally and as a square (SRC & Container)
         */
        public function dynamicAlignSourceToContainer($container, $margin=1.0)
        {

            if($margin <= 0)
                $margin = 1.0;
            elseif($margin > 1.0 && $margin <= 100)
                $margin = $margin/100;

            $rh = $this->resourceHeight;
            $rw = $this->resourceWidth;
            $ch = imagesy($container);
            $cw = imagesx($container);

            $img = false;

            if( $cw > $ch ) {

                // Container is Horizontal
                $targetHeight = $rh * $margin;     // 90% of Container Height
                $targetWidth = $this->resourceWidth * $margin;

                while($targetHeight > $rh || $targetHeight > $ch) {
                    $targetHeight -= 10;
                }

                $ratio = $targetHeight / $this->resourceHeight;

                while($targetWidth*$ratio > $rw) {
                    $targetWidth -= 10;
                }

                while($targetWidth*$ratio > $cw) {
                    $targetWidth = $targetWidth - 10;
                }

                $ratio2 = $targetWidth / $rw;

                $targetHeight = $targetHeight * $ratio2;
                $targetWidth = $targetWidth * $ratio;

                $img = $this->resize($targetWidth, $targetHeight);

            } elseif($cw <= $ch) {
                // Container is Vertical

                $targetHeight = $rh * $margin;     // 90% of Container Height
                $targetWidth = $this->resourceWidth * $margin;

                while($targetHeight > $rh || $targetHeight > $ch) {
                    $targetHeight -= 10;
                }

                $ratio = $targetHeight / $this->resourceHeight;

                while($targetWidth*$ratio > $rw*$margin) {
                    $targetWidth -= 10;
                }

                while($targetWidth*$ratio > $cw*$margin) {
                    $targetWidth = $targetWidth - 10;
                }

                $ratio2 = $targetWidth / $rw;

                $targetHeight = $targetHeight * $ratio2;
                $targetWidth = $targetWidth * $ratio;

                $img = $this->resize($targetWidth, $targetHeight);

            }

            return $img;

        }

        /**
         * @function public blur()
         * @param resource $image
         * @param integer $rounds
         * @return bool|resource $image
         *
         * @description Apply Gaussian Blur filter $rounds times to the given image resource
         */
        public function blur($image, $rounds=8)
        {
            if(is_resource($image) && is_numeric($rounds) && intval($rounds) > 0) {

                for( $i = 0; $i < $rounds; $i++ ) {
                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                }

                return $image;

            } else {
                return false;
            }
        }

        /**
         * @function public pixelate()
         * @param resource $image
         * @param integer $amount_x
         * @param integer $amount_y
         * @return bool|resource $img
         */
        public function pixelate($image, $amount_x, $amount_y)
        {
            imagefilter($image, IMG_FILTER_PIXELATE, $amount_x, $amount_y);
            return $image;
        }

        /**
         * @function public setBackgroundImage()
         * @param resource $container
         * @return bool|resource $img
         *
         * @description Auto resize the SRC image to fill the whole container element
         *              no matter if it's pixeled or not
         */
        public function setBackgroundImage($container)
        {
            $rh = $this->resourceHeight;
            $rw = $this->resourceWidth;
            $ch = $this->containerSize['height'];
            $cw = $this->containerSize['width'];

            $img = false;

            $newHeight = $rh;
            $newWidth = $rw;
            while($newHeight < $ch || $newWidth < $cw) {
                $newHeight += 10;
                $newWidth += 10;
            }

            $img = $this->resize($newWidth, $newHeight);
            $pos_x = - ( ( $cw / 2 ) - ( $newWidth / 2 ) );
            $pos_y = - ( ( $ch / 2 ) - ( $newHeight / 2 ) );
            imagecopy($container, $img, 0, 0, $pos_x, $pos_y, $cw, $ch);
            $img = $container;

            return $img;
        }

        /**
         * @function public combine()
         * @param resource $background
         * @param resource $foreground
         * @return bool|resource $image
         *
         * @description Function to combine the generated Back- and Foreground
         */
        public function combine($background, $foreground)
        {
            $f_x = imagesx($foreground);
            $f_y = imagesy($foreground);
            $b_x = imagesx($background);
            $b_y = imagesy($background);
            $pos_x = ( ($b_x / 2) - ($f_x / 2) );
            $pos_y = ( ($b_y / 2) - ($f_y / 2) );
            imagecopyresampled($background, $foreground, $pos_x, $pos_y, 0, 0, $f_x, $f_y, $f_x, $f_y);
            return $background;
        }
    }
