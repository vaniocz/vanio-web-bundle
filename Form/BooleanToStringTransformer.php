<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BooleanToStringTransformer implements DataTransformerInterface
{
    /** @var string */
    private $trueValue;

    /** @var mixed[] */
    private $falseValues;

    public function __construct(string $trueValue, array $falseValues = ['', '0'])
    {
        $this->trueValue = $trueValue;
        $this->falseValues = $falseValues;

        if (in_array($this->trueValue, $this->falseValues, true)) {
            throw new InvalidArgumentException('The specified "true" value is contained in the false-values.');
        }
    }

    /**
     * @param bool|null $value
     * @return string|null
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        } elseif (!is_bool($value)) {
            throw new TransformationFailedException('Expected a Boolean.');
        }

        return $value ? $this->trueValue : null;
    }

    /**
     * @param mixed $value
     * @return bool|null
     */
    public function reverseTransform($value)
    {
        return $value === null ? null : !in_array($value, $this->falseValues, true);
    }
}
