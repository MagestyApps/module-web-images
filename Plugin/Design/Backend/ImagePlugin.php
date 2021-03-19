<?php

namespace MagestyApps\WebImages\Plugin\Design\Backend;

use Magento\Theme\Model\Design\Backend\Image;

class ImagePlugin
{
    public function afterGetAllowedExtensions(Image $subject, $extensions)
    {
        $extensions[] = 'svg';

        return $extensions;
    }
}
