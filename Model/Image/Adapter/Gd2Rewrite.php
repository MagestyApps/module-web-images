<?php

namespace MagestyApps\WebImages\Model\Image\Adapter;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Image\Adapter\Gd2;
use Magento\Framework\Phrase;

class Gd2Rewrite extends Gd2
{
    /**
     * Image output callbacks by type
     *
     * @var array
     */
    private static $_callbacks = [
        IMAGETYPE_WEBP => ['output' => 'imagewebp', 'create' => 'imagecreatefromwebp'],
    ];

    /**
     * Open image for processing
     *
     * @param string $filename
     * @return void
     * @throws \OverflowException|FileSystemException
     */
    public function open($filename)
    {
        if (!file_exists($filename)) {
            throw new FileSystemException(
                new Phrase('File "%1" does not exist.', [$this->_fileName])
            );
        }
        if (!$filename || filesize($filename) === 0 || !$this->validateURLScheme($filename)) {
            throw new \InvalidArgumentException('Wrong file');
        }

        $this->_fileName = $filename;
        $this->_reset();
        $this->getMimeType();
        if (!isset(self::$_callbacks[$this->_fileType])) {
            parent::open($filename);
            return;
        }

        $this->_getFileAttributes();
        if ($this->_isMemoryLimitReached()) {
            throw new \OverflowException('Memory limit has been reached.');
        }
        $this->imageDestroy();
        $this->_imageHandler = call_user_func(
            $this->_getCallback('create', null, sprintf('Unsupported image format. File: %s', $this->_fileName)),
            $this->_fileName
        );
    }

    /**
     * Save image to specific path.
     *
     * If some folders of path does not exist they will be created
     *
     * @param null|string $destination
     * @param null|string $newName
     * @return void
     * @throws \Exception  If destination path is not writable
     */
    public function save($destination = null, $newName = null)
    {
        if (!isset(self::$_callbacks[$this->_fileType])) {
            parent::save($destination, $newName);
            return;
        }

        $fileName = $this->_prepareDestination($destination, $newName);

        if (!$this->_resized) {
            // keep alpha transparency
            $isAlpha = false;
            $isTrueColor = false;
            $this->_getTransparency($this->_imageHandler, $this->_fileType, $isAlpha, $isTrueColor);
            if ($isAlpha) {
                if ($isTrueColor) {
                    $newImage = imagecreatetruecolor($this->_imageSrcWidth, $this->_imageSrcHeight);
                } else {
                    $newImage = imagecreate($this->_imageSrcWidth, $this->_imageSrcHeight);
                }
                $this->_fillBackgroundColor($newImage);
                imagecopy($newImage, $this->_imageHandler, 0, 0, 0, 0, $this->_imageSrcWidth, $this->_imageSrcHeight);
                $this->imageDestroy();
                $this->_imageHandler = $newImage;
            }
        }

        // Enable interlace
        imageinterlace($this->_imageHandler, true);

        // Convert image to RGB
        imagepalettetotruecolor($this->_imageHandler);

        // Set image quality value
        switch ($this->_fileType) {
            case IMAGETYPE_PNG:
                $quality = 9;   // For PNG files compression level must be from 0 (no compression) to 9.
                break;

            case IMAGETYPE_JPEG:
                $quality = $this->quality();
                break;

            default:
                $quality = null;    // No compression.
        }

        // Prepare callback method parameters
        $functionParameters = [$this->_imageHandler, $fileName];
        if ($quality) {
            $functionParameters[] = $quality;
        }

        call_user_func_array($this->_getCallback('output'), $functionParameters);
    }

    /**
     * Render image and return its binary contents.
     *
     * @see \Magento\Framework\Image\Adapter\AbstractAdapter::getImage
     *
     * @return string
     */
    public function getImage()
    {
        if (!isset(self::$_callbacks[$this->_fileType])) {
            return parent::getImage();
        }

        ob_start();
        call_user_func($this->_getCallback('output'), $this->_imageHandler);
        return ob_get_clean();
    }

    /**
     * Add watermark to image
     *
     * @param string $imagePath
     * @param int $positionX
     * @param int $positionY
     * @param int $opacity
     * @param bool $tile
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function watermark($imagePath, $positionX = 0, $positionY = 0, $opacity = 30, $tile = false)
    {
        if (!isset(self::$_callbacks[$this->_fileType])) {
            parent::watermark($imagePath, $positionX, $positionY, $opacity, $tile);
            return;
        }

        list($watermarkSrcWidth, $watermarkSrcHeight, $watermarkFileType,) = $this->_getImageOptions($imagePath);
        $this->_getFileAttributes();
        $watermark = call_user_func(
            $this->_getCallback('create', $watermarkFileType, 'Unsupported watermark image format.'),
            $imagePath
        );

        $merged = false;

        $watermark = $this->createWatermarkBasedOnPosition($watermark, $positionX, $positionY, $merged, $tile);

        imagedestroy($watermark);
        $this->refreshImageDimensions();
    }

    /**
     * Obtain function name, basing on image type and callback type
     *
     * @param string $callbackType
     * @param null|int $fileType
     * @param string $unsupportedText
     * @return string
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     */
    private function _getCallback($callbackType, $fileType = null, $unsupportedText = 'Unsupported image format.')
    {
        if (null === $fileType) {
            $fileType = $this->_fileType;
        }

        if (empty(self::$_callbacks[$fileType])) {
            throw new \InvalidArgumentException($unsupportedText);
        }
        if (empty(self::$_callbacks[$fileType][$callbackType])) {
            throw new \BadFunctionCallException('Callback not found.');
        }
        return self::$_callbacks[$fileType][$callbackType];
    }

