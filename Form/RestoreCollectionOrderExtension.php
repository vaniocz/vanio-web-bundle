<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestoreCollectionOrderExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['restore_order']) {
            return;
        }

        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], -100)
            ->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit'], -100);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['restoreOrder'] = $options['restore_order'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('restore_order', false)
            ->setAllowedTypes('restore_order', 'bool');
    }

    public function getExtendedType(): string
    {
        return CollectionType::class;
    }

    /**
     * @internal
     * @throws TransformationFailedException
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data === null) {
            return;
        } elseif (!is_array($data)) {
            throw new TransformationFailedException('Invalid form data, array expected.');
        }

        foreach ($data as $name => $value) {
            if ($form->has($name)) {
                $child = $form->get($name);
                $form->remove($name);
                $form->add($child);
            }
        }
    }

    /**
     * @internal
     * @throws TransformationFailedException
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null) {
            return;
        } elseif (!is_array($data)) {
            throw new TransformationFailedException('Invalid form data, array expected.');
        }

        $orderedData = [];

        foreach ($event->getForm() as $name => $form) {
            $orderedData[$name] = $data[$name];
        }

        $event->setData($orderedData);
    }
}
