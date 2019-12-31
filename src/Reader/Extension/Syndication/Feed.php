<?php

/**
 * @see       https://github.com/laminas/laminas-feed for the canonical source repository
 * @copyright https://github.com/laminas/laminas-feed/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-feed/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Feed\Reader\Extension\Syndication;

use DateTime;
use Laminas\Feed\Reader;
use Laminas\Feed\Reader\Extension;

class Feed extends Extension\AbstractFeed
{
    /**
     * Get update period
     *
     * @return string
     * @throws Reader\Exception\InvalidArgumentException
     */
    public function getUpdatePeriod()
    {
        $name = 'updatePeriod';
        $period = $this->getData($name);

        if ($period === null) {
            $this->data[$name] = 'daily';
            return 'daily'; //Default specified by spec
        }

        switch ($period) {
            case 'hourly':
            case 'daily':
            case 'weekly':
            case 'yearly':
                return $period;
            default:
                throw new Reader\Exception\InvalidArgumentException("Feed specified invalid update period: '$period'."
                    .  " Must be one of hourly, daily, weekly or yearly"
                );
        }
    }

    /**
     * Get update frequency
     *
     * @return int
     */
    public function getUpdateFrequency()
    {
        $name = 'updateFrequency';
        $freq = $this->getData($name, 'number');

        if (!$freq || $freq < 1) {
            $this->data[$name] = 1;
            return 1;
        }

        return $freq;
    }

    /**
     * Get update frequency as ticks
     *
     * @return int
     */
    public function getUpdateFrequencyAsTicks()
    {
        $name = 'updateFrequency';
        $freq = $this->getData($name, 'number');

        if (!$freq || $freq < 1) {
            $this->data[$name] = 1;
            $freq = 1;
        }

        $period = $this->getUpdatePeriod();
        $ticks = 1;

        switch ($period) {
            case 'yearly':
                $ticks *= 52; //TODO: fix generalisation, how?
                // no break
            case 'weekly':
                $ticks *= 7;
                // no break
            case 'daily':
                $ticks *= 24;
                // no break
            case 'hourly':
                $ticks *= 3600;
                break;
            default: //Never arrive here, exception thrown in getPeriod()
                break;
        }

        return $ticks / $freq;
    }

    /**
     * Get update base
     *
     * @return DateTime|null
     */
    public function getUpdateBase()
    {
        $updateBase = $this->getData('updateBase');
        $date = null;
        if ($updateBase) {
            $date = DateTime::createFromFormat(DateTime::W3C, $updateBase);
        }
        return $date;
    }

    /**
     * Get the entry data specified by name
     *
     * @param string $name
     * @param string $type
     * @return mixed|null
     */
    private function getData($name, $type = 'string')
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $data = $this->xpath->evaluate($type . '(' . $this->getXpathPrefix() . '/syn10:' . $name . ')');

        if (!$data) {
            $data = null;
        }

        $this->data[$name] = $data;

        return $data;
    }

    /**
     * Register Syndication namespaces
     *
     * @return void
     */
    protected function registerNamespaces()
    {
        $this->xpath->registerNamespace('syn10', 'http://purl.org/rss/1.0/modules/syndication/');
    }
}
