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

        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->persist(new CheckedUrl($url, $out));
        $manager->flush();
    }
}