<?php
namespace Vanio\WebBundle\Form;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Assert\Assertion;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vanio\WebBundle\Model\UploadedFile;

class UploadedFileType extends AbstractType implements DataMapperInterface
{
    /** @var EntityRepository */
    private $uploadedFileRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var CacheManager|null */
    private $cacheManager;

    /** @var string */
    private $webRoot;

    public function __construct(
        EntityRepository $uploadedFileRepository,
        RequestStack $requestStack,
        string $webRoot,
        CacheManager $cacheManager = null
    ) {
        $this->uploadedFileRepository = $uploadedFileRepository;
        $this->requestStack = $requestStack;
        $this->cacheManager = $cacheManager;
        $this->webRoot = str_replace('\\', '/', realpath($webRoot));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper($this);
        $builder->add('files', TextType::class, [
            'required' => $options['required'],
            'required_message' => $options['required_message'],
            'error_bubbling' => true,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars += [
            'multiple' => $options['multiple'],
            'accept' => $options['accept'],
            'thumbnailFilter' => $options['thumbnail_filter'],
            'nonCompoundWrapper' => true,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'class' => File::class,
                'multiple' => false,
                'accept' => null,
                'thumbnail_filter' => 'uploaded_file_thumbnail',
                'required_message' => 'Choose a file.',
                'error_bubbling' => false,
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('multiple', 'bool')
            ->setAllowedTypes('accept', ['string', 'null'])
            ->setAllowedTypes('thumbnail_filter', ['string', 'null']);
    }

    /**
     * @param File|File[]|null $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        $form = $forms['files'];
        $config = $form->getParent()->getConfig();
        $thumbnailFilter = $config->getOption('thumbnail_filter');
        $formData = [];

        if ($data === null) {
            $data = [];
        } elseif (!$config->getOption('multiple')) {
            $data = [$data];
        }

        foreach ($data as $key => $file) {
            $url = $this->resolveFilePath($file);

            if ($url !== null) {
                $formData[] = [
                    'key' => $key,
                    'url' => $url,
                    'thumbnailUrl' => $this->resolveThumbnailUrl($file, $thumbnailFilter),
                    'name' => $file->metaData()['name'] ?? null,
                    'size' => $file->metaData()['size'] ?? null,
                ];
            }
        }

        $form->setData($formData === [] ? null : json_encode($formData));
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        $form = $forms['files'];
        $config = $form->getParent()->getConfig();
        $formData = $form->getData();
        $formData = $formData === null ? [] : json_decode($form->getData(), true);
        $files = [];
        $class = $config->getOption('class');

        if (!$multiple = $config->getOption('multiple')) {
            $data = [$data];
        }

        foreach ($formData as $fileData) {
            if (isset($fileData['key'])) {
                $files[$fileData['key']] = $data[$fileData['key']];
            } elseif (isset($fileData['id'])) {
                $file = $this->getUploadedFile($fileData['id'])->file();
                $files[] = new $class($file);
            }
        }

        $data = $multiple ? $files : (reset($files) ?: null);
    }

    private function resolveFilePath(File $file): ?string
    {
        if (!$path = $file->file()->getRealPath()) {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        $message = sprintf('The file "%s" is placed outside of web root "%s".', $path, $this->webRoot);
        Assertion::startsWith($path, $this->webRoot, $message);
        $path = substr($path, strlen($this->webRoot));

        return $path;
    }

    /**
     * @param File $file
     * @param string|null $thumbnailFilter
     * @return string|null
     */
    private function resolveThumbnailUrl(File $file, string $thumbnailFilter = null)
    {
        if ($thumbnailFilter === null || !$this->cacheManager || !$file->isImage()) {
            return null;
        }

        $path = $this->resolveFilePath($file);

        return $file->metaData()['mimeType'] === 'image/svg+xml'
            ? $path
            : $this->cacheManager->getBrowserPath($path, $thumbnailFilter);
    }

    private function getUploadedFile(string $id): UploadedFile
    {
        return $this->uploadedFileRepository->getOneBy([
            'id' => $id,
            'sessionId' => $this->requestStack->getCurrentRequest()->getSession()->getId(),
        ]);
    }
}
