<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class FormToggleDataMapper implements DataMapperInterface
{
    public function __construct(DataMapperInterface $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param mixed $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        if ($data === null) {
            foreach ($forms as $form) {
                $form->setData(null);
            }
        } else {
            $this->dataMapper->mapDataToForms($data, $forms);
        }
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $children = iterator_to_array($forms);
        /** @var FormInterface $form */
        $child = reset($children);
        $parent = $child->getParent()->getParent();
        $toggleForm = $parent->get($parent->getConfig()->getOption('toggle_name'));
        $disabledData = $parent->getConfig()->getOption('disabled_data');

        if ($toggleForm->getData()) {
            $this->dataMapper->mapFormsToData($forms, $data);
        } else {
            $data = $disabledData instanceof \Closure ? $disabledData($parent) : $disabledData;
        }
    }
}
