<?php
/**
 * Created by PhpStorm.
 * User: piotr
 * Date: 14.06.2017
 * Time: 16:59
 */

namespace piotrmus\urlparser;


/**
 * Class ParsedUrl
 * @package piotrmus\urlparser
 */
class ParsedUrl
{
    /**
     * @var
     */
    public $url;
    /**
     * @var
     */
    public $topDomain;
    /**
     * @var
     */
    public $domain;
    /**
     * @var
     */
    public $subdomains;
}