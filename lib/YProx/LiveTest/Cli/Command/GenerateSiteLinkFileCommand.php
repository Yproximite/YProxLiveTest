<?php

namespace YProx\LiveTest\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use YProx\LiveTest\SiteParser;

class GenerateSiteLinkFileCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('platformMapURL', InputArgument::REQUIRED);
        $this->addArgument('baseUrl', InputArgument::REQUIRED);
        $this->addArgument('outfile', InputArgument::REQUIRED);
        $this->setName('generate:site-link-file');
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outfile = $input->getArgument('outfile');
        $baseUrl = $input->getArgument('baseUrl');
        $outhandle = fopen($outfile, 'w');

        $url = $input->getArgument('platformMapURL');
        $dom = new \DOMDocument(1.0);
        $dom->load($url);
        $xpath = new \DOMXPath($dom);
        $domSites = $xpath->query('//site[@isBaseSite="no" and @billingStatus!="test"]');

        foreach ($domSites as $domSite) {
            $url = 'site.php?site_id='.$domSite->getAttribute('id');
            fwrite($outhandle, $url."\n");
            $res = $this->curlget($baseUrl.'/'.$url);
            $parser = new SiteParser($baseUrl);
            if ($res['content']) {
                $links = $parser->getLinks($res['content']);

                $urlChecks = array();
                foreach ($links as $link) {
                    $link = str_replace('site.php/', '', $link);
                    fwrite($outhandle, 'site.php'.$link."?site_id=".$domSite->getAttribute('id')."\n");
                }
            }
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

