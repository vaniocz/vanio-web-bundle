<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Vanio\Stdlib\Uri;

class MultilingualListener implements EventSubscriberInterface
{
    const COOKIE_LOCALE_NAME = 'preferred_locale';
    const COOKIE_LOCALE_LIFETIME = '+1 year';

    /** @var array */
    private $supportedLocales;

    /** @var string[] */
    private $multilingualRootPaths;

    /** @var string[] */
    private $localePrefixes;

    /** @var Request */
    private $request;

    /** @var string|null */
    private $preferredLocale;

    /** @var string|null */
    private $requestLocale;

    /** @var string|null */
    private $browserLocale;

    /**
     * @param string[] $supportedLocales
     * @param string[] $multilingualRootPaths
     * @throws \InvalidArgumentException
     */
    public function __construct(array $supportedLocales, array $multilingualRootPaths, array $localePrefixes)
    {
        if (!$supportedLocales) {
            throw new \InvalidArgumentException('Supported locales must not be empty.');
        }

        $this->supportedLocales = $supportedLocales;
        $this->multilingualRootPaths = $multilingualRootPaths;
        $this->localePrefixes = $localePrefixes;
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->request = $event->getRequest();

        if ($this->multilingualRootRequested()) {
            $preferredLocale = $this->preferredLocale();
            $localePrefix = array_key_exists($preferredLocale, $this->localePrefixes)
                ? $this->localePrefixes[$preferredLocale]
                : $preferredLocale;

            if ($localePrefix) {
                $redirectPath = sprintf(
                    '%s%s/%s/',
                    $this->request->getBaseUrl(),
                    rtrim($this->request->getPathInfo(), '/'),
                    $localePrefix
                );
                $uri = (new Uri($this->request->getUri()))->withPath($redirectPath);
                $event->setResponse(new RedirectResponse((string)$uri));
            }
        }
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (
            $event->getRequestType() !== KernelInterface::MASTER_REQUEST
            || $this->requestLocale() === null && !$this->multilingualRootRequested()
        ) {
            return;
        }

        $properCookieLocale = $this->preferredLocale() === $this->browserLocale() ? null : $this->preferredLocale();

        if ($this->cookieLocale() !== $properCookieLocale) {
            $event->getResponse()->headers->setCookie($this->createLocaleCookie($properCookieLocale));
        }
    }

    private function multilingualRootRequested(): bool
    {
        $pathInfo = rtrim($this->request->getPathInfo(), '/');

        foreach ($this->multilingualRootPaths as $multilingualRootPath) {
            if (rtrim($multilingualRootPath, '/') === $pathInfo) {
                return true;
            }
        }

        return false;
    }

    private function preferredLocale(): string
    {
        if ($this->preferredLocale === null) {
            if ($this->requestLocale() !== null) {
                $this->preferredLocale = $this->requestLocale();
            } else {
                $this->preferredLocale = in_array($this->cookieLocale(), $this->supportedLocales, true)
                    ? $this->cookieLocale()
                    : $this->browserLocale();
            }
        }

        return $this->preferredLocale;
    }

    /**
     * @return string|null
     */
    private function requestLocale()
    {
        if ($this->requestLocale === null) {
            $requestLocale = $this->request->attributes->get('_locale');
            $this->requestLocale = in_array($requestLocale, $this->supportedLocales, true) ? $requestLocale : null;
        }

        return $this->requestLocale;
    }

    private function browserLocale(): string
    {
        if ($this->browserLocale === null) {
            $this->browserLocale = $this->request->getPreferredLanguage($this->supportedLocales);
        }

        return $this->browserLocale;
    }

    /**
     * @return string|null
     */
    private function cookieLocale()
    {
        return $this->request->cookies->get(self::COOKIE_LOCALE_NAME);
    }

    private function createLocaleCookie(string $locale = null): Cookie
    {
        return new Cookie(
            self::COOKIE_LOCALE_NAME,
            $locale,
            self::COOKIE_LOCALE_LIFETIME,
            $this->request->getBasePath() === '' ? '/' : $this->request->getBasePath()
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', PHP_INT_MAX],
            KernelEvents::RESPONSE => ['onResponse', PHP_INT_MIN],
        ];
    }
}
