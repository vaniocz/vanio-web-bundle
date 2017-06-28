<?php
namespace Vanio\WebBundle\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vanio\WebBundle\Model\UploadedFile;

class UploadedFilesController extends Controller
{
    public function uploadAction(Request $request): JsonResponse
    {
        $uploadedFile = new UploadedFile(new File(current($request->files->all())), $request->getSession()->getId());
        $this->uploadedFileRepository()->add($uploadedFile);
        $this->entityManager()->flush();

        return new JsonResponse(['id' => $uploadedFile->id()]);
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
}
