<?php

namespace Pim\Component\Connector\Reader\File;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemReaderInterface;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\ItemStep;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Pim\Component\Connector\Writer\File\JsonWriter;
use Symfony\Component\Validator\Constraints as Assert;

class JsonReader extends AbstractConfigurableStepElement implements
    ItemReaderInterface,
    StepExecutionAwareInterface
{
    /** @var string */
    protected $filePath;

    /** @var bool */
    protected $uploadAllowed = false;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var resource */
    protected $json;

    /**
     * Set file path
     *
     * @param string $filePath
     *
     * @return JsonReader
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        $this->json = null;

        return $this;
    }

    /**
     * Get file path
     *
     * @return string $filePath
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $rawLine = fgets($this->json);
        $this->stepExecution->incrementReadCount();

        return false !== $rawLine ? json_decode($rawLine, true) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $jobInstance = $this->stepExecution->getJobExecution()->getJobInstance();
        foreach ($jobInstance->getJob()->getSteps() as $step) {
            if (!$step instanceof ItemStep) {
                continue;
            }
            $writer = $step->getWriter();
            if ($writer instanceof JsonWriter) {
                $this->filePath = $writer->getFileName();
            }
        }
        echo "\n" . $this->filePath . "\n";

        if (null !== $this->filePath) {
            $this->json = fopen($this->filePath, 'r');
        }
    }
}
