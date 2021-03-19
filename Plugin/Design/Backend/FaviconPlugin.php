<?php

namespace MagestyApps\WebImages\Plugin\Design\Backend;

use Magento\Theme\Model\Design\Backend\Favicon;

class FaviconPlugin
{
    public function afterGetAllowedExtensions(Favicon $subject, $extensions)
    {
        $extensions[] = 'svg';

        return $extensions;
    }
}
