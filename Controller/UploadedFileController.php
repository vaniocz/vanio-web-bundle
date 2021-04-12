<?php
namespace Vanio\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vanio\WebBundle\Model\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadedFileController
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var UploaderHelper */
    private $uploaderHelper;

    /** @var CacheManager|null */
    private $cacheManager;

    public function __construct(
        ManagerRegistry $doctrine,
        UploaderHelper $uploaderHelper,
        ?CacheManager $cacheManager
    ) {
        $this->doctrine = $doctrine;
        $this->uploaderHelper = $uploaderHelper;
        $this->cacheManager = $cacheManager;
    }

    public function uploadAction(Request $request): JsonResponse
    {
        if (!$file = current($request->files->all())) {
            throw new BadRequestHttpException('Missing file.');
        }

        $file = new File($file);
        $uploadedFile = new UploadedFile($file, $request->getSession()->getId());
        $this->uploadedFileRepository()->add($uploadedFile);
        $this->entityManager()->flush();

        return new JsonResponse([
            'id' => (string) $uploadedFile->id(),
            'url' => $this->uploaderHelper->asset($uploadedFile, 'file'),
            'thumbnailUrl' => $this->resolveThumbnailUrl($uploadedFile, $request->get('thumbnailFilter')),
            'name' => $file->metaData()['name'] ?? null,
            'size' => $file->metaData()['size'] ?? null,
        ]);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string|null $thumbnailFilter
     * @return string|null
     */
    private function resolveThumbnailUrl(UploadedFile $uploadedFile, string $thumbnailFilter = null)
    {
        if ($thumbnailFilter === null || !$this->cacheManager || !$uploadedFile->file()->isImage()) {
            return null;
        }

        $path = $this->uploaderHelper->asset($uploadedFile, 'file');

        return $uploadedFile->file()->metaData()['mimeType'] === 'image/svg+xml'
            ? $path
            : $this->cacheManager->getBrowserPath(parse_url($path, PHP_URL_PATH), $thumbnailFilter);
    }

    private function uploadedFileRepository(): EntityRepository
    {
        return $this->entityManager()->getRepository(UploadedFile::class);
    }

    private function entityManager(): EntityManager
    {
        return $this->doctrine->getManagerForClass(UploadedFile::class);
    }
}