    /**
     * Checks for invalid URL schema if it exists
     *
     * @param string $filename
     * @return bool
     */
    private function validateURLScheme(string $filename) : bool
    {
        $allowed_schemes = ['ftp', 'ftps', 'http', 'https'];
        $url = parse_url($filename);
        if ($url && isset($url['scheme']) && !in_array($url['scheme'], $allowed_schemes)) {
            return false;
        }

        return true;
    }

    /**
     * Helper function to free up memory associated with _imageHandler resource
     *
     * @return void
     */
    private function imageDestroy()
    {
        if (is_resource($this->_imageHandler)) {
            imagedestroy($this->_imageHandler);
        }
    }

    /**
     * Checks if image has alpha transparency
     *
     * @param resource $imageResource
     * @param int $fileType
     * @param bool $isAlpha
     * @param bool $isTrueColor
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    private function _getTransparency($imageResource, $fileType, &$isAlpha = false, &$isTrueColor = false)
    {
        $isAlpha = false;
        $isTrueColor = false;
        // assume that transparency is supported by gif/png only
        if (IMAGETYPE_GIF === $fileType || IMAGETYPE_PNG === $fileType) {
            // check for specific transparent color
            $transparentIndex = imagecolortransparent($imageResource);
            if ($transparentIndex >= 0) {
                return $transparentIndex;
            } elseif (IMAGETYPE_PNG === $fileType) {
                // assume that truecolor PNG has transparency
                $isAlpha = $this->checkAlpha($this->_fileName);
                $isTrueColor = true;
                // -1
                return $transparentIndex;
            }
        }
        if (IMAGETYPE_JPEG === $fileType) {
            $isTrueColor = true;
        }
        return false;
    }

    /**
     * Fill image with main background color.
     *
     * Returns a color identifier.
     *
     * @param resource &$imageResourceTo
     * @return int
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function _fillBackgroundColor(&$imageResourceTo)
    {
        // try to keep transparency, if any
        if ($this->_keepTransparency) {
            $isAlpha = false;
            $transparentIndex = $this->_getTransparency($this->_imageHandler, $this->_fileType, $isAlpha);
            try {
                // fill truecolor png with alpha transparency
                if ($isAlpha) {
                    if (!imagealphablending($imageResourceTo, false)) {
                        throw new \InvalidArgumentException('Failed to set alpha blending for PNG image.');
                    }
                    $transparentAlphaColor = imagecolorallocatealpha($imageResourceTo, 0, 0, 0, 127);
                    if (false === $transparentAlphaColor) {
                        throw new \InvalidArgumentException('Failed to allocate alpha transparency for PNG image.');
                    }
                    if (!imagefill($imageResourceTo, 0, 0, $transparentAlphaColor)) {
                        throw new \InvalidArgumentException('Failed to fill PNG image with alpha transparency.');
                    }
                    if (!imagesavealpha($imageResourceTo, true)) {
                        throw new \InvalidArgumentException('Failed to save alpha transparency into PNG image.');
                    }

                    return $transparentAlphaColor;
                } elseif (false !== $transparentIndex) {
                    // fill image with indexed non-alpha transparency
                    $transparentColor = false;
                    if ($transparentIndex >= 0 && $transparentIndex <= imagecolorstotal($this->_imageHandler)) {
                        list($r, $g, $b) = array_values(imagecolorsforindex($this->_imageHandler, $transparentIndex));
                        $transparentColor = imagecolorallocate($imageResourceTo, $r, $g, $b);
                    }
                    if (false === $transparentColor) {
                        throw new \InvalidArgumentException('Failed to allocate transparent color for image.');
                    }
                    if (!imagefill($imageResourceTo, 0, 0, $transparentColor)) {
                        throw new \InvalidArgumentException('Failed to fill image with transparency.');
                    }
                    imagecolortransparent($imageResourceTo, $transparentColor);
                    return $transparentColor;
                }
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            } catch (\Exception $e) {
                // fallback to default background color
            }
        }
        list($r, $g, $b) = $this->_backgroundColor;
        $color = imagecolorallocate($imageResourceTo, $r, $g, $b);
        if (!imagefill($imageResourceTo, 0, 0, $color)) {
            throw new \InvalidArgumentException("Failed to fill image background with color {$r} {$g} {$b}.");
        }

        return $color;
    }

    /**
     * Create watermark based on it's image position.
     *
     * @param resource $watermark
     * @param int $positionX
     * @param int $positionY
     * @param bool $merged
     * @param bool $tile
     * @return false|resource
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function createWatermarkBasedOnPosition(
        $watermark,
        int $positionX,
        int $positionY,
        bool $merged,
        bool $tile
    ) {
        if ($this->getWatermarkWidth() &&
            $this->getWatermarkHeight() &&
            $this->getWatermarkPosition() != self::POSITION_STRETCH
        ) {
            $watermark = $this->createWaterMark($watermark, $this->getWatermarkWidth(), $this->getWatermarkHeight());
        }

        if ($this->getWatermarkPosition() == self::POSITION_TILE) {
            $tile = true;
        } elseif ($this->getWatermarkPosition() == self::POSITION_STRETCH) {
            $watermark = $this->createWaterMark($watermark, $this->_imageSrcWidth, $this->_imageSrcHeight);
        } elseif ($this->getWatermarkPosition() == self::POSITION_CENTER) {
            $positionX = $this->_imageSrcWidth / 2 - imagesx($watermark) / 2;
            $positionY = $this->_imageSrcHeight / 2 - imagesy($watermark) / 2;
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        } elseif ($this->getWatermarkPosition() == self::POSITION_TOP_RIGHT) {
            $positionX = $this->_imageSrcWidth - imagesx($watermark);
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        } elseif ($this->getWatermarkPosition() == self::POSITION_TOP_LEFT) {
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        } elseif ($this->getWatermarkPosition() == self::POSITION_BOTTOM_RIGHT) {
            $positionX = $this->_imageSrcWidth - imagesx($watermark);
            $positionY = $this->_imageSrcHeight - imagesy($watermark);
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        } elseif ($this->getWatermarkPosition() == self::POSITION_BOTTOM_LEFT) {
            $positionY = $this->_imageSrcHeight - imagesy($watermark);
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        }

        if ($tile === false && $merged === false) {
            $this->imagecopymergeWithAlphaFix(
                $this->_imageHandler,
                $watermark,
                $positionX,
                $positionY,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark),
                $this->getWatermarkImageOpacity()
            );
        } else {
            $offsetX = $positionX;
            $offsetY = $positionY;
            while ($offsetY <= $this->_imageSrcHeight + imagesy($watermark)) {
                while ($offsetX <= $this->_imageSrcWidth + imagesx($watermark)) {
                    $this->imagecopymergeWithAlphaFix(
                        $this->_imageHandler,
                        $watermark,
                        $offsetX,
                        $offsetY,
                        0,
                        0,
                        imagesx($watermark),
                        imagesy($watermark),
                        $this->getWatermarkImageOpacity()
                    );
                    $offsetX += imagesx($watermark);
                }
                $offsetX = $positionX;
                $offsetY += imagesy($watermark);
            }
        }

        return $watermark;
    }

    /**
     * Create watermark.
     *
     * @param resource $watermark
     * @param string $width
     * @param string $height
     * @return false|resource
     */
    private function createWaterMark($watermark, string $width, string $height)
    {
        $newWatermark = imagecreatetruecolor($width, $height);
        imagealphablending($newWatermark, false);
        $col = imagecolorallocate($newWatermark, 255, 255, 255);
        imagecolortransparent($newWatermark, $col);
        imagefilledrectangle($newWatermark, 0, 0, $width, $height, $col);
        imagesavealpha($newWatermark, true);
        imagecopyresampled(
            $newWatermark,
            $watermark,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($watermark),
            imagesy($watermark)
        );

        return $newWatermark;
    }

