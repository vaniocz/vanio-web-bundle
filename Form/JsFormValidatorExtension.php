<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsFormValidatorExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('guess_constraints', true)
            ->setAllowedTypes('guess_constraints', 'bool');
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
