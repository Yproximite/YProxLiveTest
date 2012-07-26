<?php

namespace YProx\LiveTest\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use Symfony\Bundle\FrameworkBundle\Util\Mustache;
use Ylly\CmsBundle\Site\Importer\SiteImporter;
use Symfony\Component\HttpKernel\Util\Filesystem;

class ScreenshotCommand extends Command
{
    protected $output;
    protected $fails = array();
    protected $host = '127.0.0.1';
    protected $port = '4444';
    protected $outputPath;

    protected function configure()
    {
        $this->addArgument('platformMapURL', InputArgument::REQUIRED);
        $this->addArgument('outputPath', InputArgument::REQUIRED);

        $this->addOption('use-base-url', false, InputOption::VALUE_REQUIRED, 'Use base URL and Site IDs rather than hosts');
        $this->addOption('limit', null, null, 'Set screenshot limit (for testing)');
        $this->setName('test:screenshots');
        $this->setDescription('Loads first page of each site in map and take a screenshot');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $this->output = $output;
        $domOut = new \DOMDocument("1.0");
        $outEl = $domOut->createElement('screenshots');
        $outEl->setAttribute('date', date('c'));
        $domOut->appendChild($outEl);
        $limit = $input->getOption('limit');

        $this->outputPath = $input->getArgument('outputPath');

        if (substr($this->outputPath, 0, 1) != '/') {
            $this->outputPath = __DIR__.'/../../../../../'.$this->outputPath;
        }

        $output->writeln('<info>Writing files to '.$this->outputPath.'</info>');

        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath);
        }

        $url = $input->getArgument('platformMapURL');
        $dom = new \DOMDocument(1.0);
        $dom->load($url);
        $xpath = new \DOMXPath($dom);
        $domSites = $xpath->query('//site[@billingStatus!="test"]');

        if ($domSites->length == 0) {
            $output->writeln('<error>No sites found in platform map</error>');
            return;
        }

        $this->useBaseUrl = $input->getOption('use-base-url');
        $this->verbose = $input->getOption('verbose');

        $session = $this->doCommand('getNewBrowserSession', array('firefox', 'http://127.0.0.1'));
        $this->doCommand('setTimeout', array(30 * 1000));

        $parts = explode(',', $session);
        if ($parts[0] == 'OK') {
        } else {
            throw new \Exception('Could not start selenium session: '.implode(',', $parts));
        }
        $this->sessionId = $parts[1];

        foreach ($domSites as $i => $domSite) {
            if ($limit && $i > $limit) {
                break;
            }
            if ($baseUrl = $this->useBaseUrl) {
                $baseUrl = $baseUrl.'/?site_id='.$domSite->getAttribute('id').'&icare&_allow_base_site=1';
            } else {
                $baseUrl = 'http://'.$domSite->getAttribute('host');
            }

            $this->output->writeln('Checking site '.($i + 1).'/'.$domSites->length.' : '.$domSite->getAttribute('host').' ('.$baseUrl.')');
            $this->doCommand('open', array($baseUrl));
            $this->doCommand('waitForPageToLoad');
            $this->doCommand('captureEntirePageScreenshot', array($this->outputPath.'/site-'.$domSite->getAttribute('id').'.png'));
            $body = $this->doCommand('getHtmlSource');
            $html = substr($body, 3);
            $html = '<html>'.$html.'</html>';

            $screenEl = $domOut->createElement('screenshot');
            $screenEl->setAttribute('createdAt', date('c'));
            $screenEl->setAttribute('index', $i + 1);
            $screenEl->setAttribute('id', $domSite->getAttribute('id'));
            $screenEl->appendChild($domOut->importNode($domSite, true));
            $outEl->appendChild($screenEl);
            $domOut->save($this->outputPath.'/screenshots.xml');
            $date = date('Y-m-d');
            $domOut->save($fname = $this->outputPath.'/screenshots-'.$date.'.xml');
            $output->writeln('<info>Written XML metadata to : </info>'.$fname);
        }

        $end = microtime(true) - $start;
        $output->writeln('<info>Closing browser instance ..</info>');
        $this->doCommand('testComplete');
        $output->writeln('');
        $output->writeln('<info>Done ('.number_format($end, 2).' seconds)</info>');
    }

    protected function doCommand($command, array $arguments = array())
    {
        $url = sprintf(
          'http://%s:%s/selenium-server/driver/?cmd=%s',
          $this->host,
          $this->port,
          urlencode($command)
        );

        $numArguments = count($arguments);

        for ($i = 0; $i < $numArguments; $i++) {
            $argNum = strval($i + 1);
            
            if($arguments[$i] == ' ') {
              $url .= sprintf('&%s=%s', $argNum, urlencode($arguments[$i]));
            } else {
              $url .= sprintf('&%s=%s', $argNum, urlencode(trim($arguments[$i])));
            }            
        }

        if (isset($this->sessionId)) {
            $url .= sprintf('&%s=%s', 'sessionId', $this->sessionId);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);

        $response = curl_exec($curl);
        $info     = curl_getinfo($curl);

        if (!$response) {
            throw new \RuntimeException(curl_error($curl));
        }

        curl_close($curl);

        if ($info['http_code'] != 200) {
            $this->stop();

            throw new \RuntimeException(
              'The response from the Selenium RC server is invalid: ' .
              $response
            );
        }

        return $response;
    }
}

