<?php

namespace YProx\LiveTest\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSiteSetFileCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('platformMapURL', InputArgument::REQUIRED);
        $this->addArgument('outfile', InputArgument::REQUIRED);
        $this->addOption('randomize', true, InputOption::VALUE_NONE, 'Select random set of sites, use with random set size');
        $this->addOption(
            'limit', 
            null,
            InputOption::VALUE_REQUIRED, 
            'Limit size of set'
        );
        $this->setName('generate:site-set-file');
        $this->setDescription('Generates a file which contains a set of sites for testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('platformMapURL');
        $dom = new \DOMDocument(1.0);
        $dom->load($url);
        $xpath = new \DOMXPath($dom);
        $domSites = $xpath->query('//site[@isBaseSite="no" and @billingStatus!="test"]');
        $sDataset = array();
        $doRandomize = $input->getOption('randomize');
        $doLimit = $input->getOption('limit');
        $outfile = $input->getArgument('outfile');

        foreach ($domSites as $domSite) {
            $sData = array();
            foreach ($domSite->attributes as $attribute) {
                $sData[$attribute->nodeName] = $attribute->nodeValue;
            }
            $sDataset[] = $sData;
        }
        
        if ($doRandomize) {
            shuffle($sDataset);
        }

        if ($doLimit) {
            $sDataset = array_slice($sDataset, 0, $doLimit);
        }

        $outhandle = fopen($outfile, 'w');

        foreach ($sDataset as $sData) {
            fwrite($outhandle, json_encode($sData)."\n");
        }

        fclose($outhandle);
    }
}
