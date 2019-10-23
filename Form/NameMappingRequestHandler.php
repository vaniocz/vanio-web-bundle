<?php
namespace Vanio\WebBundle\Form;

use Assert\Assert;
use Assert\Assertion;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\File;
use Symfony\Component\HttpFoundation\Request;

class NameMappingRequestHandler implements RequestHandlerInterface
{
    /**
     * @param FormInterface $form
     * @param Request|null $request
     */
    public function handleRequest(FormInterface $form, $request = null): void
    {
        Assertion::isInstanceOf($request, Request::class);

        if ($request->getMethod() !== $form->getConfig()->getMethod()) {
            return;
        }

        $data = in_array($request->getMethod(), ['GET', 'HEAD', 'TRACE'])
            ? $request->query->all()
            : $request->request->all();

        if (!$data) {
            return;
        }

        $nameMapping = NameMappingExtension::resolveNameMapping($form);
        $transformedData = $this->transformData($form, $data, $nameMapping);
        $transformedName = $nameMapping[''] ?? $form->getName();

        if ($transformedName === '') {
            $form->submit($transformedData);
        } elseif (isset($transformedData[$transformedName])) {
            $form->submit($transformedData[$transformedName]);
        }
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public function isFileUpload($data): bool
    {
        return $data instanceof File;
    }

    /**
     * @param FormInterface $form
     * @param mixed $data
     * @param string[] $nameMapping
     * @return mixed
     */
    private function transformData(FormInterface $form, $data, array $nameMapping)
    {
        if (!$form->count()) {
            return $data;
        }

        $transformedData = [];

        foreach ($form as $name => $child) {
            $childNameMapping = NameMappingExtension::resolveNameMapping($child);
            $childNameMapping = array_replace_recursive($childNameMapping, $nameMapping[$name] ?? []);
            $transformedName = $childNameMapping[''] ?? $child->getName();

            if ($transformedName === '') {
                $transformedData[$name] = $this->transformData($child, $data, $childNameMapping);
            } elseif (array_key_exists($transformedName, $data)) {
                $transformedData[$name] = $this->transformData($child, $data[$transformedName], $childNameMapping);
            }
        }

        return $transformedData;
    }
}
