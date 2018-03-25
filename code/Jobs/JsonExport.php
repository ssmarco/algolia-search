<?php

namespace Marcz\Algolia\Jobs;

use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\Core\Injector\Injector;
use Exception;
use Marcz\Search\Processor\Exporter;
use SilverStripe\Assets\File;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Core\Config\Config;

class JsonExport extends AbstractQueuedJob implements QueuedJob
{
    /**
     * Methods that corresponds to the chronological steps for this job.
     * All methods must return true to signal successful process
     *
     * @var array
     */
    protected $definedSteps = [
        'stepOne',
        'stepTwo',
        'stepCreateNextSchedule',
    ];

    /**
     * @param string $className
     * @param int $offset
     */
    public function __construct($className = null, $offset = 0)
    {
        $this->totalSteps  = count($this->definedSteps);
        $this->currentStep = 0;
        $this->className   = $className;
        $this->offset      = (int) $offset;
        $this->filename    = '';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Json document export: "' . $this->className . '" starting at ' . $this->offset;
    }

    /**
     * @return string
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    public function process()
    {
        $stepIsDone = false;

        if (!$this->className) {
            throw new Exception('Missing className defined on the constructor');
        }

        if (!isset($this->definedSteps[$this->currentStep])) {
            throw new Exception('User error, unknown step defined.');
        }

        $stepIsDone = call_user_func([$this, $this->definedSteps[$this->currentStep]]);

        if ($stepIsDone) {
            $this->currentStep++;
        }

        // and checking whether we're complete
        if ($this->currentStep >= $this->totalSteps) {
            $this->isComplete = true;
        }
    }

    public function stepOne()
    {
        $this->addMessage('Step 1: step one...');

        $file     = new File();
        $exporter = Exporter::create();
        $dateTime = DBDatetime::now();
        $bulk     = $exporter->bulkExport($this->className, $this->offset);
        $fileName = sprintf(
            '%s_export_%s_%d.json',
            $this->className,
            $dateTime->URLDatetime(),
            $this->offset
        );

        Config::modify()->set(File::class, 'allowed_extensions', ['json']);
        $file->setFromString(json_encode($bulk), $fileName);
        $file->write();
        $file->publishFile();

        $this->filename = $file->getAbsoluteURL();

        $this->addMessage('<p><a href="' . $this->filename . '" target="_blank">' . $fileName . '</a></p>');

        return true;
    }

    public function stepTwo()
    {
        $this->addMessage('Step 2: step two...');
        return true;
    }

    public function stepCreateNextSchedule()
    {
        $this->addMessage('Step 3: Creating next schedule and finishing up.');
        // $job     = Injector::inst()->createWithArgs(self::class, [$this->className, $offset = 100]);
        // $service = singleton(QueuedJobService::class)->queueJob($job);

        return true;
    }

    /**
     * Called when the job is determined to be 'complete'
     * Clean-up object properties
     */
    public function afterComplete()
    {
        $this->className = null;
        $this->start     = null;
    }
}
