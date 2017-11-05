<?php
namespace Vanio\WebBundle\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Templating\Helper\ImagineHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vanio\WebBundle\Model\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadedFileController extends Controller
{
    public function uploadAction(Request $request): JsonResponse
    {
        if (!$file = current($request->files->all())) {
            throw new BadRequestHttpException('Missing file.');
        }

        $file = new File($file);
        $uploadedFile = new UploadedFile($file, $request->getSession()->getId());
        $this->uploadedFileRepository()->add($uploadedFile);
        $this->entityManager()->flush();
        $path = $this->uploaderHelper()->asset($uploadedFile, 'file');
        $thumbnailFilter = $request->get('thumbnailFilter');

        if ($thumbnailFilter !== null && $file->isImage()) {
            $path = $this->cacheManager()->getBrowserPath($path, $thumbnailFilter);
        }

        return $this->json([
            'id' => (string) $uploadedFile->id(),
            'path' => $path,
            'name' => $file->metaData()['name'] ?? null,
            'size' => $file->metaData()['size'] ?? null,
            'mimeType' => $file->metaData()['mimeType'] ?? null,
        ]);
    }

    private function uploadedFileRepository(): EntityRepository
    {
        return $this->entityManager()->getRepository(UploadedFile::class);
    }

    private function entityManager(): EntityManager
    {
        return $this->doctrine()->getManagerForClass(UploadedFile::class);
    }

    private function doctrine(): ManagerRegistry
    {
        return $this->get('doctrine');
    }

    private function uploaderHelper(): UploaderHelper
    {
        return $this->get('vich_uploader.templating.helper.uploader_helper');
    }

    private function cacheManager(): CacheManager
    {
        return $this->container->get('liip_imagine.cache.manager');
    }
}
