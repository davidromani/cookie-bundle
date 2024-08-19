<?php

namespace Eveltic\CookieBundle\Command;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ev:cookie:install',
    description: 'Install eveltic cookie bundle.'
)]
class InstallCommand extends Command
{
    private $installFiles = [
        '/vendor/eveltic/cookie-bundle/config/eveltic_cookie.yaml' => '/config/packages/eveltic_cookie.yaml',
        '/vendor/eveltic/cookie-bundle/config/routes/eveltic_cookie.yaml' => '/config/routes/eveltic_cookie.yaml',
    ];

     /**
     * Constructor for the InstallCommand class.
     *
     * @param KernelInterface $kernel 
     *        The Symfony Kernel Interface used to get the project's root directory.
     */
    public function __construct(
        private readonly KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->copyFiles($input, $output);
        return Command::SUCCESS;
    }

    private function copyFiles(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        $totalFiles = count($this->installFiles);
        $errorCount = 0;

        $io->info('Installing bundle assets');

        $progressBar = new ProgressBar($io, $totalFiles);
        $progressBar->start();
        foreach($this->installFiles as $fileOrigin => $fileDestination){
            $from = $this->getPath($fileOrigin);
            $to = $this->getPath($fileDestination);
            $filesystem->copy($from, $to, true);
            $progressBar->advance();
            if(!$filesystem->exists($to)){
                $errorCount++;
                $io->caution(sprintf('File %s cannot be installed, please perform a manual installation. Please read the file Readme.md', $to));
            }
        }

        $progressBar->finish();
    
        $io->newLine(2);
        $io->info('Installation finished');
        $io->newLine(2);
        if($errorCount == 0){
            $io->success('All files installed successfully.');
        } else if ($errorCount < $totalFiles) {
            $io->caution('Some files were not installed. Please check error messages.');
        } else {
            $io->error('No files were installed. Please check error messages.');
        }
    }

    private function getPath($relative = null): string
    {
        return sprintf('%s%s', $this->kernel->getProjectDir(), $relative);
    }
}