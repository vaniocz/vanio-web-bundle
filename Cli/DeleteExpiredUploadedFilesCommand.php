<?php
namespace Vanio\WebBundle\Cli;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanio\DomainBundle\Doctrine\EntityRepository;
use Vanio\WebBundle\Model\UploadedFile;

class DeleteExpiredUploadedFilesCommand extends Command
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var EntityRepository */
    private $uploadedFileRepository;

    public function __construct(ManagerRegistry $registry, EntityRepository $uploadedFileRepository)
    {
        parent::__construct();
        $this->registry = $registry;
        $this->uploadedFileRepository = $uploadedFileRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('delete-expired-uploaded-files')
            ->addOption(
                'expiration',
                'x',
                InputOption::VALUE_OPTIONAL,
                'Expiration date/time in strtotime format.',
                '1 day'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $expiration = new \DateTime($input->getOption('expiration'));

        if ($expiration > new \DateTime) {
            $expiration = new \DateTime("-{$input->getOption('expiration')}");
        }

        $deletedFilesCount = 0;
        $output->writeln("Deleting uploaded files older than {$expiration->format('Y-m-d H:i:s')}.");

        foreach ($this->findExpiredUploadedFiles($expiration) as $uploadedFile) {
            $this->uploadedFileRepository->remove($uploadedFile);
            $output->writeln($uploadedFile->file()->file()->getPathname(), OutputInterface::VERBOSITY_VERBOSE);
            $deletedFilesCount++;
        }

        $this->registry->getManagerForClass(UploadedFile::class)->flush();
        $output->writeln("<info>{$deletedFilesCount} expired uploaded files have been deleted.</info>");
    }

    /**
     * @return array|UploadedFile[]
     */
    private function findExpiredUploadedFiles(\DateTime $expiration): array
    {
        return $this->uploadedFileRepository
            ->createQueryBuilder('uploadedFile')
            ->where('uploadedFile.file.uploadedAt < :uploadedAt')
            ->setParameter('uploadedAt', $expiration)
            ->getQuery()
            ->getResult();
    }
}
