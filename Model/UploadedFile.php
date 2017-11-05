<?php
namespace Vanio\WebBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\DomainBundle\Model\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=EntityRepository::class)
 * @Vich\Uploadable
 */
class UploadedFile
{
    /**
     * @var Uuid
     * @ORM\Column
     * @ORM\Id
     */
    private $id;

    /**
     * @var File
     * @ORM\Embedded(class=File::class)
     * @Vich\UploadableField(mapping="uploaded_file")
     */
    private $file;

    /**
     * @var string
     * @ORM\Column
     */
    private $sessionId;

    public function __construct(File $file, string $sessionId)
    {
        $this->id = Uuid::uuid4();
        $this->file = $file;
        $this->sessionId = $sessionId;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function file(): File
    {
        return $this->file;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }
}
