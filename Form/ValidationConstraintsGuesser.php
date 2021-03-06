<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

class ValidationConstraintsGuesser
{
    /** @var ValidationParser */
    private $validationParser;

    /** @var array */
    private $defaultOptions;

    /** @var array */
    private $constraintMessageParameterMappings;

    /** @var array */
    private $constraintMappings = [
        'notBlank' => NotBlank::class,
        'same' => [IdenticalTo::class, ['value2' => 'value']],
        'maxLength' => [Length::class, ['maxLength' => 'max', 'message' => 'maxMessage'], ['max_length' => 'limit']],
        'min' => [GreaterThanOrEqual::class, ['minValue' => 'value'], ['min' => 'compared_value']],
        'max' => [LessThanOrEqual::class, ['maxValue' => 'value'], ['max' => 'compared_value']],
        'greaterThan' => [GreaterThan::class, ['limit' => 'value']],
        'lessThan' => [LessThan::class, ['limit' => 'value']],
        'email' => [Email::class],
        'url' => [Url::class],
        'supportedImageFile' => [Image::class, ['message' => 'mimeTypesMessage']],
        'regex' => [Regex::class, ['pattern' => 'pattern']],
    ];

    public function __construct(
        ValidationParser $validationParser,
        array $constraintMappings = [],
        array $defaultOptions = ['groups' => 'form']
    ) {
        $this->validationParser = $validationParser;
        $this->constraintMappings = $constraintMappings + $this->constraintMappings;
        $this->defaultOptions = $defaultOptions;

        foreach ($this->constraintMappings as $method => $constraintMapping) {
            if (is_array($constraintMapping) && isset($constraintMapping[2])) {
                $this->constraintMessageParameterMappings[$constraintMapping[0]] = $constraintMapping[2];
            }
        }
    }

    public function guessValidationConstraints(string $class): array
    {
        if (!$validationRules = $this->validationParser->parseValidationRules($class)) {
            return [];
        }

        $constraints = [];

        foreach ($validationRules as $validationRule) {
            if ($constraintMapping = $this->constraintMappings[$validationRule['_method']] ?? null) {
                $constraint = $this->createValidationConstraint($validationRule, (array) $constraintMapping);
                $constraints[$validationRule['_propertyPath'] ?? null][] = $constraint;
            }
        }

        return $constraints;
    }

    /**
     * @param Constraint|string $constraint
     * @return array
     */
    public function getConstraintMessageParameterMappings($constraint): array
    {
        $constraint = is_object($constraint) ? get_class($constraint) : $constraint;

        return $this->constraintMessageParameterMappings[$constraint] ?? [];
    }

    private function createValidationConstraint(array $validationRule, array $constraintMapping): Constraint
    {
        list($constraintClass, $optionsMapping) = $constraintMapping + [null, []];
        $optionsMapping += ['message' => 'message'];
        $options = $this->defaultOptions;

        foreach ($optionsMapping as $from => $to) {
            $options[$to] = $validationRule[$from] ?? null;
        }

        return new $constraintClass($options);
    }
}
