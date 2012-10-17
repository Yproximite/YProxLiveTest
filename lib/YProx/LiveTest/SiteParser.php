<?php

namespace YProx\LiveTest;

class SiteParser
{
    protected $baseUrl;

    public function __cosntruct($baseUrl, $sData)
    {
        $this->baseUrl = $baseUrl;
        $this->sData = $sData;
    }

    public function getLinks($html)
    {
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
        preg_match_all('&'.$regexp.'&siU', $html, $matches);
        var_dump($matches);
        return array();
    }

    public function getDOMLinks($html)
    {
        $dom = new \DOMDocument(1.0);
        $dom->loadHtml($html);
        $xpath = new \DOMXPath($dom);
        $anchors = $xpath->query('//a');
        $links = array();
     
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            echo $href."\n";
            $ext = preg_match('&\.(.*?)$&', $href, $matches);

            if (isset($matches[1])) {
                if ($matches[1] != 'html') {
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
            if (preg_match('&^https?&', $href)) {
                continue;
            }

            $links[] = $href;
        }

        return $links;
    }
}
