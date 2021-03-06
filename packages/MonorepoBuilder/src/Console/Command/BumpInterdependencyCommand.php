<?php declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\MonorepoBuilder\FileSystem\ComposerJsonProvider;
use Symplify\MonorepoBuilder\InterdependencyUpdater;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class BumpInterdependencyCommand extends Command
{
    /**
     * @var string
     */
    private const VERSION_ARGUMENT = 'version';

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var InterdependencyUpdater
     */
    private $interdependencyUpdater;

    /**
     * @var ComposerJsonProvider
     */
    private $composerJsonProvider;

    public function __construct(
        SymfonyStyle $symfonyStyle,
        InterdependencyUpdater $interdependencyUpdater,
        ComposerJsonProvider $composerJsonProvider
    ) {
        parent::__construct();

        $this->symfonyStyle = $symfonyStyle;
        $this->interdependencyUpdater = $interdependencyUpdater;
        $this->composerJsonProvider = $composerJsonProvider;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Bump dependency of split packages on each other');
        $this->addArgument(
            self::VERSION_ARGUMENT,
            InputArgument::REQUIRED,
            'New version of inter-dependencies, e.g. "^4.4.2"'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getArgument(self::VERSION_ARGUMENT);

        $rootComposerJson = $this->composerJsonProvider->getRootComposerJson();

        // @todo resolve better for only found packages
        // see https://github.com/Symplify/Symplify/pull/1037/files
        [$vendor,] = explode('/', $rootComposerJson['name']);

        $this->interdependencyUpdater->updateFileInfosWithVendorAndVersion(
            $this->composerJsonProvider->getPackagesComposerJsonFileInfos(),
            $vendor,
            $version
        );

        $this->symfonyStyle->success(sprintf('Inter-dependencies of packages were updated to "%s".', $version));

        // success
        return 0;
    }
}
