<?php
/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Plugin\Wysiwyg\Images;

use Magento\Cms\Model\Wysiwyg\Images\Storage;
use MagestyApps\WebImages\Helper\ImageHelper;

class StoragePlugin
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * StoragePlugin constructor.
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        ImageHelper $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Skip resizing vector images
     *
     * @param Storage $storage
     * @param callable $proceed
     * @param $source
     * @param bool $keepRatio
     * @return mixed
     */
    public function aroundResizeFile(Storage $storage, callable $proceed, $source, $keepRatio = true)
    {
        if ($this->imageHelper->isVectorImage($source)) {
            return $source;
        }

        return $proceed($source, $keepRatio);
    }

    /**
     * Return original file path as thumbnail for vector images
     *
     * @param Storage $storage
     * @param callable $proceed
     * @param $filePath
     * @param false $checkFile
     */
    public function aroundGetThumbnailPath(Storage $storage, callable $proceed, $filePath, $checkFile = false)
    {
        if ($this->imageHelper->isVectorImage($filePath)) {
            return $filePath;
        }

        return $proceed($filePath, $checkFile);
    }
}
