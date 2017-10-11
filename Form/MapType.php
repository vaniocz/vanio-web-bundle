<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mapTransformer = new MapTransformer(
            $options['key_name'],
            $options['value_name'],
            $options['append_on_empty_key']
        );
        $builder
            ->addModelTransformer($mapTransformer)
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'], PHP_INT_MAX);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'entry_type' => MapEntryType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'key_type' => null,
                'key_name' => 'key',
                'key_options' => [],
                'value_type' => null,
                'value_name' => 'value',
                'value_options' => [],
                'append_on_empty_key' => false,
            ])
            ->setNormalizer('entry_options', $this->entryOptionsNormalizer());
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        $map = $event->getData();

        if ($map === null) {
            return;
        }

        $options = $event->getForm()->getConfig()->getOptions();
        $data = [];

        foreach ($map as $key => $value) {
            $data[] = [
                $options['key_name'] => $key,
                $options['value_name'] => $value,
            ];
        }

        $event->setData($data);
    }

    private function entryOptionsNormalizer(): \Closure
    {
        return function (Options $options, array $value) {
            $value['block_name'] = 'entry';

            return $value + [
                'key_type' => $options['key_type'],
                'key_name' => $options['key_name'],
                'key_options' => $options['key_options'],
                'value_type' => $options['value_type'],
                'value_name' => $options['value_name'],
                'value_options' => $options['value_options'],
            ];
        };
    }
}
