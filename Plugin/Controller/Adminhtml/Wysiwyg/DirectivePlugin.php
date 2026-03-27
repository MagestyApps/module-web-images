<?php
/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Plugin\Controller\Adminhtml\Wysiwyg;

use Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive;
use Magento\Cms\Model\Template\Filter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryResolver;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Url\DecoderInterface;
use MagestyApps\WebImages\Helper\ImageHelper;

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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryResolver
     */
    private $directoryResolver;

    /**
     * DirectivePlugin constructor.
     * @param DecoderInterface $urlDecoder
     * @param Filter $filter
     * @param RawFactory $resultRawFactory
     * @param ImageHelper $imageHelper
     * @param Filesystem $filesystem
     * @param DirectoryResolver $directoryResolver
     */
    public function __construct(
        DecoderInterface $urlDecoder,
        Filter $filter,
        RawFactory $resultRawFactory,
        ImageHelper $imageHelper,
        Filesystem $filesystem,
        DirectoryResolver $directoryResolver
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->filter = $filter;
        $this->resultRawFactory = $resultRawFactory;
        $this->imageHelper = $imageHelper;
        $this->filesystem = $filesystem;
        $this->directoryResolver = $directoryResolver;
    }

    /**
     * Handle vector images for media storage thumbnails
     *
     * @param Directive $subject
     * @param callable $proceed
     * @return Raw
     */
    public function aroundExecute(Directive $subject, callable $proceed)
    {
        try {
            $directive = $subject->getRequest()->getParam('___directive');
            $directive = $this->urlDecoder->decode($directive);
            $imagePath = $this->filter->filter($directive);
            $imagePath = str_replace('\\', '/', $imagePath);

            if (!$this->imageHelper->isVectorImage($imagePath)) {
                throw new LocalizedException(__('This is not a vector image'));
            }

            $urlPath = $this->filesystem->getUri(DirectoryList::MEDIA);
            $relativeFilePath = str_replace(rtrim($urlPath, '/') . '/', '', $imagePath);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $absolutePath = $mediaDirectory->getAbsolutePath($relativeFilePath);

            if (!$this->directoryResolver->validatePath($absolutePath, DirectoryList::MEDIA)) {
                throw new LocalizedException(__('Invalid Path'));
            }

            /** @var Raw $resultRaw */
            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setHeader('Content-Type', 'image/svg+xml');
            $resultRaw->setContents($mediaDirectory->readFile($relativeFilePath));

            return $resultRaw;
        } catch (\Exception $e) {
            return $proceed();
        }
    }
}