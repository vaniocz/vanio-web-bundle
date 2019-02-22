<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MapTransformer implements DataTransformerInterface
{
    /** @var string */
    private $keyName;

    /** @var string */
    private $valueName;

    /** @var bool */
    private $appendOnEmptyKey;

    public function __construct(string $keyName, string $valueName, bool $appendOnEmptyKey)
    {
        $this->keyName = $keyName;
        $this->valueName = $valueName;
        $this->appendOnEmptyKey = $appendOnEmptyKey;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function transform($value): array
    {
        return $value ?? [];
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function reverseTransform($value): array
    {
        $map = [];

        foreach ($value as $data) {
            if (!array_key_exists($this->keyName, $data) || !array_key_exists($this->valueName, $data)) {
                throw new TransformationFailedException('Invalid data structure.');
            } elseif (array_key_exists((string) $data[$this->keyName], $map)) {
                throw new TransformationFailedException('Duplicate key detected.');
            }

            if ($this->appendOnEmptyKey && $data[$this->keyName] === null) {
                $map[] = $data[$this->valueName];
            } else {
                $map[(string) $data[$this->keyName]] = $data[$this->valueName];
            }
        }

        return $map;
    }
}
