<?php
/**
 * @copyright: Copyright © 2016 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

/**
 * Prepare cron jobs data
 */
namespace Firebear\ImportExport\Plugin\Config;

use Firebear\ImportExport\Model\JobFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 * @package Firebear\ImportExport\Plugin\Config
 */
class Data
{
    /**
     * Import Job factory
     *
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $jobCodePattern = 'importexport_jobs_run_id_%u';

    /**
     * @param JobFactory $jobFactory
     * @param LoggerInterface                $logger
     */
    public function __construct(JobFactory $jobFactory, LoggerInterface $logger)
    {
        $this->jobFactory = $jobFactory;
        $this->logger = $logger;
    }

    /**
     * Implement cron jobs created via admin panel into system cron jobs generated from xml files
     *
     * @param \Magento\Cron\Model\Config\Data $subject
     * @param                                 $result
     * @return mixed
     */
    public function afterGetJobs(\Magento\Cron\Model\Config\Data $subject, $result)
    {
        $jobCollection = $this->jobFactory->create()->getCollection();
        $jobCollection->addFieldToFilter('is_active', 1);
        $jobCollection->load();
        foreach ($jobCollection as $job) {
            $jobName = sprintf($this->jobCodePattern, $job->getId());
            $result['default'][$jobName] = [
                'name' => $jobName,
                'instance' => 'Firebear\ImportExport\Cron\RunImportJobs',
                'method' => 'execute',
                'schedule' => $job->getCron()
            ];
        }

        return $result;
    }
}
