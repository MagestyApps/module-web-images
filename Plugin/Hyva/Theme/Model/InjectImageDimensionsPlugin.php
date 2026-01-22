<?php
/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Plugin\Hyva\Theme\Model;

use Hyva\Theme\Model\InjectImageDimensions;
use MagestyApps\WebImages\Helper\ImageHelper;

class InjectImageDimensionsPlugin
{
    /**
     * @var ImageHelper
     */
    private ImageHelper $helper;

    /**
     * @param ImageHelper $helper
     */
    public function __construct(
        ImageHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Skip adding image dimensions for SVG images
     *
     * @param InjectImageDimensions $subject
     * @param callable $proceed
     * @param string $imgTag
     * @param string $src
     * @return string
     */
    public function aroundInjectNativeDimensionsToImgTag(InjectImageDimensions $subject, callable $proceed, string $imgTag, string $src): string
    {
        if ($this->helper->isVectorImage($src)) {
            return '';
        }

        return $proceed($imgTag, $src);
    }
}
