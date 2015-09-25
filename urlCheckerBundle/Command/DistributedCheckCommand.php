<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Kaliop\Queueing\Demos\UrlCheckerBundle\Entity\CheckedUrl;

class DistributedCheckCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:demo:distributedcheck')
            ->setDescription("Checks a list of URLs for validity using the queueing system")
            ->addArgument('queue_name', InputArgument::REQUIRED, 'The queue name (string)')
            ->addArgument('urls', InputArgument::IS_ARRAY, 'The urls to check (separated by spaces)')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'A file with a list of urls to check (one per line)')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'The number of urls to batch in one message (default: 1)', 1)
            ->addOption('driver', 'i', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batchSize = $input->getOption('batch-size');
        $urls = $input->getArgument('urls');
        if (($filename = $input->getOption('file')) != '') {
            $urls = array_merge($urls, file($filename, FILE_IGNORE_NEW_LINES));
        }

        $driverName = $input->getOption('driver');
        $driver = $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        $queue = $input->getArgument('queue_name');
        $messageProducer = $this->getContainer()->get('kaliop_queueing.message_producer.console_command');
        $messageProducer->setDriver($driver)->setQueueName($queue);

        $time = microtime(true);
        for($done = 0; $done < count($urls); $done += $batchSize) {
            $messageProducer->publish(
                'kaliop_queueing:demo:checkurls',
                array_slice($urls, $done, $batchSize),
                array()
            );
        }
        $time = microtime(true) - $time;

        $output->writeln(sprintf('Queued %3d URLs to be checked in %.3f secs', count($urls), $time));
    }
}
