<?php

namespace MagestyApps\WebImages\Plugin\Controller\Adminhtml\Wysiwyg\Images;

use Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\Thumbnail;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\Controller\Result\RawFactory;
use MagestyApps\WebImages\Helper\ImageHelper;

class ThumbnailPlugin
{
    /**
     * @var Images
     */
    private $wysiwygImages;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * ThumbnailPlugin constructor.
     * @param Images $wysiwygImages
     * @param RawFactory $resultRawFactory
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        Images $wysiwygImages,
        RawFactory $resultRawFactory,
        ImageHelper $imageHelper
    ) {
        $this->wysiwygImages = $wysiwygImages;
        $this->resultRawFactory = $resultRawFactory;
        $this->imageHelper = $imageHelper;
    }

    public function aroundExecute(Thumbnail $subject, callable $proceed)
    {
        $file = $subject->getRequest()->getParam('file');
        $file = $this->wysiwygImages->idDecode($file);
        $thumb = $subject->getStorage()->resizeOnTheFly($file);

        if ($this->imageHelper->isVectorImage($thumb)) {
            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setHeader('Content-Type', 'image/svg+xml');
            $resultRaw->setContents(file_get_contents($thumb));

            return $resultRaw;
        } else {
            return $proceed();
        }
    }
}
