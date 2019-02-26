<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;

class IntegerType extends AbstractType implements DataTransformerInterface
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer($this);
    }

    /**
     * @param int|float|string|null $value
     * @return string
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        } else if (!is_int($value) && !ctype_digit($value)) {
            throw new TransformationFailedException('Expected an integer or a string of numeric characters only.');
        }

        return $value;
    }
    
    /**
     * @param string $value
     * @return int|null
     */
    public function reverseTransform(string $value): ?int
    {
        if ('' === $value) {
            return;
        } elseif (!ctype_digit($value)) {
            throw new TransformationFailedException('Expected a string of numeric characters only.');
        }
 
        return (int) $value;
    }
}
