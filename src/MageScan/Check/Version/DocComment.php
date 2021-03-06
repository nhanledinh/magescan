<?php
/**
 * Mage Scan
 *
 * PHP version 5
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */

namespace MageScan\Check\Version;

use GuzzleHttp\Psr7\Response;
use MageScan\Check\AbstractCheck;
use MageScan\Check\Version;

/**
 * Scan for Magento edition and version via doc block style comment
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */
class DocComment extends AbstractCheck
{
    /**
     * Guess magento edition and version
     *
     * @return array|boolean
     */
    public function getInfo()
    {
        $response = $this->getRequest()->get('js/varien/product.js');
        $edition = $this->getEdition($response);
        if ($edition) {
            $version = $this->getVersion($response, $edition);
            return [$edition, $version];
        }
        return false;
    }

    /**
     * Guess Magento edition from license in public file
     *
     * @param Response $response
     *
     * @return string|boolean
     */
    public function getEdition(Response $response)
    {
        if ($response->getStatusCode() == 200) {
            preg_match('/@license.*/', $response->getBody(), $match);
            if (isset($match[0])) {
                if (strpos($match[0], 'enterprise') !== false) {
                    return Version::EDITION_ENTERPRISE;
                } elseif (strpos($match[0], 'commercial') !== false) {
                    return Version::EDITION_PROFESSIONAL;
                }
                return Version::EDITION_COMMUNITY;
            }
        }
        return false;
    }

    /**
     * Guess Magento version from copyright in public file
     *
     * @param Response       $response
     * @param string|boolean $edition
     *
     * @return string|boolean
     */
    public function getVersion(Response $response, $edition)
    {
        if ($response->getStatusCode() == 200 && $edition != false) {
            preg_match('/@copyright.*/', $response->getBody(), $match);
            if (isset($match[0])
                && preg_match('/[0-9-]{4,}/', $match[0], $match)
                && isset($match[0])
            ) {
                return $this->getMagentoVersionByYear($match[0], $edition);
            }
        }
        return false;
    }

    /**
     * Guess Magento version from copyright year and edition
     *
     * @param string $year
     * @param string $edition
     *
     * @return string
     */
    protected function getMagentoVersionByYear($year, $edition)
    {
        switch ($year) {
            case '2006-2015':
            case '2006-2014':
            case '2014':
                return $edition == Version::EDITION_ENTERPRISE ?
                    '1.14' : '1.9';
            case 2013:
                return $edition == Version::EDITION_ENTERPRISE ?
                    '1.13' : '1.8';
            case 2012:
                return ($edition == Version::EDITION_ENTERPRISE || $edition == Version::EDITION_PROFESSIONAL) ?
                    '1.12' : '1.7';
            case 2011:
                return ($edition == Version::EDITION_ENTERPRISE || $edition == Version::EDITION_PROFESSIONAL) ?
                    '1.11' : '1.6';
            case 2010:
                return ($edition == Version::EDITION_ENTERPRISE || $edition == Version::EDITION_PROFESSIONAL) ?
                    '1.9 - 1.10' : '1.4 - 1.5';
        }
        return 'Unknown';
    }
}
