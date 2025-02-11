<?php

namespace MagestyApps\WebImages\Plugin\Product\Gallery;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use MagestyApps\WebImages\Helper\ImageHelper;

class ProcessorPlugin
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var Config
     */
    private $mediaConfig;

    /**
     * @var Database
     */
    private $fileStorageDb;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var \Magento\Framework\File\Mime
     */
    private $mime;

    /**
     * @var \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private $attribute;

    public function __construct(
        ImageHelper $imageHelper,
        Filesystem $filesystem,
        Config $mediaConfig,
        Database $fileStorageDb,
        ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\File\Mime $mime
    ) {
       $this->imageHelper = $imageHelper;
       $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
       $this->mediaConfig = $mediaConfig;
       $this->fileStorageDb = $fileStorageDb;
       $this->attributeRepository = $attributeRepository;
       $this->mime = $mime;
    }

    /**
     * We have to duplicate so many code lines because Magento hardcoded allowed image extensions
     *
     * @param Product\Gallery\Processor $subject
     * @param callable $proceed
     * @param Product $product
     * @param $file
     * @param $mediaAttribute
     * @param $move
     * @param $exclude
     * @return string
     */
    public function aroundAddImage(
        Product\Gallery\Processor $subject,
        callable $proceed,
        Product $product,
        $file,
        $mediaAttribute = null,
        $move = false,
        $exclude = true
    ) {
        $pathinfo = pathinfo($file);
        if (!in_array($pathinfo['extension'], $this->imageHelper->getWebImageExtensions())) {
            return $proceed($product, $file, $mediaAttribute, $move, $exclude);
        }

        $file = $this->mediaDirectory->getRelativePath($file);
        if (!$this->mediaDirectory->isFile($file)) {
            throw new LocalizedException(__("The image doesn't exist."));
        }

        $fileName = \Magento\MediaStorage\Model\File\Uploader::getCorrectFileName($pathinfo['basename']);
        $dispersionPath = \Magento\MediaStorage\Model\File\Uploader::getDispersionPath($fileName);
        $fileName = $dispersionPath . '/' . $fileName;

        $fileName = $this->getNotDuplicatedFilename($fileName, $dispersionPath);

        $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);

        try {
            /** @var $storageHelper \Magento\MediaStorage\Helper\File\Storage\Database */
            $storageHelper = $this->fileStorageDb;
            if ($move) {
                $this->mediaDirectory->renameFile($file, $destinationFile);

                //If this is used, filesystem should be configured properly
                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            } else {
                $this->mediaDirectory->copyFile($file, $destinationFile);

                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('The "%1" file couldn\'t be moved.', $e->getMessage()));
        }

        $fileName = str_replace('\\', '/', $fileName);

        $attrCode = $this->getAttribute()->getAttributeCode();
        $mediaGalleryData = $product->getData($attrCode);
        $position = 0;

        $absoluteFilePath = $this->mediaDirectory->getAbsolutePath($destinationFile);
        $imageMimeType = $this->mime->getMimeType($absoluteFilePath);
        $imageContent = $this->mediaDirectory->readFile($absoluteFilePath);
        $imageBase64 = base64_encode($imageContent);
        $imageName = $pathinfo['filename'];

        if (!is_array($mediaGalleryData)) {
            $mediaGalleryData = ['images' => []];
        }

        foreach ($mediaGalleryData['images'] as &$image) {
            if (isset($image['position']) && $image['position'] > $position) {
                $position = $image['position'];
            }
        }

        $position++;
        $mediaGalleryData['images'][] = [
            'file' => $fileName,
            'position' => $position,
            'label' => '',
            'disabled' => (int)$exclude,
            'media_type' => 'image',
            'types' => $mediaAttribute,
            'content' => [
                'data' => [
                    ImageContentInterface::NAME => $imageName,
                    ImageContentInterface::BASE64_ENCODED_DATA => $imageBase64,
                    ImageContentInterface::TYPE => $imageMimeType,
                ]
            ]
        ];

        $product->setData($attrCode, $mediaGalleryData);

        if ($mediaAttribute !== null) {
            $this->setMediaAttribute($product, $mediaAttribute, $fileName);
        }

        return $fileName;
    }

    /**
     * Get filename which is not duplicated with other files in media temporary and media directories
     *
     * @param string $fileName
     * @param string $dispersionPath
     * @return string
     * @since 101.0.0
     */
    protected function getNotDuplicatedFilename($fileName, $dispersionPath)
    {
        $fileMediaName = $dispersionPath . '/'
            . \Magento\MediaStorage\Model\File\Uploader::getNewFileName($this->mediaConfig->getMediaPath($fileName));
        $fileTmpMediaName = $dispersionPath . '/'
            . \Magento\MediaStorage\Model\File\Uploader::getNewFileName($this->mediaConfig->getTmpMediaPath($fileName));

        if ($fileMediaName != $fileTmpMediaName) {
            if ($fileMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileMediaName,
                    $dispersionPath
                );
            } elseif ($fileTmpMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileTmpMediaName,
                    $dispersionPath
                );
            }
        }

        return $fileMediaName;
    }

    /**
     * Return media_gallery attribute
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     * @since 101.0.0
     */
    public function getAttribute()
    {
        if (!$this->attribute) {
            $this->attribute = $this->attributeRepository->get('media_gallery');
        }

        return $this->attribute;
    }

    /**
     * Set media attribute value
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string|string[] $mediaAttribute
     * @param string $value
     * @return $this
     * @since 101.0.0
     */
    public function setMediaAttribute(\Magento\Catalog\Model\Product $product, $mediaAttribute, $value)
    {
        $mediaAttributeCodes = $this->mediaConfig->getMediaAttributeCodes();

        if (is_array($mediaAttribute)) {
            foreach ($mediaAttribute as $attribute) {
                if (in_array($attribute, $mediaAttributeCodes)) {
                    $product->setData($attribute, $value);
                }
            }
        } elseif (in_array($mediaAttribute, $mediaAttributeCodes)) {
            $product->setData($mediaAttribute, $value);
        }

        return $this;
    }
}
