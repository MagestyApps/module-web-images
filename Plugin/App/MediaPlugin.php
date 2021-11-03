<?php

namespace MagestyApps\WebImages\Plugin\App;

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\App\Media;
use MagestyApps\WebImages\Helper\ImageHelper;
use Psr\Log\LoggerInterface;

class MediaPlugin
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var MediaConfig
     */
    private $imageConfig;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WriteInterface
     */
    private $directoryPub;

    /**
     * @var WriteInterface
     */
    private $directoryMedia;

    /**
     * MediaPlugin constructor.
     * @param ImageHelper $imageHelper
     * @param Filesystem $filesystem
     * @param MediaConfig $imageConfig
     * @param Http $request
     * @param LoggerInterface $logger
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ImageHelper $imageHelper,
        Filesystem $filesystem,
        MediaConfig $imageConfig,
        Http $request,
        LoggerInterface $logger
    ) {
        $this->imageHelper = $imageHelper;
        $this->imageConfig = $imageConfig;
        $this->request = $request;
        $this->logger = $logger;

        $this->directoryPub = $filesystem->getDirectoryWrite(
            DirectoryList::PUB,
            Filesystem\DriverPool::FILE
        );
        $this->directoryMedia = $filesystem->getDirectoryWrite(
            DirectoryList::MEDIA,
            Filesystem\DriverPool::FILE
        );
    }

    /**
     * When trying to process a vector image, just copy it to the cache folder instead of resizing
     *
     * @param Media $subject
     * @return array
     */
    public function beforeLaunch(Media $subject)
    {
        try {
            $relativeFileName = $this->getRelativeFileName();

            if ($this->imageHelper->isVectorImage($relativeFileName)) {
                $originalImage = $this->getOriginalImage($relativeFileName);
                $originalImagePath = $this->directoryMedia->getAbsolutePath(
                    $this->imageConfig->getMediaPath($originalImage)
                );

                $this->directoryMedia->copyFile(
                    $originalImagePath,
                    $this->directoryPub->getAbsolutePath($relativeFileName)
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Could not process vector image', [
                'message' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Get relative file name
     *
     * @return string
     */
    private function getRelativeFileName()
    {
        return str_replace('..', '', ltrim($this->request->getPathInfo(), '/'));
    }

    /**
     * Find the path to the original image of the cache path
     *
     * @param string $resizedImagePath
     * @return string
     */
    private function getOriginalImage(string $resizedImagePath): string
    {
        return preg_replace('|^.*?((?:/([^/])/([^/])/\2\3)?/?[^/]+$)|', '$1', $resizedImagePath);
    }
}
