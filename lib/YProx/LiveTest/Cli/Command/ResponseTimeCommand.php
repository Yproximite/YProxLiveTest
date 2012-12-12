<?php

namespace YProx\LiveTest\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResponseTimeCommand extends Command
{
    protected $output;
    protected $verbose = false;
    protected $checkedHosts = array();
    protected $fails = array();

    protected $writeResponseHandle;
    protected $writeErrorsHandle;

    protected $validResponses = array(
        '200'
    );

    protected $badWords = array(
        'Fatal',
    );

    protected $checks = 0;

    protected function configure()
    {
        $this->addArgument('platformMapURL', InputArgument::REQUIRED);
        $this->addOption('write-errors', null, InputOption::VALUE_REQUIRED, 'Write errors to specified file');
        $this->addOption('write-response-times', null, InputOption::VALUE_REQUIRED, 'Write errors to specified file');
        $this->addOption('use-base-url', false, InputOption::VALUE_REQUIRED, 'Use base URL and Site IDs rather than hosts');
        $this->addOption('host-filter', null, InputOption::VALUE_REQUIRED, 'Reg ex filter for hostname');
        $this->setName('test:response-time');
        $this->setDescription('Loads each page of a platform and checks for errors');
//        $this->setDescription('Given a URL to a platformmap.xml document, for each site this script will find the navigation and click each link - checking for errors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $this->output = $output;
        $url = $input->getArgument('platformMapURL');
        $dom = new \DOMDocument(1.0);
        $dom->load($url);
        $xpath = new \DOMXPath($dom);
        $domSites = $xpath->query('//site[@isBaseSite="no" and @billingStatus!="test"]');

        if ($writeResponseTimesFname = $input->getOption('write-response-times')) {
            $this->writeResponseHandle = fopen($writeResponseTimesFname, 'w');
        }

        if ($writeErrorsFname = $input->getOption('write-errors')) {
            $this->writeErrorsHandle = fopen($writeErrorsFname, 'w');
        }

        $hostFilter = $input->getOption('host-filter');

        $this->useBaseUrl = $input->getOption('use-base-url');

        $this->verbose = $input->getOption('verbose');

        foreach ($domSites as $i => $domSite) {

            if ($hostFilter) {
                if (!preg_match($hostFilter, $domSite->getAttribute('host'))) {
                    continue;
                }
            }

            $this->output->writeln('Checking site '.($i + 1).'/'.$domSites->length.' : '.$domSite->getAttribute('host').' ('.$this->useBaseUrl.')');

            if ($stats = $this->checkSite($domSite)) {
                // only crawl links if status is 200
                if ($stats['status'] == 'HTTP/1.0 200 OK' ||
                $stats['status'] == 'HTTP/1.1 200 OK') {
                    $this->crawl($domSite, $stats);
                }
            }
        }

        if ($this->fails) {
            foreach ($this->fails as $fail) {
                $this->output->writeln('FAIL: '.$fail['url'].' - ['.$fail['status'].'] '.$fail['message']);
            }
        } else {
            $this->output->writeln('All OK');
        }

        if ($this->writeResponseHandle) {
            fclose($this->writeResponseHandle);
        }

        if ($this->writeErrorsHandle) {
            fclose($this->writeErrorsHandle);
        }

        $end = microtime(true) - $start;
        $output->writeln('');
        $output->writeln('<info>Done ('.number_format($end, 2).' seconds)</info>');
    }

    protected function crawl($domSite, $stats)
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument(1.0);
        $dom->loadHtml($stats['content']);
        $xpath = new \DOMXPath($dom);
        $navLinks = $xpath->query('//a');
     
