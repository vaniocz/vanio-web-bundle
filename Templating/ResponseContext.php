<?php
namespace Vanio\WebBundle\Templating;

class ResponseContext
{
    /** @var int|null */
    private $statusCode;

    /** @var string|null */
    private $statusText;

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function statusText(): ?string
    {
        return $this->statusText;
    }

    public function setStatus(int $statusCode = null, string $statusText = null)
    {
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
    }

    public function clear()
    {
        $this->statusCode = null;
        $this->statusText = null;
    }
}
