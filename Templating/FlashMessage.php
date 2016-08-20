<?php
namespace Vanio\WebBundle\Templating;

class FlashMessage
{
    /** @var string */
    private $message;

    /** @var array */
    private $parameters;

    /** @var string|null */
    private $domain;

    /** @var string|null */
    private $locale;

    public function __construct(string $message, array $parameters = [], string $domain = null, string $locale = null)
    {
        $this->message = $message;
        $this->parameters = $parameters;
        $this->domain = $domain;
        $this->locale = $locale;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return string|null
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * @return string|null
     */
    public function locale()
    {
        return $this->locale;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
