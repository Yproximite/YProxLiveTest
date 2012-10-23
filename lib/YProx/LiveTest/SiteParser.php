<?php

namespace YProx\LiveTest;

class SiteParser
{
    public function __cosntruct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getLinks($html)
    {
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
        preg_match_all('&'.$regexp.'&siU', $html, $matches);
        $links = $this->filterLinks($matches[2]);
        return $links;
    }

    public function filterLinks($anchors)
    {

        $links = array();
        foreach ($anchors as $href) {
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
