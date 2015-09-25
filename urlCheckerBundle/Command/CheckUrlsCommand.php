<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Kaliop\Queueing\Demos\UrlCheckerBundle\Entity\CheckedUrl;

class CheckUrlsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:demo:checkurls')
            ->setDescription("Checks a list of URLs for validity")
            ->addArgument('urls', InputArgument::IS_ARRAY, 'The urls to check (separated by spaces)')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'A file with a list of urls to check (one per line)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = $input->getArgument('urls');
        if (($filename = $input->getOption('file')) != '') {
            $urls = array_merge($urls, file($filename, FILE_IGNORE_NEW_LINES));
        }
        $em = $this->getContainer()->get('doctrine')->getManager();

        $time = microtime(true);
        foreach($urls as $url) {
            $checkTime = time();
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true
            ));
            $out = curl_exec($ch);

            if ($out !== false) {
                $out = 0;
            } else {
                $out = curl_errno($ch);
            }

            $checked = $em->find('KaliopQueueingDemosUrlCheckerBundle:CheckedUrl', md5($url));
            if ($checked == null) {
                $checked = new CheckedUrl($url, $out, $checkTime);
                $em->persist($checked);
            } else {
                $checked->setStatus($out);
                $checked->setCheckDate($checkTime);
            }
            $em->flush();
        }
        $time = microtime(true) - $time;

        $output->writeln(sprintf('%d URLs checked in %.3f secs', count($urls), $time));
    }
}