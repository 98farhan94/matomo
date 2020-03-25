<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Actions\Columns\Metrics;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\ProcessedMetric;

/**
 * The average amount for a certain performance metric. Calculated as
 *
 *     sum_time / nb_hits_with_time
 *
 * The above metrics are calculated during archiving. This metric is calculated before
 * serving a report.
 */
abstract class AveragePerformanceMetric extends ProcessedMetric
{
    const ID = '';

    public function getName()
    {
        return 'avg_' . self::ID;
    }

    public function getDependentMetrics()
    {
        return array('sum_' . self::ID, 'nb_hits_with_' . self::ID);
    }

    public function getTemporaryMetrics()
    {
        return array('sum_' . self::ID);
    }

    public function compute(Row $row)
    {
        $sumGenerationTime = $this->getMetric($row, 'sum_' . self::ID);
        $hitsWithTimeGeneration = $this->getMetric($row, 'nb_hits_with_' . self::ID);

        return Piwik::getQuotientSafe($sumGenerationTime, $hitsWithTimeGeneration, $precision = 3);
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

    public function beforeCompute($report, DataTable $table)
    {
        $hasTimeGeneration = array_sum($this->getMetricValues($table, 'sum_' . self::ID)) > 0;

        if (!$hasTimeGeneration
            && $table->getRowsCount() != 0
            && !$this->hasAverageMetric($table)
        ) {
            // No generation time: remove it from the API output and add it to empty_columns metadata, so that
            // the columns can also be removed from the view
            $table->filter('ColumnDelete', array(array(
                'sum_' . self::ID,
                'nb_hits_with_' . self::ID,
                'min_' . self::ID,
                'max_' . self::ID
            )));

            if ($table instanceof DataTable) {
                $emptyColumns = $table->getMetadata(DataTable::EMPTY_COLUMNS_METADATA_NAME);
                if (!is_array($emptyColumns)) {
                    $emptyColumns = array();
                }
                $emptyColumns[] = 'sum_' . self::ID;
                $emptyColumns[] = 'nb_hits_with_' . self::ID;
                $emptyColumns[] = 'min_' . self::ID;
                $emptyColumns[] = 'max_' . self::ID;
                $table->setMetadata(DataTable::EMPTY_COLUMNS_METADATA_NAME, $emptyColumns);
            }
        }

        return $hasTimeGeneration;
    }

    private function hasAverageMetric(DataTable $table)
    {
        return $table->getFirstRow()->getColumn($this->getName()) !== false;
    }
}