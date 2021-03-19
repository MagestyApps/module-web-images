<?php
/**
 * Copyright © 2021 MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class ImageHelper extends AbstractHelper
{
    const XML_PATH_VECTOR_EXTENSIONS = 'magestyapps_webimages/extensions/vector';

    /**
     * Check if the file is a vector image
     *
     * @param $file
     * @return bool
     */
    public function isVectorImage($file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, $this->getVectorExtensions());
    }

    /**
     * et vector images extensions
     *
     * @return array
     */
    public function getVectorExtensions()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VECTOR_EXTENSIONS, 'store') ?: [];
    }
}
