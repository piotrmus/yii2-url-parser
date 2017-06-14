<?php

namespace piotrmus\urlparser;

use Yii;
use yii\base\Component;

class Parser extends Component
{
    public static $suffixListUrl = 'https://publicsuffix.org/list/public_suffix_list.dat';

    public static function getResourceDirectory()
    {
        return Yii::getAlias('@app') . '/runtime/hostname-parser';
    }

    public static function getCacheFile()
    {
        return self::getResourceDirectory() . '/cache.ser';
    }

    private static function downloadSuffixList()
    {
        $listString = file_get_contents(self::$suffixListUrl);
        $re = '/^[^\/\/\n]{2,}$/m';
        $str = $listString;
        preg_match_all($re, $str, $domains, PREG_PATTERN_ORDER, 0);
        $domains = self::parseDomainList($domains[0]);
        return $domains;
    }

    private static function parseDomainList($domains)
    {
        $parsed = [];

        foreach ($domains as $domain) {
            $currentArr = &$parsed;
            $parsedDomain = array_reverse(split('\.', $domain));
            for ($deep = 0; $deep < count($parsedDomain); $deep++) {
                if ($parsedDomain[$deep] == '*') {
                    continue;
                }
                if (!isset($currentArr[$parsedDomain[$deep]])) {
                    $currentArr[$parsedDomain[$deep]] = [];
                }
                $currentArr = &$currentArr[$parsedDomain[$deep]];
            }
        }

        return $parsed;
    }

    private static function saveCache($domainList)
    {
        if (!file_exists(self::getResourceDirectory())) {
            mkdir(self::getResourceDirectory());
        }
        $data = [
            'create_time' => time(),
            'data' => $domainList
        ];
        file_put_contents(self::getCacheFile(), serialize($data));
    }

    public static function createCache()
    {
        $domainList = self::downloadSuffixList();
        self::saveCache($domainList);
        return $domainList;
    }

    public static function cacheExist()
    {
        return file_exists(self::getCacheFile());
    }

    public static function getDomainList()
    {
        if (self::cacheExist()) {
            $data = unserialize(file_get_contents(self::getCacheFile()));
            return $data['data'];
        }
        return self::createCache();
    }

    public static function parse($hostname)
    {
        if (!preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $hostname)) {
            return null;
        }

        $domainNamesList = self::getDomainList();
        $parsedHostname = array_reverse(split('\.', $hostname));
        $host = [];
        $domainNames = [];
        $currentArray = &$domainNamesList;
        for ($deep = 0; $deep < count($parsedHostname); $deep++) {
            if (isset($currentArray[$parsedHostname[$deep]])) {
                $domainNames[] = $parsedHostname[$deep];
                $currentArray = $currentArray[$parsedHostname[$deep]];
            } else {
                $host[] = $parsedHostname[$deep];
            }
        }

        if (count($host) == 0) {
            $host[] = $domainNames[count($domainNames) - 1];
            unset($domainNames[count($domainNames) - 1]);
        }

        $topDomain = implode('.', array_reverse($domainNames));
        $mainDomain = $host[0] . '.' . $topDomain;

        return [
            'domain' => $mainDomain,
            'topDomain' => $topDomain,
            'subDomains' => $host,
        ];
    }
}