<?php
/**
 * Copyright © 2021 MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Model\File;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\DriverPool;
use MagestyApps\WebImages\Helper\ImageHelper;

class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Uploader constructor.
     * @param ImageHelper $imageHelper
     * @param $fileId
     * @param Mime|null $fileMime
     * @param DirectoryList|null $directoryList
     * @param DriverPool|null $driverPool
     * @param TargetDirectory|null $targetDirectory
     * @param Filesystem|null $filesystem
     */
    public function __construct(
        ImageHelper $imageHelper,
        $fileId,
        Mime $fileMime = null,
        DirectoryList $directoryList = null,
        DriverPool $driverPool = null,
        TargetDirectory $targetDirectory = null,
        Filesystem $filesystem = null
    ) {
        parent::__construct(
            $fileId,
            $fileMime,
            $directoryList,
            $driverPool,
            $targetDirectory,
            $filesystem
        );

        $this->imageHelper = $imageHelper;
    }

    /**
     * Add web images to the list of allowed Mime-Types
     *
     * @param array $validTypes
     * @return bool
     */
    public function checkMimeType($validTypes = [])
    {
        foreach ($this->imageHelper->getVectorExtensions() as $extension) {
            $validTypes[] = 'image/' . $extension;
        }

        foreach ($this->imageHelper->getWebImageExtensions() as $extension) {
            $validTypes[] = 'image/' . $extension;
        }

        return parent::checkMimeType($validTypes);
    }

    /**
     * Add web images to the list of allowed extensions
     *
     * @param array $extensions
     * @return Uploader
     */
    public function setAllowedExtensions($extensions = [])
    {
        $extensions = array_merge(
            $extensions,
            $this->imageHelper->getVectorExtensions(),
            $this->imageHelper->getWebImageExtensions()
        );

        return parent::setAllowedExtensions($extensions);
    }
}
