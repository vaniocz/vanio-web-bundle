<?php
namespace Vanio\WebBundle\Serializer;

use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface as JmsSerializerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

class Serializer
{
    public function __construct(
        SymfonySerializerInterface $symfonySerializer = null,
        JmsSerializerInterface $jmsSerializer = null
    ) {
        $this->symfonySerializer = $symfonySerializer;
        $this->jmsSerializer = $jmsSerializer;
    }

    public function serialize(array $data, string $format): string
    {
        if ($this->jmsSerializer) {
            try {
                return $this->jmsSerializer->serialize($data, $format);
            } catch (UnsupportedFormatException $e) {}
        }

        if ($this->symfonySerializer) {
            try {
                return $this->symfonySerializer->serialize($data, $format);
            } catch (NotEncodableValueException $e) {}
        }

        if ($format === 'json') {
            return json_encode($data);
        }

        throw new \LogicException(sprintf('Unable to serialize to format "%s".', $format));
    }
}
