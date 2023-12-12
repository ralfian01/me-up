<?php

namespace AppConfig;

use MVCME\Config\UserAgents as ConfigUserAgents;

/**
 * User Agents
 */
class UserAgents extends ConfigUserAgents
{
    // /**
    //  * OS Platforms
    //  * @var array<string, string>
    //  */
    // public array $platforms = [
    //     'windows nt 10.0' => 'Windows 10',
    //     'android'         => 'Android',
    //     'iphone'          => 'iOS',
    //     'ipad'            => 'iOS',
    //     'linux'           => 'Linux',
    //     'debian'          => 'Debian',
    //     'openbsd'         => 'OpenBSD',
    //     'unix'            => 'Unknown Unix OS',
    //     'symbian'         => 'Symbian OS',
    // ];

    // /**
    //  * Browsers
    //  * @var array<string, string>
    //  */
    // public array $browsers = [
    //     'OPR'    => 'Opera',
    //     'Flock'  => 'Flock',
    //     'Edge'   => 'Spartan',
    //     'Edg'    => 'Edge',
    //     'Chrome' => 'Chrome',
    //     'Internet Explorer' => 'Internet Explorer',
    //     'Firefox'           => 'Firefox',
    //     'Safari'            => 'Safari',
    //     'Mozilla'           => 'Mozilla',
    // ];

    // /**
    //  * Mobiles
    //  * @var array<string, string>
    //  */
    // public array $mobiles = [
    //     'motorola'             => 'Motorola',
    //     'nokia'                => 'Nokia',
    //     'palm'                 => 'Palm',
    //     'iphone'               => 'Apple iPhone',
    //     'ipad'                 => 'iPad',
    //     'ipod'                 => 'Apple iPod Touch',
    //     'sony'                 => 'Sony Ericsson',
    //     'ericsson'             => 'Sony Ericsson',
    //     'blackberry'           => 'BlackBerry',
    //     'cocoon'               => 'O2 Cocoon',
    //     'blazer'               => 'Treo',
    //     'lg'                   => 'LG',
    //     'amoi'                 => 'Amoi',
    //     'xda'                  => 'XDA',
    //     'mda'                  => 'MDA',
    //     'vario'                => 'Vario',
    //     'htc'                  => 'HTC',
    //     'samsung'              => 'Samsung',
    //     'sharp'                => 'Sharp',
    //     'sie-'                 => 'Siemens',
    //     'alcatel'              => 'Alcatel',
    //     'benq'                 => 'BenQ',
    //     'ipaq'                 => 'HP iPaq',
    //     'mot-'                 => 'Motorola',
    //     'playstation portable' => 'PlayStation Portable',
    //     'playstation 3'        => 'PlayStation 3',
    //     'playstation vita'     => 'PlayStation Vita',
    //     'hiptop'               => 'Danger Hiptop',
    //     'nec-'                 => 'NEC',
    //     'panasonic'            => 'Panasonic',
    //     'philips'              => 'Philips',
    //     'sagem'                => 'Sagem',
    //     'sanyo'                => 'Sanyo',
    //     'spv'                  => 'SPV',
    //     'zte'                  => 'ZTE',
    //     'sendo'                => 'Sendo',
    //     'nintendo dsi'         => 'Nintendo DSi',
    //     'nintendo ds'          => 'Nintendo DS',
    //     'nintendo 3ds'         => 'Nintendo 3DS',
    //     'wii'                  => 'Nintendo Wii',
    //     'open web'             => 'Open Web',
    //     'openweb'              => 'OpenWeb',

    //     // Operating Systems
    //     'android'    => 'Android',
    //     'symbian'    => 'Symbian',
    //     'SymbianOS'  => 'SymbianOS',
    //     'elaine'     => 'Palm',
    //     'series60'   => 'Symbian S60',
    //     'windows ce' => 'Windows CE',

    //     // Browsers
    //     'obigo'         => 'Obigo',
    //     'netfront'      => 'Netfront Browser',
    //     'openwave'      => 'Openwave Browser',
    //     'mobilexplorer' => 'Mobile Explorer',
    //     'operamini'     => 'Opera Mini',
    //     'opera mini'    => 'Opera Mini',
    //     'opera mobi'    => 'Opera Mobile',
    //     'fennec'        => 'Firefox Mobile',

    //     // Other
    //     'digital paths' => 'Digital Paths',
    //     'avantgo'       => 'AvantGo',
    //     'xiino'         => 'Xiino',
    //     'novarra'       => 'Novarra Transcoder',
    //     'vodafone'      => 'Vodafone',
    //     'docomo'        => 'NTT DoCoMo',
    //     'o2'            => 'O2',

    //     // Fallback
    //     'mobile'     => 'Generic Mobile',
    //     'wireless'   => 'Generic Mobile',
    //     'j2me'       => 'Generic Mobile',
    //     'midp'       => 'Generic Mobile',
    //     'cldc'       => 'Generic Mobile',
    //     'up.link'    => 'Generic Mobile',
    //     'up.browser' => 'Generic Mobile',
    //     'smartphone' => 'Generic Mobile',
    //     'cellphone'  => 'Generic Mobile',
    // ];

    // /**
    //  * Robots
    //  * There are hundred of bots but these are the most common
    //  * @var array<string, string>
    //  */
    // public array $robots = [
    //     'googlebot'            => 'Googlebot',
    //     'msnbot'               => 'MSNBot',
    //     'baiduspider'          => 'Baiduspider',
    //     'bingbot'              => 'Bing',
    //     'slurp'                => 'Inktomi Slurp',
    //     'yahoo'                => 'Yahoo',
    //     'ask jeeves'           => 'Ask Jeeves',
    //     'fastcrawler'          => 'FastCrawler',
    //     'infoseek'             => 'InfoSeek Robot 1.0',
    //     'lycos'                => 'Lycos',
    //     'yandex'               => 'YandexBot',
    //     'mediapartners-google' => 'MediaPartners Google',
    //     'CRAZYWEBCRAWLER'      => 'Crazy Webcrawler',
    //     'adsbot-google'        => 'AdsBot Google',
    //     'feedfetcher-google'   => 'Feedfetcher Google',
    //     'curious george'       => 'Curious George',
    //     'ia_archiver'          => 'Alexa Crawler',
    //     'MJ12bot'              => 'Majestic-12',
    //     'Uptimebot'            => 'Uptimebot',
    // ];
}
