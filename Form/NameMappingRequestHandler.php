<?php
namespace Vanio\WebBundle\Form;

use Assert\Assert;
use Assert\Assertion;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class NameMappingRequestHandler implements RequestHandlerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
        $transformedName = $nameMapping[''] ?? null;
        $translationDomain = $form->getConfig()->getOption('name_translation_domain');

        if (is_string($translationDomain) && $transformedName !== '') {
            $translationId = $transformedName ?? NameMappingExtension::resolveTranslationId($form);
            $transformedName = $this->translate($translationId, $nameTranslationDomain);
        }

        if ($transformedName === null) {
            $transformedName = $form->getName();
        }

        $transformedData = $this->transformData(
            $form,
            $transformedName === '' ? $data : $data[$transformedName],
            $nameMapping,
            $translationDomain
        );

        if ($transformedData) {
            $form->submit($transformedData);
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
     * @param string|null|bool $translationDomain
     * @return mixed
     */
    private function transformData(FormInterface $form, $data, array $nameMapping, $translationDomain = null)
    {
        if (!$form->count()) {
            return $data;
        }

        $formConfig = $form->getConfig();
        $type = $formConfig->getType();
        $transformedData = [];

        foreach ($form as $name => $child) {
            $childNameMapping = NameMappingExtension::resolveNameMapping($child);
            $childNameMapping = array_replace_recursive($childNameMapping, $nameMapping[$name] ?? []);
            $childTranslationDomain = $child->getConfig()->getOption('name_translation_domain') ?? $translationDomain;
            $transformedName = $childNameMapping[''] ?? null;

            if (is_string($childTranslationDomain) && $transformedName !== '') {
                $translationId = $transformedName ?? NameMappingExtension::resolveTranslationId($child);
                $translatedName = $this->translate($translationId, $childTranslationDomain);

                if ($translatedName !== null) {
                    $transformedName = $translatedName;
                }
            }

            if ($transformedName === null) {
                $transformedName = $child->getName();
            }

            if ($transformedName === '') {
                $transformedData[$name] = $this->transformData(
                    $child,
                    $data,
                    $childNameMapping,
                    $childTranslationDomain
                );
            } elseif (!is_array($data)) {
                return $data;
            } elseif (array_key_exists($transformedName, $data)) {
                $transformedData[$name] = $this->transformData(
                    $child,
                    $data[$transformedName],
                    $childNameMapping,
                    $childTranslationDomain
                );
            }
        }

        return $transformedData;
    }

    private function translate(string $id, string $domain): ?string
    {
        $isTranslated = $this->translator instanceof TranslatorBagInterface
            && $this->translator->getCatalogue(null)->has($id, $domain)
            && $this->translator->getCatalogue(null)->get($id, $domain) !== false;

        if (!$isTranslated) {
            return null;
        }

        $path = explode('.', $this->translator->trans($id, [], $domain));

        return end($path);
    }
}
