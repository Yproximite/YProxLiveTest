<?php

namespace YProx\LiveTest\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use YProx\LiveTest\SiteParser;

class GenerateSiteHashFileCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('baseUrl', InputArgument::REQUIRED);
        $this->addArgument('infile', InputArgument::REQUIRED);
        $this->addArgument('outfile', InputArgument::REQUIRED);
        $this->setName('generate:site-hash-file');
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile = $input->getArgument('infile');
        $outfile = $input->getArgument('outfile');
        $baseUrl = $input->getArgument('baseUrl');

        if (!file_exists($infile)) {
            $output->writeln('<error>File '.$infile.' does not exist');
            die(1);
        }

        $sDataset = array();
        $outhandle = fopen($outfile, 'w');

        $inhandle = fopen($infile, 'r');

        while ($line = fgets($inhandle)) {
            $sData = json_decode($line, true);

            $url = $baseUrl.'/site.php?site_id='.$sData['id'];

            $output->writeln('Checking site: '.$url);

            $res = $this->curlget($url);

            $hash = md5($res['content']);
            $sData['respHash'] = $hash;
            $sData['respHttpCode'] = $res['info']['http_code'];

            $parser = new SiteParser($baseUrl, $sData);

            if ($res['info']['http_code'] == 200) {
                $output->writeln('<info>HTTP Code: 200</info>');
            } else {
                $output->writeln('<error>HTTP Code: '.$res['info']['http_code'].'</error>');
            }

            if (!$res['content']) {
                $output->writeln('<error>No content found</error>');
            } else {
            }

            if ($res['content']) {
                $links = $parser->getLinks($res['content']);

                $urlChecks = array();
                foreach ($links as $link) {
                    $absUrl = $url.$link;
                    $output->writeln('-- Getting link: '.$absUrl);
                    $res = $this->curlget($absUrl);
                    $hash = md5($res['content']);
                    $urlCheck = array();
                    $urlCheck['hash'] = $hash;
                    $urlCheck['httpCode'] = $res['info']['http_code'];
                    $urlChecks[$link] = $urlCheck;
                }

                $sData['urls'] = $urlChecks;
            }

            fwrite($outhandle, json_encode($sData)."\n");
        }
    }

    protected function curlget($url)
    {
        $code = 200;
        $response = null;

        try {
            $response = file_get_contents($url);
        } catch (\ErrorException $e) {
            preg_match('&HTTP.*([0-9]{3})&', $e->getMessage(), $matches);
            $code = $matches[1];
        }

        $resp = array(
            'content' => $response,
            'info' => array('http_code' => $code),
        );
        return $resp;
    }
}