    /**
     * Fix an issue with the usage of imagecopymerge where the alpha channel is lost
     *
     * @param resource $dst_im
     * @param resource $src_im
     * @param int $dst_x
     * @param int $dst_y
     * @param int $src_x
     * @param int $src_y
     * @param int $src_w
     * @param int $src_h
     * @param int $pct
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function imagecopymergeWithAlphaFix(
        $dst_im,
        $src_im,
        $dst_x,
        $dst_y,
        $src_x,
        $src_y,
        $src_w,
        $src_h,
        $pct
    ) {
        if ($pct >= 100) {
            return imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
        }

        if ($pct < 0) {
            return false;
        }

        $sizeX = imagesx($src_im);
        $sizeY = imagesy($src_im);
        if (false === $sizeX || false === $sizeY) {
            return false;
        }

        $tmpImg = imagecreatetruecolor($src_w, $src_h);
        if (false === $tmpImg) {
            return false;
        }

        if (false === imagealphablending($tmpImg, false)) {
            return false;
        }

        if (false === imagesavealpha($tmpImg, true)) {
            return false;
        }

        if (false === imagecopy($tmpImg, $src_im, 0, 0, 0, 0, $sizeX, $sizeY)) {
            return false;
        }

        $transparency = 127 - (($pct*127)/100);
        if (false === imagefilter($tmpImg, IMG_FILTER_COLORIZE, 0, 0, 0, $transparency)) {
            return false;
        }

        if (false === imagealphablending($dst_im, true)) {
            return false;
        }

        if (false === imagesavealpha($dst_im, true)) {
            return false;
        }

        $result = imagecopy($dst_im, $tmpImg, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
        imagedestroy($tmpImg);

        return $result;
    }
}
