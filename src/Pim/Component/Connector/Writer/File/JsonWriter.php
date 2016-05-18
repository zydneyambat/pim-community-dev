<?php

namespace Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Batch\Job\RuntimeErrorException;
use Symfony\Component\Validator\Constraints as Assert;

class JsonWriter extends AbstractConfigurableStepElement implements
    ItemWriterInterface
{
    const FILE_PREFIX = 'csv-to-json-import';

    /** @var resource */
    protected $file;

    /** @var string */
    protected $fileName;


    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            fputs($this->file, json_encode($item) . "\n");
        }
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getConfigurationFields()
    {
        return [];
    }

    /**
     * Create the file to write to and return its pointer
     *
     * @throws RuntimeErrorException
     *
     * @return resource
     */
    public function initialize()
    {
        $this->fileName = tempnam(sys_get_temp_dir(), self::FILE_PREFIX);
        $this->file     =  fopen($this->fileName, 'w');
    }
}
