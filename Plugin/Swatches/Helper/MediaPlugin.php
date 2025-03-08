<?php
/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Plugin\Swatches\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Swatches\Helper\Media;
use MagestyApps\WebImages\Helper\ImageHelper;

class MediaPlugin
{
    /**
     * @var ImageHelper
     */
    private $helper;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var Media
     */
    private $subject;

    /**
     * @var array
     */
    private $swatchImageTypes = ['swatch_image', 'swatch_thumb'];

    /**
     * @param ImageHelper $helper
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ImageHelper $helper,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->helper = $helper;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Skip resizing SVG images for swatches. Instead, just copy the image to the size folders.
     *
     * @param Media $subject
     * @param callable $proceed
     * @param $imageUrl
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function aroundGenerateSwatchVariations(Media $subject, callable $proceed, $imageUrl)
    {
        if ($this->helper->isVectorImage($imageUrl)) {
            $this->subject = $subject;

            $absoluteImagePath = $this->getOriginalFilePath($imageUrl);
            foreach ($this->swatchImageTypes as $swatchType) {
                $imageConfig = $this->subject->getImageConfig();
                $swatchNamePath = $this->generateNamePath($imageConfig, $imageUrl, $swatchType);

                $this->mediaDirectory->copyFile($absoluteImagePath, $swatchNamePath['path_for_save'] . '/' . $swatchNamePath['name']);
            }
        } else {
            return $proceed($imageUrl);
        }
    }

    /**
     * @param string $file
     * @return string
     */
    private function getOriginalFilePath($file)
    {
        return $this->mediaDirectory->getAbsolutePath($this->subject->getAttributeSwatchPath($file));
    }

    /**
     * Generate swatch path and name for saving
     *
     * @param array $imageConfig
     * @param string $imageUrl
     * @param string $swatchType
     * @return array
     */
    protected function generateNamePath($imageConfig, $imageUrl, $swatchType)
    {
        $fileName = $this->prepareFileName($imageUrl);
        $absolutePath = $this->mediaDirectory->getAbsolutePath($this->subject->getSwatchCachePath($swatchType));
        return [
            'path_for_save' => $absolutePath . $this->subject->getFolderNameSize($swatchType, $imageConfig) . $fileName['path'],
            'name' => $fileName['name']
        ];
    }

    /**
     * Image url /m/a/magento.png return ['name' => 'magento.png', 'path => '/m/a']
     *
     * @param string $imageUrl
     * @return array
     */
    protected function prepareFileName($imageUrl)
    {
        $fileArray = explode('/', $imageUrl);
        $fileName = array_pop($fileArray);
        $filePath = implode('/', $fileArray);
        return ['name' => $fileName, 'path' => $filePath];
    }
}
