# [<img alt="Vanio" src="http://www.vanio.cz/img/vanio-logo.png" width="130" align="top">](http://www.vanio.cz) Web Bundle

[![Build Status](https://travis-ci.org/vaniocz/vanio-web-bundle.svg?branch=master)](https://travis-ci.org/vaniocz/vanio-web-bundle)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/vanio-web-bundle/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/vanio-web-bundle?branch=master)
![PHP7](https://img.shields.io/badge/php-7-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/vanio-web-bundle/license)](https://github.com/vaniocz/vanio-web-bundle/blob/master/LICENSE)

**WORK IN PROGRESS - DO NOT USE**

# Installation
Installation can be done as usually using composer.
`composer require vanio/vanio-web-bundle`

Next step is to register this bundle as well as bundles it depends on inside your `AppKernel`.
```php
// app/AppKernel.php
// ...

class AppKernel extends Kernel
{
    // ...

    public function registerBundles(): array
    {
        $bundles = [
            // ...
            new Vanio\UserBundle\VanioWebBundle,
        ];

        // ...
    }
}
```
# Features

## Redirecting to Referer

It's quite a common task to redirect user back after certain actions.
This bundle defines a service named `vanio_web.request.referer_resolver` which helps you with that.
It reads HTTP_REFERER header from current request and tries to match the referring URL against defined routes.
In case of missing header or invalid URL (like URL pointing to a different webpage) fallback path is used.
Since this functionality is mostly used from inside controllers, it is possible to use `Vanio\WebBundle\Request\RefererHelperTrait`
which defines one method - `redirectToReferer(string $fallbackPath = null): RedirectResponse`.

## Flash Messages
Another stupid thing which seems too complicated to me is translating of flash messages.
It's actually very easy but you need a session and a translator.
Two dependencies just to show a translated flash message.
To simplify that there is `Vanio\WebBundle\Request\FlashMessage` value object you can use as an envelope of the message and pass the message parameters and domain to it.

```php
$this->addFlash(FlashMessage::TYPE_DANGER, new FlashMessage('message', ['key' => 'value'], 'vanio_web'));
```

But adding a flash message is just half of the problem.
You'll also need to display it somewhere in your view and actually translate it yourself.

## Templating

Sometimes, even just determining a class name of HTML elements can be cumbersome when it depends on some conditions.
So there is `class_name(array $classes): string` Twig function.
You need to pass it an array where key is a class and value is a boolean value indicating whether this class name should be present.
Passing a numeric key means the class name is always present.

In Twig, there is no possibility how to determine whether a given object implements a given type.
So, for example, it is not possible to determine whether a flash message is just a string or an instance of the added `FlashMessage` class.
And that's why `instance of(string $class)` Twig test was added. You can use it like this:

```twig
{{ message is instance of('Vanio\\WebBundle\\Request\\FlashMessage')
    ? message.message|trans(message.parameters, message.domain, message.locale)
    : message }}
```

# Default Configuration
```yml
referer_fallback_path: /
```
