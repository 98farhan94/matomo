<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Actions\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\ProcessedMetric;

/**
 * The average amount of time it took loading a page completely. Calculated as:
 *
 *     avg_time_latency + avg_time_transfer + avg_time_dom_processing + avg_time_dom_completion + avg_time_on_load
 */
class AveragePageLoadTime extends ProcessedMetric
{
    public function getName()
    {
        return 'avg_page_load_time';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('General_ColumnAveragePageLoadTime');
    }

    public function compute(Row $row)
    {
        $sum = 0;
        foreach ($this->getDependentMetrics() as $dependentMetric) {
            $sum += self::getMetric($row, $dependentMetric);
        }

        return $sum;
    }

    public function format($value, Formatter $formatter)
    {
        if ($formatter instanceof Formatter\Html
            && !$value
        ) {
            return '-';
        } else {
            return $formatter->getPrettyTimeFromSeconds($value, $displayAsSentence = true);
        }
    }

    public function getDependentMetrics()
    {
        return array('avg_time_latency', 'avg_time_transfer', 'avg_time_dom_processing', 'avg_time_dom_completion', 'avg_time_on_load');
    }
}