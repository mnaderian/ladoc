<?php

namespace Lo\Command;

use League\CommonMark\Exception\CommonMarkException;
use Lo\Enum\Version;
use Lo\FileManager;
use Lo\Index\IndexManager;
use Lo\InputResolver;
use Lo\Repository;
use Lo\Styles;
use Lo\Termwind;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'search'
)]
class MainCommand extends Command
{
    public function configure(): void
    {

        $this->addArgument(
            'section',
            InputArgument::OPTIONAL,
            'Section name',
            'list'
        );

        $this->addArgument(
            'query',
            InputArgument::IS_ARRAY,
            'Search string'
        );

        $this->addOption(
            'branch',
            'b',
            InputArgument::OPTIONAL,
            'Laravel version branch',
            Version::getLatestVersion()->value
        );

        $this->addOption(
            'letter',
            'l',
            InputArgument::OPTIONAL,
            'Filter Main list by letter',
            ''
        );
    }

    /**
     * @throws \Exception
     * @throws CommonMarkException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $query = $input->getArgument('query');
        $versionInput = $input->getOption('branch');


        $version = Version::fromValue($versionInput);

        $fileManager = new FileManager(
            $version,
            ROOT_APP . '/.docs',
            ROOT_APP . '/index',
        );

        $indexManager = new IndexManager($fileManager);

        if (!$indexManager->check()) {
            (new Repository($fileManager))->check();
            $indexManager->createIndex();
        }

        $inputResolver = new InputResolver($indexManager);

        $action = $inputResolver->resolve($section, $query);

        $content = $action->execute(
           $query,
           ['letter' => $input->getOption('letter')]
        );

        (new Termwind(
            new Styles(require ROOT_APP . '/styles.php')
        ))->render($content);

        return Command::SUCCESS;
    }

}
