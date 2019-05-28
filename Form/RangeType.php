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
            ->add('minimum', NumberType::class)
            ->add('maximum', NumberType::class);
        $emptyData = $builder->getEmptyData();

        if ($emptyData instanceof Range) {
            $builder->get('minimum')->setEmptyData($this->normToView($builder->get('minimum'), $emptyData->minimum()));
            $builder->get('maximum')->setEmptyData($this->normToView($builder->get('maximum'), $emptyData->maximum()));
        }

        if (is_a($options['data_class'], Range::class, true)) {
            $builder->setDataMapper(ConstructorPropertyPathMapper::nullable());
        }
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param mixed[] $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars += [
            'widget' => $options['widget'],
            'minimum' => $options['minimum'] ?? 0,
            'maximum' => $options['maximum'] ?? 9999,
            'step' => $options['step'],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Range::class,
                'empty_data' => null,
                'minimum' => null,
                'maximum' => null,
                'step' => 1,
                'widget' => 'range',
            ])
            ->setAllowedTypes('minimum', ['int', 'float', 'null'])
            ->setAllowedTypes('maximum', ['int', 'float', 'null'])
            ->setAllowedTypes('step', ['int', 'float'])
            ->setAllowedValues('widget', ['range', 'slider']);
    }

    public function getBlockPrefix(): string
    {
        return 'vanio_range';
    }

    private function normToView(FormBuilderInterface $form, float $value): string
    {
        foreach ($form->getViewTransformers() as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }
}
