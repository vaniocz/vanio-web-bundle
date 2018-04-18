<?php
namespace Vanio\WebBundle\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Vanio\Stdlib\Strings;

class WarmupImagesCommand extends Command
{
    /** @var HttpKernelInterface */
    public $kernel;

    /** @var UrlMatcherInterface */
    public $urlMatcher;

    /** @var Client|null */
    private $client;

    public function __construct(HttpKernelInterface $kernel, UrlMatcherInterface $router)
    {
        parent::__construct();
        $this->kernel = $kernel;
        $this->urlMatcher = $router;
    }

    protected function configure()
    {
        $this
            ->setName('vanio:warmup-images')
            ->setDescription('Requests images by crawling pages.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path used to crawl images.', '/');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->client()->followRedirects();
        $path = $input->getArgument('path');

        if (!$imagePaths = $this->requestImagePaths($path)) {
            $output->writeln(sprintf('No images to warmup on path <info>%s</info>.', $path));

            return;
        }

        $output->writeln(sprintf('Warming up images on path <info>%s</info>.', $path));
        $progressBar = new ProgressBar($output);
        $progressBar->setFormatDefinition('custom', "%current%/%max% [%bar%] %percent:3s%%\nWarming up <info>%path%</info>.");
        $progressBar->setFormat('custom');
        $this->client()->followRedirects(false);
        $progressBar->start(count($imagePaths));

        foreach ($imagePaths as $path) {
            $progressBar->setMessage($path, 'path');
            $progressBar->advance();
            $this->client()->request(Request::METHOD_GET, $path);
        }

        $progressBar->finish();
    }

    private function requestImagePaths(string $path): array
    {
        $crawler = $this->client()->request(Request::METHOD_GET, $path);
        $imagePaths = [];

        foreach ($crawler->filter('img') as $image) {
            foreach ($this->resolveImagePaths($image) as $path) {
                try {
                    $parameters = $this->urlMatcher->match($path);

                    if (in_array($parameters['_route'] ?? null, ['liip_imagine_filter'])) {
                        $imagePaths[$path] = $path;
                    }
                } catch (ResourceNotFoundException $e) {}
            }
        }

        return $imagePaths;
    }

    /**
     * @param \DOMElement $image
     * @return string[]
     */
    private function resolveImagePaths(\DOMElement $image): array
    {
        $imagePaths = [];

        if ($srcSet = $image->getAttribute('srcset') ?: $image->getAttribute('data-srcset')) {
            foreach (explode(',', $srcSet) as $src) {
                $imagePaths[] = $this->resolvePath(current(explode(' ', trim($src))));
            }
        }

        $src = $image->getAttribute('src') ?: $image->getAttribute('data-src');

        if ($src && !Strings::startsWith($src, 'data:')) {
            $imagePaths[] = $this->resolvePath($src);
        }

        return $imagePaths;
    }

    private function resolvePath(string $src): string
    {
        if (Strings::contains($src, '//')) {
            $src = preg_replace('~^.*//~', '', $src);
            $src = substr($src, strlen($this->urlMatcher->getContext()->getHost()));
        }

        return substr($src, strlen($this->urlMatcher->getContext()->getBaseUrl()));
    }

    private function client(): Client
    {
        if (!$this->client) {
            $this->client = new Client($this->kernel);
        }

        return $this->client;
    }
}
