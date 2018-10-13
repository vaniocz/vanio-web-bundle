<?php
namespace Vanio\WebBundle\Form;

use Fp\JsFormValidatorBundle\Factory\JsFormValidatorFactory as BaseJsFormValidatorFactory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
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
        $constraints = $this->guessConstraints($form);
        $groups = $this->resolveValidationGroups($form);

        if ($this->shouldValidateRequired($form, $groups)) {
            $constraints[] = new NotBlank([
                'message' => $form->getConfig()->getOption('required_message'),
                'groups' => $groups,
            ]);
        }

        if (!$constraints) {
            return $validationData;
        }

        $validationData['form']['constraints'] = $this->mergeValidationConstraints(
            $validationData['form']['constraints'] ?? [],
            $this->parseConstraints($constraints)
        );
        $validationData['form']['groups'] = $this->getValidationGroups($form);

        return $validationData;
    }

    /**
     * @param FormInterface $form
     * @return Constraint[]
     */
    private function guessConstraints(FormInterface $form): array
    {
        if (!$parent = $form->getParent()) {
            return [];
        }

        $parentConfig = $parent->getConfig();

        if (!$parentConfig->getOption('guess_constraints')) {
            return [];
        } elseif (!$class = $parentConfig->getOption('class', $parentConfig->getDataClass())) {
            return [];
        }

        $propertyPath = $parentConfig->getType()->getBlockPrefix() === 'scalar_object'
            ? null
            : $form->getPropertyPath();
        $constraints = $this->validationConstraintsGuesser->guessValidationConstraints($class);
        $constraints = $constraints[(string) $propertyPath] ?? [];

        if (!$parent->isRequired()) {
            $constraints = array_filter($constraints, [$this, 'isNotNotBlankConstraint']);
        }

        return $constraints;
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

    /**
     * @return string[]
     */
    private function resolveValidationGroups(FormInterface $form): array
    {
        $resolveValidationGroups = function () use ($form) {
            return FormValidator::{'getValidationGroups'}($form);
        };
        $resolveValidationGroups = $resolveValidationGroups->bindTo(null, FormValidator::class);

        return $resolveValidationGroups();
    }

    private function shouldValidateRequired(FormInterface $form, array $groups): bool
    {
        if (!$form->isRequired() || !$this->resolveValidateRequired($form)) {
            return false;
        }

        $config = $form->getConfig();
        $parent = $form->getParent();
        $skippedParentTypes = ['choice', 'scalar_object', 'repeated'];

        if ($config->getCompound() && $config->getType()->getBlockPrefix() !== 'repeated') {
            return false;
        } elseif ($parent && in_array($parent->getConfig()->getType()->getBlockPrefix(), $skippedParentTypes)) {
            return false;
        } elseif ($this->hasNotBlankConstraint($config->getOption('constraints'), $groups)) {
            return false;
        }

        return !$parent || !$this->shouldValidateEmbedded($parent) || !$this->hasPropertyNotBlankConstraint(
            $parent->getConfig()->getDataClass(),
            $form->getPropertyPath(),
            $groups
        );
    }

    private function resolveValidateRequired(FormInterface $form): bool
    {
        do {
            $validateRequired = $form->getConfig()->getOption('validate_required');

            if ($validateRequired !== null) {
                return $validateRequired;
            }
        } while ($form = $form->getParent());

        return false;
    }

    private function hasNotBlankConstraint(array $constraints, array $groups): bool
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotBlank && array_intersect($groups, $constraint->groups)) {
                return true;
            }
        }

        return false;
    }

    private function shouldValidateEmbedded(FormInterface $form): bool
    {
        $dataClass = $form->getConfig()->getDataClass();

        return $dataClass && (
            !$form->getParent()
            || $this->hasValidConstraint($form->getConfig()->getOption('constraints'))
            || $this->hasClassValidConstraint($dataClass)
        );
    }

    private function hasValidConstraint(array $constraints): bool
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Valid) {
                return true;
            }
        }

        return false;
    }

    private function hasClassValidConstraint(string $dataClass): bool
    {
        if (!$this->validator->hasMetadataFor($dataClass)) {
            return false;
        }

        return $this->hasValidConstraint($this->getMetadataFor($dataClass)->getConstraints());
    }

    /**
     * @param string $dataClass
     * @param PropertyPathInterface $propertyPath
     * @param string[] $groups
     * @return bool
     */
    private function hasPropertyNotBlankConstraint(
        string $dataClass,
        PropertyPathInterface $propertyPath,
        array $groups
    ): bool {
        if (!$this->validator->hasMetadataFor($dataClass)) {
            return false;
        }

        $metadata = $this->getMetadataFor($dataClass);

        foreach ($propertyPath->getElements() as $element) {
            $metadata = $metadata->getPropertyMetadata($element);
        }

        foreach ($metadata as $propertyMetadata) {
            if ($this->hasNotBlankConstraint($propertyMetadata->getConstraints(), $groups)) {
                return true;
            }
        }

        return false;
    }
}
