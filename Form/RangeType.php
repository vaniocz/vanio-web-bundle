<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Form\ConstructorPropertyPathMapper;
use Vanio\DomainBundle\Model\Range;

class RangeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minimum', NumberType::class, ['data' => $options['minimum']])
            ->add('maximum', NumberType::class, ['data' => $options['maximum']]);

        if (is_a($options['data_class'], Range::class, true)) {
            $builder->setDataMapper(new ConstructorPropertyPathMapper);
        }
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param mixed[] $options
     */
    public  function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars += [
            'widget' => $options['widget'],
            'minimum' => $options['minimum'],
            'maximum' => $options['maximum'],
            'step' => $options['step'],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Range::class,
                'minimum' => 0,
                'maximum' => 9999,
                'step' => 1,
                'widget' => 'default',
            ])
            ->setAllowedTypes('minimum', ['int', 'float'])
            ->setAllowedTypes('maximum', ['int', 'float'])
            ->setAllowedTypes('step', ['int', 'float'])
            ->setAllowedValues('widget', ['default', 'slider']);
    }

    public function getBlockPrefix(): string
    {
        return 'vanio_range';
    }
}
