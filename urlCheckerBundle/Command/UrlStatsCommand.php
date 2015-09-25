<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Kaliop\Queueing\Demos\UrlCheckerBundle\Entity\CheckedUrl;

class UrlStatsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:demo:urlstats')
            ->setDescription("Manages the collected data about valid urls")
            ->addArgument('action', InputArgument::OPTIONAL, 'The action to execute: reset or display', 'display');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qb = $this->getContainer()->get('doctrine')->getManager()->createQueryBuilder();

        switch($input->getArgument('action')) {
            case 'reset':
                $qb->delete()
                    ->from('KaliopQueueingDemosUrlCheckerBundle:CheckedUrl', 'u');
                $qb->getQuery()->execute();

                $output->writeln('Checked URLs have been reset');
                break;

            case 'display':
            default:
                $qb->select('min(u.checkDate)', 'max(u.checkDate)', 'count(u)')
                    ->from('KaliopQueueingDemosUrlCheckerBundle:CheckedUrl', 'u');
                $data = $qb->getQuery()->getResult();

                $output->writeln(sprintf('%d URLs checked in %.3f secs', $data[0][3], $data[0][2] - $data[0][1]));
            break;
        }
    }
}