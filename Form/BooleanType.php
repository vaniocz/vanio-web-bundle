<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Distinguishes between false, true and null.
 */
class BooleanType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new BooleanToStringTransformer($options['value']));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = [
            'value' => $options['value'],
            'checked' => $form->getViewData() !== null,
            'expanded' => $options['expanded'],
        ] + $view->vars;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'value' => '1',
                'compound' => false,
                'expanded' => false,
                'false_values' => ['', '0'],
                'empty_data' => function (FormInterface $form, $data) {
                    return $data;
                },
            ])
            ->setAllowedTypes('false_values', 'array')
            ->setAllowedTypes('expanded', 'bool');
    }
}