        foreach ($navLinks as $navLink) {
            $href = $navLink->getAttribute('href');
            $ext = preg_match('&\.(.*?)$&', $href, $matches);

            if (isset($matches[1])) {
                if ($matches[1] != 'html') {
                    continue;
                }
            }

            if ($href == '/') {
                continue; // we have already checked
            }

            if (trim($href) == '#') {
                continue;
            }

            if (trim($href) == '') {
                continue;
            }

            // check only pages in this domain (assuming no absolute links)
            if (!preg_match('&^https?&', $href)) {
                $this->output->writeln(' -- checking linked page '.$href);
                $this->checkSite($domSite, $href);
            }
        }
    }

    protected function checkSite($domSite, $link = '')
    {
        if (in_array($domSite->getAttribute('host').$link, $this->checkedHosts)) {
            return array();
        }

        $url = $this->getBaseUrl($domSite, $link);

        $stats = $this->fetchUrl($url);
        $displayStats = $stats;
        unset($displayStats['content']);
        if ($this->verbose) {
        }
        $htmlSource = $stats['content'];

        // ########### checks

        // check for closing html tag
        # if (preg_match('&.*<html>.*&', $htmlSource)) {
        #     if (!preg_match('&.*</html>.*&', $htmlSource)) {
        #         $this->fail($url, 'No closing HTML tag');
        #     }
        # }

        if (!in_array($stats['status'], array('HTTP/1.0 200 OK', 'HTTP/1.0 302 Found','HTTP/1.1 200 OK', 'HTTP/1.1 302 Found'))) {
            if (preg_match('&500&', $stats['status'])) {
                if (preg_match('&<h1>(.*)</h1>&', $stats['content'], $matches)) {
                    $error = strip_tags($matches[1]);
                    $this->fail($url, $stats['status'], $error);
                }
                
            } else {
                $this->fail($url, $stats['status'], 'Fail');
            }
        } else {
//            $this->output->writeln('<info>'.$stats['status'].'</info>');
        }

        // write stats
        if ($this->writeResponseHandle) {
            $this->writeResponseTime($url, $stats);
        }

        $this->checkedHosts[] = $domSite->getAttribute('host').$link;

        return $stats;
    }

    protected function fail($url, $status, $message)
    {
        $this->fails[] = array('url' => $url, 'status' => $status, 'message' => $message);
        $this->output->writeln('<error>'.$url.': ['.$status.'] '.$message.'</error>');

        if ($this->writeErrorsHandle) {
            fwrite($this->writeErrorsHandle, sprintf(sprintf("%s : %s\n", $url, $message)));
        }
    }

    protected function writeResponseTime($url, $stats)
    {
        fwrite($this->writeResponseHandle, sprintf(sprintf("%02.2f : %s\n", $stats['responseTime'], $url)));
    }

    private function fetchUrl($url)
    {
        $startTime = microtime(true);
        $ch = curl_init();

        if ($this->verbose) {
            $this->output->writeln($url);
        }

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11');

        // Only calling the head
        curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output

        if ($this->verbose) {
            $this->output->writeln('<info>Making request</info>');
        }
        $content = curl_exec ($ch);
        curl_close ($ch);

        if ($this->verbose) {
            $this->output->writeln('<info>Request made</info>');
        }

        $endTime = microtime(true);

        $content = explode("\n", $content);
        $headers = array();
        $status = array_shift($content);

        while (preg_match('&(.*?):(.*)&', array_shift($content), $matches)) {
            $headers[$matches[1]] = $matches[2];
        }

        $responseTime = number_format($endTime - $startTime, 2);

        $stats['status'] = trim($status);
        $stats['responseTime'] = $responseTime;
        $stats['url'] = $url;
        $stats['headers'] = $headers;
        $stats['content'] = implode("\n", $content);

        return $stats;
    }

    protected function getBaseUrl($domSite, $link = '')
    {
        $url = $this->useBaseUrl;
        if (!$url) {
            $url = $domSite->getAttribute('host');
        }

        if (!preg_match('&^(http|https)://&', $url)) {
            $url = 'http://'.$url;
        }

        return sprintf('%s%s?site_id=%d&icare',
            $url,
            $link,
            $domSite->getAttribute('id')
        );
    }
}


