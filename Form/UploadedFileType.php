<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vanio\WebBundle\Model\UploadedFile;

class UploadedFileType extends AbstractType implements DataMapperInterface
{
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_SUCCESS = 'success';

    /** @var EntityRepository */
    private $uploadedFileRepository;

    /** @var SessionStorageInterface */
    private $sessionStorage;

    public function __construct(EntityRepository $uploadedFileRepository, SessionStorageInterface $sessionStorage)
    {
        $this->uploadedFileRepository = $uploadedFileRepository;
        $this->sessionStorage = $sessionStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper($this);
        $builder->add('files', HiddenType::class, [
            'error_bubbling' => false,
            'required' => $options['required'],
            'required_message' => $options['required_message'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'class' => File::class,
                'multiple' => false,
                'accept' => null,
                'required_message' => 'Choose a file.',
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('multiple', 'bool')
            ->setAllowedTypes('accept', ['string', 'null']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['accept'] = $options['accept'];
    }

    /**
     * @param File|File[]|null $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $form */
        $form = reset($forms);
        $formData = [];

        if (!$form->getParent()->getConfig()->getOption('multiple')) {
            $data = $data === null ? [] : [$data];
        }

        foreach ($data as $key => $file) {
            $formData[] = 'uploaded:' . $key;
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
        /** @var FormInterface $form */
        $form = reset($forms);
        $formData = $form->getData();
        $formData = $formData === null ? [] : json_decode($form->getData());
        $multiple = $form->getParent()->getConfig()->getOption('multiple');
        $data = $multiple ? $data : [$data];
        $files = [];

        foreach ($formData as $file) {
            list($status, $key) = explode(':', $file, 2);

            if ($status === self::STATUS_SUCCESS) {
                $file = $this->getUploadedFile($key)->file();
                $class = $form->getParent()->getConfig()->getOption('class');

                if (!is_a($file, $class, true)) {
                    $file = new $class($file);
                }
            } else {
                $file = $data[$key];
            }

            if ($status === self::STATUS_UPLOADED) {
                $files[$key] = $file;
            } else {
                $files[] = $file;
            }
        }

        $data = $multiple ? $files : (reset($files) ?: null);
    }

    protected function sessionStorage(): SessionStorageInterface
    {
        return $this->sessionStorage;
    }

    private function getUploadedFile(string $uploadedFileId): UploadedFile
    {
        return $this->uploadedFileRepository->get($uploadedFileId);
    }
}
