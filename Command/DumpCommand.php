<?php

namespace Bazinga\Bundle\JsTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * @author Adrien Russo <adrien.russo.qc@gmail.com>
 */
class DumpCommand extends ContainerAwareCommand
{
    private $targetPath;

    private $jsonpCallback;

    /**
     * @var array
     */
    private $formats;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('bazinga:js-translation:dump')
            ->setDefinition(array(
                new InputArgument(
                    'target',
                    InputArgument::OPTIONAL,
                    'Override the target directory to dump JS translation files in.'
                ),
                new InputOption(
                    'formats',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Comma-separated list of formats to be used during dump.'
                ),
                new InputOption(
                    'jsonp-callback',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'JSONP callback expression used during dump (default is "callback").'
                ),
            ))
            ->setDescription('Dumps all JS translation files to the filesystem');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->targetPath = $input->getArgument('target') ?:
            sprintf('%s/../web/js', $this->getContainer()->getParameter('kernel.root_dir'));

        $formatsString = $input->getOption('formats');
        $this->formats = empty($formatsString) ? [] : explode(',', $formatsString);

        $this->jsonpCallback = $input->getOption('jsonp-callback') ?: 'callback';
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_dir($dir = dirname($this->targetPath))) {
            $output->writeln('<info>[dir+]</info>  ' . $dir);
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException('Unable to create directory ' . $dir);
            }
        }

        $output->writeln(sprintf(
            'Installing translation files in <comment>%s</comment> directory',
            $this->targetPath
        ));

        $this
            ->getContainer()
            ->get('bazinga.jstranslation.translation_dumper')
            ->dump($this->targetPath, [
                'formats' => $this->formats,
                'jsonp_callback' => $this->jsonpCallback,
            ]);
    }
}
