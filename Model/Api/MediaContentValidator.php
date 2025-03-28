<?php

declare(strict_types=1);

/**
 * Copyright © MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\Model\Api;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Api\ImageContentValidator;
use Magento\Framework\Api\ImageContentValidatorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

class MediaContentValidator implements ImageContentValidatorInterface
{
    public function __construct(
        private ImageContentValidator $imageContentValidator,
    ) {}

    public function isValid(ImageContentInterface $imageContent): bool
    {
        return $this->isSvgContent($imageContent) && $this->isAllowed($imageContent)
            ? $this->isValidSvg($imageContent)
            : $this->imageContentValidator->isValid($imageContent);
    }

    /**
     * Skip image size check in case of svg image, SVGs are not compatible with getimagesizefromstring
     * Use it at your own risk! This method does not sanitize the SVG content and malicious code can be pushed!
     *
     * @throws InputException
     * @see ImageContentValidator::isValid
     */
    private function isValidSvg(ImageContentInterface $imageContent): bool
    {
        $imageName = (string)$imageContent->getName();
        if ($imageName !== '' && preg_match('/^[^\\/?*:";<>()|{}\\\\]+$/', $imageName) === 1) {
            throw new InputException(new Phrase('Provided image name contains forbidden characters.'));
        }

        return true;
    }

    private function isSvgContent(ImageContentInterface $imageContent): bool
    {
        $fileContent = @base64_decode($imageContent->getBase64EncodedData(), true);

        return str_starts_with($fileContent, '<svg ') && str_ends_with($fileContent, '</svg>');
    }

    private function isAllowed(ImageContentInterface $imageContent): bool
    {
        return in_array($imageContent->getType(), ['svg-xml', 'svg'], true);
    }
}
