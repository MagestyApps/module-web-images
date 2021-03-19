<?php

namespace MagestyApps\WebImages\Plugin\Controller\Adminhtml\Wysiwyg;

use Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive;
use Magento\Cms\Model\Template\Filter;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\Framework\Url\DecoderInterface;use MagestyApps\WebImages\Helper\ImageHelper;

class DirectivePlugin
{
    /**
     * @var DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * DirectivePlugin constructor.
     * @param DecoderInterface $urlDecoder
     * @param Filter $filter
     * @param RawFactory $resultRawFactory
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        DecoderInterface $urlDecoder,
        Filter $filter,
        RawFactory $resultRawFactory,
        ImageHelper $imageHelper
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->filter = $filter;
        $this->resultRawFactory = $resultRawFactory;
        $this->imageHelper = $imageHelper;
    }

    public function aroundExecute(Directive $subject, callable $proceed)
    {
        $directive = $subject->getRequest()->getParam('___directive');
        $directive = $this->urlDecoder->decode($directive);

        try {
            $imagePath = $this->filter->filter($directive);

            if ($this->imageHelper->isVectorImage($imagePath)) {
                $resultRaw = $this->resultRawFactory->create();
                $resultRaw->setHeader('Content-Type', 'image/svg+xml');
                $resultRaw->setContents(file_get_contents($imagePath));

                return $resultRaw;
            }

            return $proceed();
        } catch (\Exception $e) {
            return $proceed();
        }
    }
}
