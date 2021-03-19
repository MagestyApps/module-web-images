<?php

namespace MagestyApps\WebImages\Plugin\Design\Backend;

use Magento\Theme\Model\Design\Backend\Logo;

class LogoPlugin
{
    public function afterGetAllowedExtensions(Logo $subject, $extensions)
    {
        $extensions[] = 'svg';

        return $extensions;
    }
}
