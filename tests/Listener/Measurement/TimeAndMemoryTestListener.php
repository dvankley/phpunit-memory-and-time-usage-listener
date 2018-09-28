<?php

namespace PhpunitMemoryAndTimeUsageListener\Listener\Measurement;

use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\MemoryMeasurement;
use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\TestMeasurement;
use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\TimeMeasurement;
use \PHPUnit\Framework\Test as Test;
use \PHPUnit\Framework\Warning as Warning;
use \PHPUnit\Framework\TestListener as TestListener;
use \PHPUnit\Framework\TestListenerDefaultImplementation as DefaultListener;

class TimeAndMemoryTestListener implements TestListener
{
    use DefaultListener;

    /** @var TestMeasurement[] */
    protected $testMeasurementCollection;

    /** @var bool  */
    protected $showOnlyIfEdgeIsExceeded = false;

    /**
     * Time in milliseconds we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $executionTimeEdge = 100;

    /**
     * Memory bytes usage we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $memoryUsageEdge = 1024;

    /**
     * Memory bytes usage we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $memoryPeakDifferenceEdge = 1024;

    /**
     * @var TimeMeasurement
     */
    protected $executionTime;
    protected $memoryUsage;
    protected $memoryPeakIncrease;

    public function __construct($configurationOptions = array())
    {
        $this->setConfigurationOptions($configurationOptions);
    }

    /**
     * @param Test $test
     */
    public function startTest(Test $test)
    {
        $this->memoryUsage = memory_get_usage();
        $this->memoryPeakIncrease = memory_get_peak_usage();
    }

    /**
     * @param Test $test
     * @param $time
     */
    public function endTest(Test $test, $time)
    {
        $this->executionTime = new TimeMeasurement($time);
        $this->memoryUsage = memory_get_usage() - ($this->memoryUsage);
        $this->memoryPeakIncrease = memory_get_peak_usage() - ($this->memoryPeakIncrease);

        echo PHP_EOL . ">> Peak memory: " . (int)(memory_get_peak_usage() / 1000) . " kB" . PHP_EOL;

        if ($this->haveToSaveTestMeasurement($time)) {
            $testMeasurement = new TestMeasurement(
                $test->getName(),
                get_class($test),
                $this->executionTime,
                (new MemoryMeasurement($this->memoryUsage)),
                (new MemoryMeasurement($this->memoryPeakIncrease))
            );
            echo PHP_EOL . " - " . $testMeasurement->measuredInformationMessage() . PHP_EOL;
        }
    }

    /**
     * @return bool
     */
    protected function haveToSaveTestMeasurement()
    {
        return ((false === $this->showOnlyIfEdgeIsExceeded)
            || ((true === $this->showOnlyIfEdgeIsExceeded)
                && ($this->isAPotentialCriticalTimeUsage()
                || $this->isAPotentialCriticalMemoryUsage()
                || $this->isAPotentialCriticalMemoryPeakUsage()
                )
            )
        );
    }

    /**
     * Check if test execution time is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalTimeUsage()
    {
        return $this->checkEdgeIsOverTaken($this->executionTime->timeInMilliseconds(), $this->executionTimeEdge);
    }

    /**
     * Check if test execution memory usage is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalMemoryUsage()
    {
        return $this->checkEdgeIsOverTaken($this->memoryUsage, $this->memoryUsageEdge);
    }

    /**
     * Check if test execution memory peak usage is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalMemoryPeakUsage()
    {
        return $this->checkEdgeIsOverTaken($this->memoryPeakIncrease, $this->memoryPeakDifferenceEdge);
    }

    /**
     * @param $value
     * @param $edgeValue
     * @return bool
     */
    protected function checkEdgeIsOverTaken($value, $edgeValue)
    {
        return ($value >= $edgeValue);
    }

    /**
     * @param $configurationOptions
     */
    protected function setConfigurationOptions($configurationOptions)
    {
        if (isset($configurationOptions['showOnlyIfEdgeIsExceeded'])) {
            $this->showOnlyIfEdgeIsExceeded = $configurationOptions['showOnlyIfEdgeIsExceeded'];
        }

        if (isset($configurationOptions['executionTimeEdge'])) {
            $this->executionTimeEdge = $configurationOptions['executionTimeEdge'];
        }

        if (isset($configurationOptions['memoryUsageEdge'])) {
            $this->memoryUsageEdge = $configurationOptions['memoryUsageEdge'];
        }

        if (isset($configurationOptions['memoryPeakDifferenceEdge'])) {
            $this->memoryPeakDifferenceEdge = $configurationOptions['memoryPeakDifferenceEdge'];
        }
    }
}
