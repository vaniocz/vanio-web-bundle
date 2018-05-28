<?php
namespace Vanio\WebBundle\Form;

use Fp\JsFormValidatorBundle\Factory\JsFormValidatorFactory as BaseJsFormValidatorFactory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
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

        if (!$parent = $form->getParent()) {
            return $validationData;
        }

        $parentConfig = $parent->getConfig();

        if (!$parentConfig->getOption('guess_constraints')) {
            return $validationData;
        } elseif (!$class = $parentConfig->getOption('class', $parentConfig->getDataClass())) {
            return $validationData;
        } elseif (!$constraints = $this->validationConstraintsGuesser->guessValidationConstraints($class)) {
            return $validationData;
        }

        $propertyPath = $parentConfig->getType()->getBlockPrefix() === 'scalar_object'
            ? null
            : $form->getPropertyPath();

        if (!$constraints = $constraints[(string) $propertyPath] ?? []) {
            return $validationData;
        }

        if (!$parent->isRequired()) {
            if (!$constraints = array_filter($constraints, [$this, 'isNotNotBlankConstraint'])) {
                return $validationData;
            }
        }

        $validationData['form']['constraints'] = $this->mergeValidationConstraints(
            $validationData['form']['constraints'] ?? [],
            $this->parseConstraints($constraints)
        );
        $validationData['form']['groups'] = $this->getValidationGroups($form);

        return $validationData;
    }

    /**
     * @param Constraint[] $constraints
     * @return mixed[]
     */
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

    /**
     * @param mixed[] $oldConstraints
     * @param mixed[] $newConstraints
     * @return mixed[]
     */
    private function mergeValidationConstraints(array $oldConstraints, array $newConstraints): array
    {
        foreach ($newConstraints as $class => $constraints) {
            $mergedConstraints = [];

            foreach ($constraints as $newConstraint) {
                foreach ($oldConstraints[$class] ?? [] as $oldConstraint) {
                    if ($oldConstraint->groups !== $newConstraint->groups) {
                        $mergedConstraints[] = $oldConstraint;
                    }
                }

                $mergedConstraints[] = $newConstraint;
            }

            $oldConstraints[$class] = $mergedConstraints;
        }

        return $oldConstraints;
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
