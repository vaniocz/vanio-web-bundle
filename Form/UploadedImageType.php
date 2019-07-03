<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Model\Image;

class UploadedImageType extends AbstractType
{
    const DEFAULT_SUPPORTED_IMAGE_TYPES = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP];

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'class' => Image::class,
                'supported_image_types' => self::DEFAULT_SUPPORTED_IMAGE_TYPES,
                'required_message' => 'Choose an image.',
            ])
            ->setNormalizer('accept', $this->acceptNormalizer())
            ->setAllowedTypes('supported_image_types', ['array']);
    }

    public function getParent(): string
    {
        return UploadedFileType::class;
    }

    private function acceptNormalizer(): \Closure
    {
        return function (Options $options, string $accept = null) {
            if ($accept === null && $options['supported_image_types']) {
                $accept = implode(',', $this->resolveMimeTypes($options['supported_image_types']));
            }

            return $accept;
        };
    }

    private function resolveMimeTypes(array $imageTypes): array
    {
        $mimeTypes = [];

        foreach ($imageTypes as $imageType) {
            $mimeType = is_int($imageType) ? image_type_to_mime_type($imageType) : $imageType;
            $mimeTypes[$mimeType] = $mimeType;

            if ($mimeType === 'image/x-ms-bmp') {
                $mimeTypes['image/bmp'] = 'image/bmp';
            }
        }

        return $mimeTypes;
    }
}
