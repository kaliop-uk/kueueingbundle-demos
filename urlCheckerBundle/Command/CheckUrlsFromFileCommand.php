<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CheckUrlsFromFileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:demo:checkurlsfromfile')
            ->setDescription("Checks a list of URLs for validity")
            ->addArgument('file', InputArgument::REQUIRED, 'The file with the list of urls to check (string)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = file($input->getArgument('file'), FILE_IGNORE_NEW_LINES);
        $em = $this->getContainer()->get('doctrine')->getManager();

        foreach($urls as $url) {
            $time = time();
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_CUSTOMREQUEST => 'HEAD',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true
            ));
            $out = curl_exec($ch);

            if ($out !== false) {
                $out = 0;
            } else {
                $out = curl_errno($ch);
            }

            $checked = $em->find('Kaliop\Queueing\Demos\UrlCheckerBundle\Entity\CheckedUrl', md5($url));
            if ($checked == null) {
                $checked = new CheckedUrl($url, $out, $time);
                $em->persist($checked);
            } else {
                $checked->setStatus($out);
                $checked->setCheckDate($time);
            }
            $em->flush();
        }
    }
}
