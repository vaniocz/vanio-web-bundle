<?php
namespace Vanio\WebBundle\Serializer;

class UnsupportedFormatException extends \Exception
{
    public static function create(string $format, \Throwable $previous = null): self
    {
        return new self(sprintf('Unable to serialize into unsupported format "%s".', $format, $previous));
    }
}
