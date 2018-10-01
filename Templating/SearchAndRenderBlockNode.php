<?php
namespace Vanio\WebBundle\Templating;

use Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode as BaseSearchAndRenderBlockNode;

class SearchAndRenderBlockNode extends BaseSearchAndRenderBlockNode
{
    public function compile(\Twig_Compiler $compiler)
    {
        $argumentsNode = $this->getNode('arguments');
        $arguments = iterator_to_array($argumentsNode);
        /** @var \Twig_Node $nameNode */
        $nameNode = array_shift($arguments);

        foreach ($arguments as $i => $argument) {
            $argumentsNode->setNode($i, $argument);
        }

        if (count($argumentsNode) < 2) {
            $compiler->raw("(function () { throw new Twig_Error_Runtime('Twig function \"form_block\" expects at least 2 arguments.', {$this->lineno}, \$this->source); })();");
        } elseif (
            !$nameNode instanceof \Twig_Node_Expression_Constant
            || !is_string($nameNode->getAttribute('value'))
        ) {
            $compiler->raw("(function () { throw new Twig_Error_Runtime('Twig function \"form_block\" expects the first argument to be a constant string.', {$this->lineno}, \$this->source); })();");
        } else {
            $this->setAttribute('name', sprintf('form_%s', str_replace('_', '', $nameNode->getAttribute('value'))));
        }

        $argumentsNode->removeNode(count($arguments));
        $this->setNode('arguments', $argumentsNode);
        parent::compile($compiler);
    }
}
