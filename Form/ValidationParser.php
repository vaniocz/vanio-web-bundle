<?php
namespace Vanio\WebBundle\Form;

interface ValidationParser
{
    function parseValidationRules(string $class): array;
}
