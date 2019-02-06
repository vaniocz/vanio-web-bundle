<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreserveMissingDataExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['preserve_missing_data']) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 100);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('preserve_missing_data', false)
            ->setAllowedTypes('preserve_missing_data', 'bool');
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $event->setData($this->resolveSubmittedData($event->getForm(), $event->getData()));
    }

    /**
     * @param FormInterface $form
     * @return mixed[]|string
     */
    private function resolveSubmittedData(FormInterface $form, $data = null)
    {
        if ($form->count()) {
            if (!is_array($data)) {
                $data = [];
            }

            foreach ($form->all() as $name => $child) {
                if (
                    !isset($data[$name])
                    && !$child->getConfig()->getType()->getInnerType() instanceof CheckboxType
                    && !$child instanceof ClickableInterface
                ) {
                    $data[$name] = $this->resolveSubmittedData($child);
                }
            }

            return $data;
        } elseif ($data === null) {
            $data = $form->getViewData();
        }

        if ($data === null && $form->getConfig()->getCompound()) {
            return null;
        }

        return is_array($data) ? $data : (string) $data;
    }
}
