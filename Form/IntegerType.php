<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerType extends AbstractType implements DataTransformerInterface
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('compound', false);
    }

    /**
     * @param int|float|string|null $value
     * @return string
     */
    public function transform($value): string
    {
        if ($value === null) {
            return '';
        } else if (!is_int($value) && !ctype_digit($value)) {
            throw new TransformationFailedException('Expected an integer or a string of numeric characters only.');
        }

        return (string) $value;
    }

    /**
     * @param string $value
     * @return int|null
     */
    public function reverseTransform($value): ?int
    {
        if ($value === '') {
            return null;
        } elseif (!ctype_digit($value)) {
            throw new TransformationFailedException('Expected a string of numeric characters only.');
        }

        return (int) $value;
    }
}
