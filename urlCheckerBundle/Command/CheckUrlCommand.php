<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Kaliop\Queueing\Demos\UrlCheckerBundle\Entity\CheckedUrl;

class CheckUrlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:demo:checkurl')
            ->setDescription("Checks an URL for validity")
            ->addArgument('url', InputArgument::REQUIRED, 'The url to check (string)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $checkTime = time();

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true
        ));

        $time = microtime(true);
        $out = curl_exec($ch);
        $time = microtime(true) - $time;
        if ($out !== false) {
            $out = 0;
        } else {
            $out = curl_errno($ch);
        }

        // nb: this is open to race conditions. Wish Doctrine ORM had upserts...
        $em = $this->getContainer()->get('doctrine')->getManager();
        $checked = $em->find('KaliopQueueingDemosUrlCheckerBundle:CheckedUrl', md5($url));
        if ($checked == null) {
            $checked = new CheckedUrl($url, $out, $checkTime);
            $em->persist($checked);
        } else {
            $checked->setStatus($out);
            $checked->setCheckDate($checkTime);
        }
        $em->flush();

        $output->writeln(sprintf('One URL checked in %.3f secs', $time));
    }
}