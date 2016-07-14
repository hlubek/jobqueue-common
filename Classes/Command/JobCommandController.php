<?php
namespace Flowpack\JobQueue\Common\Command;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use Flowpack\JobQueue\Common\Exception as JobQueueException;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use TYPO3\Flow\Exception;

/**
 * Job command controller
 */
class JobCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\Inject
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Work on a queue and execute jobs
     *
     * @param string $queueName The name of the queue
     * @return void
     */
    public function workCommand($queueName)
    {
        do {
            try {
                $this->jobManager->waitAndExecute($queueName);
            } catch (JobQueueException $exception) {
                $this->outputLine('<error>' . $exception->getMessage() . '</error>');
                $nestedException = $exception->getPrevious();
                if ($nestedException instanceof \Exception) {
                    // TODO Add logged exception reference
                    $this->outputLine($nestedException->getMessage());
                    $this->systemLogger->logException($nestedException, array('queueName' => $queueName));
                    $referenceCodeString = ($nestedException instanceof Exception ? ' as ' . $nestedException->getReferenceCode() . '.txt' : '');
                    $this->outputLine('<em>Exception logged' . $referenceCodeString . '</em>');
                }
            } catch (\Exception $exception) {
                // TODO Add logged exception reference
                $this->outputLine('<error>Unexpected exception during job execution:</error> %s', array($exception->getMessage()));
                $this->systemLogger->logException($exception, array('queueName' => $queueName));
                $referenceCodeString = ($exception instanceof Exception ? ' as ' . $exception->getReferenceCode() . '.txt' : '');
                $this->outputLine('<em>Exception logged' . $referenceCodeString . '</em>');
            }
        } while (true);
    }

    /**
     * List queued jobs
     *
     * @param string $queueName The name of the queue
     * @param integer $limit Number of jobs to list
     * @return void
     */
    public function listCommand($queueName, $limit = 1)
    {
        $jobs = $this->jobManager->peek($queueName, $limit);
        $totalCount = $this->queueManager->getQueue($queueName)->count();
        foreach ($jobs as $job) {
            $this->outputLine('<u>%s</u>', array($job->getLabel()));
        }

        if ($totalCount > count($jobs)) {
            $this->outputLine('(%d omitted) ...', array($totalCount - count($jobs)));
        }
        $this->outputLine('(<b>%d total</b>)', array($totalCount));
    }
}
