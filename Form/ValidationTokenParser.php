<?php
namespace Vanio\WebBundle\Form;

use Assert\LazyAssertion;
use Doctrine\Common\Annotations\TokenParser;
use Symfony\Bundle\FrameworkBundle\Translation\PhpStringTokenParser;
use Vanio\DomainBundle\Assert\Validate;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\Stdlib\Strings;
use Vanio\TypeParser\UseStatementsParser;

class ValidationTokenParser implements ValidationParser
{
    /** @var UseStatementsParser */
    private $useStatementsParser;

    /** @var TokenParser */
    private $tokenParser;

    /** @var array */
    private $validationRules;

    public function __construct(UseStatementsParser $useStatementsParser = null)
    {
        $this->useStatementsParser = $useStatementsParser ?: new UseStatementsParser;
    }

    /**
     * @param string $class
     * @return array
     */
    public function parseValidationRules(string $class): array
    {
        if ($validationRules = $this->validationRules[$class] ?? null) {
            return $validationRules;
        }

        $useStatements = $this->useStatementsParser->parseClass($class);
        $this->tokenParser = new TokenParser($this->getClassContents(new \ReflectionClass($class)));
        $this->validationRules[$class] = [];
        $token = $this->tokenParser->next();

        while ($token) {
            $validationClass = strtolower($token[1] ?? null);
            $validationClass = $useStatements[$validationClass] ?? $validationClass;
            $token = $this->tokenParser->next();

            if (($token[0] ?? null) !== T_DOUBLE_COLON) {
                continue;
            }

            $token = $this->tokenParser->next();

            if (($token[0] ?? null) !== T_STRING) {
                continue;
            }

            $method = $token[1];

            if (is_a($validationClass, Validate::class, true)) {
                $this->validationRules[$class] = array_merge(
                    $this->validationRules[$class],
                    $this->parseValidateRules($validationClass, $method)
                );
            } elseif (is_a($validationClass, Validation::class, true)) {
                if (Strings::startsWith($method, 'nullOr')) {
                    $method = substr($method, 6);
                }

                if ($validationRule = $this->parseValidationRule($validationClass, $method)) {
                    $this->validationRules[$class][] = $validationRule;
                }
            }
        }

        return $this->validationRules[$class];
    }

    private function parseValidateRules(string $class, string $method): array
    {
        if ($method === 'lazy') {
            return $this->parseTokens(['(', ')', T_OBJECT_OPERATOR, 'that']) ? $this->parseLazyRules($class) : [];
        } elseif ($method === 'that') {
            return $this->parseChainedRules($class);
        }

        return [];
    }

    private function parseValidationRule(
        string $class,
        string $method,
        array $defaultOptions = [],
        bool $shouldSkipFirstArgument = false
    ): array {
        $options = $this->parseMethodArguments($class, $method, $shouldSkipFirstArgument) + $defaultOptions + [
            '_class' => $class,
            '_method' => $method,
        ];

        return isset($options['message']) ? $options : [];
    }

    private function parseLazyRules(string $class): array
    {
        $rules = [];
        $defaultOptions = $this->parseMethodArguments(LazyAssertion::class, 'that');
        $validationClass = $class::assertionClass();

        while ($this->parseToken(T_OBJECT_OPERATOR)) {
            $method = $this->parseToken(T_STRING);

            if ($method === 'that') {
                return array_merge($rules, $this->parseLazyRules($class));
            } elseif ($method === 'verifyNow') {
                return $rules;
            } elseif ($rule = $this->parseChainedRule($validationClass, $method, $defaultOptions)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function parseChainedRules(string $class): array
    {
        $rules = [];
        $defaultOptions = $this->parseMethodArguments($class, 'that');
        $validationClass = $class::assertionClass();

        while ($this->parseToken(T_OBJECT_OPERATOR)) {
            $method = $this->parseToken(T_STRING);

            if ($rule = $this->parseChainedRule($validationClass, $method, $defaultOptions)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function parseChainedRule(string $class, string $method, array $defaultOptions = []): array
    {
        return $this->parseValidationRule($class, $method, $defaultOptions, true);
    }

    private function parseMethodArguments(string $class, string $method, bool $shouldSkipFirstArgument = false): array
    {
        if (!$this->parseToken('(')) {
            return [];
        }

        $arguments = $this->parseFunctionArguments();
        $parameters = (new \ReflectionMethod($class, $method))->getParameters();
        array_shift($parameters);
        $options = [];

        foreach ($parameters as $parameter) {
            $name = null;

            if (!$argument = $arguments[$parameter->getPosition() - $shouldSkipFirstArgument] ?? null) {
                return $options;
            } elseif ($parameter->name === 'propertyPath' || $parameter->name === 'defaultPropertyPath') {
                $name = '_propertyPath';
            } elseif ($parameter->name === 'message' || $parameter->name === 'defaultMessage') {
                $name = 'message';
            }

            try {
                $value = $this->resolveScalarToken($argument);
            } catch (\UnexpectedValueException $e) {
                return [];
            }

            if ($value !== null || $name === null) {
                $options[$name ?? $parameter->name] = $value;
            }
        }

        return $options;
    }

    private function parseFunctionArguments(): array
    {
        $arguments = [];
        $nesting = 0;
        $position = 0;

        while ($token = $this->tokenParser->next()) {
            if ($token === '(') {
                $nesting++;
            } elseif ($token === ')') {
                if (!$nesting) {
                    return $arguments;
                }

                $nesting--;
            } elseif ($token === ',' && !$nesting) {
                $position++;
                continue;
            }

            $arguments[$position][] = $token;
        }

        return $arguments;
    }

    private function parseTokens(array $expectedTokens): array
    {
        $tokens = [];

        foreach ($expectedTokens as $expectedToken) {
            if (!$token = $this->parseToken($expectedToken)) {
                return [];
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param int|string|null $expectedToken
     * @return string|null
     */
    private function parseToken($expectedToken = null)
    {
        $token = $this->tokenParser->next();
        $value = $token[1] ?? $token;

        return $expectedToken === null || $value === $expectedToken || ($token[0] ?? null) === $expectedToken
            ? $value
            : null;
    }

    /**
     * @param array $tokens
     * @return mixed
     * @throws \UnexpectedValueException
     */
    private function resolveScalarToken(array $tokens)
    {
        $value = null;
        $hereDoc = '';

        foreach ($tokens as $token) {
            if (!isset($token[1])) {
                break;
            }

            switch ($token[0]) {
                case T_LNUMBER:
                    return (int) $token[1];
                case T_DNUMBER:
                    return (float) $token[1];
                case T_ENCAPSED_AND_WHITESPACE:
                case T_CONSTANT_ENCAPSED_STRING:
                    $value .= $token[1];
                    break;
                case T_START_HEREDOC:
                    $hereDoc = $token[1];
                    break;
                case T_END_HEREDOC:
                    return PhpStringTokenParser::parseDocString($hereDoc, $value);
                default:
                    throw new \UnexpectedValueException(sprintf('Unexpected token %s.', token_name($token[0])));
            }
        }

        return $value === null ? null : PhpStringTokenParser::parse($value);
    }

    private function getClassContents(\ReflectionClass $class): string
    {
        $lineNumber = $class->getStartLine();

        try {
            $file = new \SplFileObject($class->getFileName());
        } catch (\Exception $e) {
            return '';
        }

        $file->seek($lineNumber - 1);
        $contents = '<?php ' . $file->current();

        while ($lineNumber++ < $class->getEndLine()) {
            $contents .= $file->fgets();
        }

        return $contents;
    }
}
