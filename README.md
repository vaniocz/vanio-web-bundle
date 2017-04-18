# [<img alt="Vanio" src="http://www.vanio.cz/img/vanio-logo.png" width="130" align="top">](http://www.vanio.cz) Web Bundle

[![Build Status](https://travis-ci.org/vaniocz/vanio-web-bundle.svg?branch=master)](https://travis-ci.org/vaniocz/vanio-web-bundle)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/vanio-web-bundle/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/vanio-web-bundle?branch=master)
![PHP7](https://img.shields.io/badge/php-7-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/vanio-web-bundle/license)](https://github.com/vaniocz/vanio-web-bundle/blob/master/LICENSE)

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

## Detecting request type
To detect whether current request is master or sub request, special request attribute `_request_type` is set.
This logic happens inside `Vanio\WebBundle\Request\RequestTypeListener` listener which is not registered by default.
Enable this feature by setting `detect_request_type` configuration parameter to true. 

## Redirecting to Referer
It's quite a common task to redirect user back after certain actions.
This bundle defines a service named `vanio_web.request.referer_resolver` which helps you with that.
First it tries to read from `_referer` (`%vanio_web.referer_parameter%`) query parameter. 
When the query parameter is not present then it reads HTTP_REFERER header and tries to match the referring URL against
defined routes. In case of missing header or invalid URL (like URL pointing to a different webpage) fallback path
is used. Since this functionality is mostly used from inside controllers,
it is possible to use `Vanio\WebBundle\Request\RefererHelperTrait`
which defines one method - `redirectToReferer(string $fallbackPath = null): RedirectResponse`.

## Flash Messages
Another thing which seems too complicated to me is translating of flash messages.
It's actually very easy but you need a session and a translator.
Two dependencies just to show a translated flash message.
To simplify that there is `Vanio\WebBundle\Translation\FlashMessage` value object you can use as an envelope
of the message and pass the message parameters and domain to it. This bundle also replaces `translation.extractor.php`
service with implementation able to extract messages from `FlashMessage` constructor.

```php
$this->addFlash(FlashMessage::TYPE_DANGER, new FlashMessage('message', ['key' => 'value'], 'vanio_web'));
```

But adding a flash message is just half of the problem.
You'll also need to display it somewhere in your view and actually translate it yourself.

## Form state URL canonization
Due to SEO optimizations and also in situations where having full form state serialized in URL is too long and ugly it
is possible to set `canonize: true` in form options. When you submit the form it is then redirected to canonical URL
where all empty form fields (even those equal to empty_data option) are ommited.

## Templating

### Generating class name
Sometimes, even just generating a class name of HTML elements can be cumbersome when it depends on some conditions.
Let's use `class_name(array $classes): string` Twig function.
You need to pass it an array where key is a class and value is a boolean value indicating whether this class name should
be present.

### Checking whether a text is translated
To check whether text is translated you can use `is_translated(string $id, string $locale = null)` Twig
function which checks translator's catalogue. 

### Determining current menu item
To determine whether a current request matches a menu item, use `is_current(string $route): bool` Twig function.
The route is considered current when either `_route` request attribute equals to the given route or when request
pathinfo starts with the route path and it's delimited by `/`.

### Testing whether a given object implements a given type
In Twig, there is no possibility how to determine whether a given object implements a given type.
So, for example, it is not possible to determine whether a flash message is just a string or an instance of the added
`FlashMessage` class.
And that's why `instance of(string $class)` Twig test was added. You can use it like this:

```twig
{{ message is instance of('Vanio\\WebBundle\\Translation\\FlashMessage')
    ? message.message|trans(message.parameters, message.domain, message.locale)
    : message }}
```

### Filtering arrays
To filter an array there is `filter` filter. The filtering callback has the same implementation as `empty` Twig test.
Array keys are preserved. 
```twig
{{ [null, 1]|filter }}
```

### Removing keys from arrays
Removing certain keys from an array is possible using `without(array $array, $keys)` Twig filter.
Pass it either a string or an array of keys to remove and it will return a new array with the given keys being unset. 
```twig
{{ {foo: 'bar', bar: 'baz'}|without('foo') }}
```

### Replacing based on regular expressions
The builtin `replace` Twig filter uses `strtr` under the hood but there is no support for replacing based on regular
expressions. So we've implemented `regexp_replace(string $string, $pattern, $replacement)` filter.
You can pass it either an array with keys as regular expressions and values as replacements or when the replacement
argument is provided then the pattern can be either a string or an array (keys are ignored).

```twig
{{ 'foo bar'|regexp_replace({'~foo~': 'baz', '~bar~': 'qux'}) }}
{{ 'foo'|regexp_replace('~foo~', 'bar') }}
{{ 'foo bar'|regexp_replace(['~foo~', '~bar~'], 'baz') }}
```

### Converting HTML to plaintext
Have you ever created an HTML e-mail? Providing plaintext alternative manually is tedious
so `html_to_text(string $html, array $options = []): string` Twig filter is your friend in such cases.
It uses handy [html2text](https://github.com/mtibben/html2text) library.   

# Default Configuration
```yml
detect_request_type: false
referer_fallback_path: /
referer_parameter: _referer
```
