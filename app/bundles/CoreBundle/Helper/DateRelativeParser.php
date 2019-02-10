<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class DateRelativeParser
{
    /**
     * @var array
     */
    private $dictionary;

    /** @var string */
    private $timeframe;

    /**
     * @var string
     */
    private $prefix;

    /**
     * DateRelativeParser constructor.
     *
     * @param array  $dictionary
     * @param string $timeframe
     * @param string $prefix
     */
    public function __construct(array $dictionary, $timeframe, $prefix = '')
    {
        $this->dictionary = $dictionary;
        $this->timeframe  = str_replace($prefix, '', $timeframe);
    }

    /**
     * @return bool
     */
    public function hasRelativeDate()
    {
        return in_array($this->getRelativeString(), $this->getDictionaryVariants());
    }

    /**
     * Return timeframe.
     *
     * @return string
     */
    private function getRelativeString()
    {
        return trim(str_replace($this->getRelativeDate(), '', $this->timeframe));
    }

    /**
     * Return all after /birthday string, for example -1 day.
     *
     * @return string
     */
    public function getRelativeDate()
    {
        return trim(str_replace($this->getDictionaryVariants(), '', $this->timeframe));
    }

    /**
     * Return all possible variants for dates.
     *
     * @return array
     */
    private function getDictionaryVariants()
    {
        return array_merge($this->dictionary, array_keys($this->dictionary));
    }
}
