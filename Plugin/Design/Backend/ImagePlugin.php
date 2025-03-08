<?php
/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Plugin\Design\Backend;

use Magento\Theme\Model\Design\Backend\Image;
use MagestyApps\WebImages\Helper\ImageHelper;

class ImagePlugin
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * ImagePlugin constructor.
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        ImageHelper $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Extend allowed extensions for theme files (logo, favicon, etc.)
     *
     * @param Image $subject
     * @param $extensions
     * @return array
     */
    public function afterGetAllowedExtensions(Image $subject, $extensions)
    {
        $extensions = array_merge(
            $extensions,
            array_values($this->imageHelper->getVectorExtensions()),
            array_values($this->imageHelper->getWebImageExtensions())
        );

        return $extensions;
    }
}
