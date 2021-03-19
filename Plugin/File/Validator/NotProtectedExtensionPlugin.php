<?php

namespace MagestyApps\WebImages\Plugin\File\Validator;

use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

class NotProtectedExtensionPlugin
{
    public function afterGetProtectedFileExtensions(NotProtectedExtension $subject, $extensions)
    {
        if (isset($extensions['svg'])) {
            unset($extensions['svg']);
        }

        return $extensions;
    }
}
