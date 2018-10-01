<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class JsonType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function transform($value)
    {
        return json_encode($value);
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        return json_decode($value, true);
    }
}
