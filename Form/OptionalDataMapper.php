<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class OptionalDataMapper implements DataMapperInterface
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

        $forms = iterator_to_array($forms);
        $forms['_optional_toggle']->setData($data !== null);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        if (iterator_to_array($forms)['_optional_toggle']->getData()) {
            $this->dataMapper->mapFormsToData($forms, $data);
        } else {
            $data = null;
        }
    }
}
