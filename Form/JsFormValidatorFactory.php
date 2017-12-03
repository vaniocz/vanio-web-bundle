<?php
namespace Vanio\WebBundle\Form;

use Fp\JsFormValidatorBundle\Factory\JsFormValidatorFactory as BaseJsFormValidatorFactory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DoctrineGenericTypes\Bundle\Form\ScalarObjectType;
use Vanio\Stdlib\Strings;

class JsFormValidatorFactory extends BaseJsFormValidatorFactory
{
    /** @var ValidationConstraintsGuesser|null */
    private $validationConstraintsGuesser;

    /** @var \SplObjectStorage|null */
    private $translatedConstraints;

    public function setValidationsConstraintsGuesser(ValidationConstraintsGuesser $validationConstraintsGuesser)
    {
        $this->validationConstraintsGuesser = $validationConstraintsGuesser;
    }

    /**
     * @internal
     */
    public function isNotNotBlankConstraint(Constraint $constraint): bool
    {
        return !$constraint instanceof NotBlank;
    }

    protected function getValidationData(Form $form): array
    {
        $validationData = parent::getValidationData($form);
        $parent = $form->getParent();

        if (!$parent || !$parent->getConfig()->getOption('guess_constraints')) {
            return $validationData;
        } elseif (!$class = $parent->getConfig()->getOption('class', $parent->getConfig()->getDataClass())) {
            return $validationData;
        } elseif (!$constraints = $this->validationConstraintsGuesser->guessValidationConstraints($class)) {
            return $validationData;
        }

        if (is_a($parent->getConfig()->getType()->getInnerType(), ScalarObjectType::class)) {
            $constraints = array_merge(...array_values($constraints));
        } elseif (!$constraints = $constraints[(string) $form->getPropertyPath()] ?? []) {
            return $validationData;
        }

        if (!$parent->isRequired()) {
            $constraints = array_filter($constraints, [$this, 'isNotNotBlankConstraint']);
        }

        if ($constraints) {
            $this->composeValidationData($validationData['form'], $constraints, []);
            $validationData['form']['groups'] = $this->getValidationGroups($form);
        }

        return $validationData;
    }

    protected function parseConstraints(array $constraints): array
    {
        if ($this->translatedConstraints === null) {
            $this->translatedConstraints = new \SplObjectStorage;
        }

        $data = [];

        foreach ($constraints as $constraint) {
            if ($constraint instanceof UniqueEntity) {
                continue;
            } elseif (!isset($this->translatedConstraints[$constraint])) {
                foreach ($constraint as $property => &$value) {
                    if (Strings::contains(strtolower($property), 'message')) {
                        $value = $this->replaceConstraintMessageParameters($constraint, $this->translateMessage($value));
                    }
                }

                $this->translatedConstraints[$constraint] = true;
            }

            $data[get_class($constraint)][] = $constraint;
        }

        return $data;
    }

    private function replaceConstraintMessageParameters(Constraint $constraint, string $message): string
    {
        if (!$mappings = $this->validationConstraintsGuesser->getConstraintMessageParameterMappings($constraint)) {
            return $message;
        }

        $messageParameters = [];

        foreach ($mappings as $from => $to) {
            $messageParameters["{{ $from }}"] = "{{ $to }}";
        }

        return strtr($message, $messageParameters);
    }
}
