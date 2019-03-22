<?php
namespace Vanio\WebBundle\Translation;

class FlashMessage
{
    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';

    /** @var string */
    private $type;

    /** @var string */
    private $message;

    /** @var array */
    private $parameters;

    /** @var string|null */
    private $domain;

    /** @var string|null */
    private $locale;

    public function __construct(string $type, string $message, array $parameters = [], string $domain = null, string $locale = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->parameters = $parameters;
        $this->domain = $domain;
        $this->locale = $locale;
    }

    public static function success(string $message, array $parameters = [], string $domain = null, string $locale = null): self
    {
        return new self(self::TYPE_SUCCESS, $message, $parameters, $domain, $locale);
    }

    public static function info(string $message, array $parameters = [], string $domain = null, string $locale = null): self
    {
        return new self(self::TYPE_INFO, $message, $parameters, $domain, $locale);
    }

    public static function warning(string $message, array $parameters = [], string $domain = null, string $locale = null): self
    {
        return new self(self::TYPE_WARNING, $message, $parameters, $domain, $locale);
    }

    public static function danger(string $message, array $parameters = [], string $domain = null, string $locale = null): self
    {
        return new self(self::TYPE_DANGER, $message, $parameters, $domain, $locale);
    }

    public function type(): string
    {
        return $this->type;
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
