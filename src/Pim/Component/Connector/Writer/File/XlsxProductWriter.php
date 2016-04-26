<?php

namespace Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Buffer\BufferInterface;
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\WriterInterface;

/**
 * Write product data into a XLSX file on the local filesystem
 *
 * @author    Arnaud Langlade <arnaud.langlade@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class XlsxProductWriter extends AbstractFileWriter implements ItemWriterInterface, ArchivableWriterInterface
{
    /** @var bool */
    protected $withHeader;

    /** @var FlatItemBuffer */
    protected $flatRowBuffer;

    /** @var BulkFileExporter */
    protected $mediaCopier;

    /** @var array */
    protected $writtenFiles;

    /** @var TODO int ? string ? */
    protected $linesPerFiles;

    /** @var int */
    protected $fileCount;

    /**
     * @param FilePathResolverInterface $filePathResolver
     * @param FlatItemBuffer            $flatRowBuffer
     * @param BulkFileExporter          $mediaCopier
     */
    public function __construct(
        FilePathResolverInterface $filePathResolver,
        FlatItemBuffer $flatRowBuffer,
        BulkFileExporter $mediaCopier
    ) {
        parent::__construct($filePathResolver);

        $this->flatRowBuffer = $flatRowBuffer;
        $this->mediaCopier   = $mediaCopier;
        $this->writtenFiles  = [];
        $this->fileCount     = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $exportDirectory = dirname($this->getPath());
        if (!is_dir($exportDirectory)) {
            $this->localFs->mkdir($exportDirectory);
        }

        $products = $media = [];
        foreach ($items as $item) {
            $products[] = $item['product'];
            $media[]    = $item['media'];
        }

        $this->flatRowBuffer->write($products, $this->isWithHeader());
        $this->mediaCopier->exportAll($media, $exportDirectory);

        foreach ($this->mediaCopier->getCopiedMedia() as $copy) {
            $this->writtenFiles[$copy['copyPath']] = $copy['originalMedium']['exportPath'];
        }

        foreach ($this->mediaCopier->getErrors() as $error) {
            $this->stepExecution->addWarning(
                $this->getName(),
                $error['message'],
                [],
                $error['medium']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if ($this->severalFilesNeeded()) {
            $this->enforceFileName();
        }

        $headers    = $this->flatRowBuffer->getHeaders();
        $hollowItem = array_fill_keys($headers, '');

        $fileCount = 0;
        $writtenLinesCount = 0;
        foreach ($this->flatRowBuffer->getBuffer() as $count => $incompleteItem) {
            if (0 === $writtenLinesCount % $this->getLinesPerFiles()) {
                if ($this->severalFilesNeeded()) {
                    $this->updateFilePathResolverOptions(['fileNb' => sprintf('_%d', ++$fileCount)]);
                }

                $writer = $this->getWriter();
                $writer->addRow($headers);
                $writtenLinesCount = empty($headers) ? 0 : 1;
            }

            $item = array_replace($hollowItem, $incompleteItem);
            $writer->addRow($item);
            $writtenLinesCount++;

            if (null !== $this->stepExecution) {
                $this->stepExecution->incrementSummaryInfo('write');
            }

            if (0 === $writtenLinesCount % $this->getLinesPerFiles() || $this->flatRowBuffer->count() === $count + 1) {
                $writer->close();
                $this->writtenFiles[$this->getPath()] = basename($this->getPath());
                $writtenLinesCount = 0;
            }
        }
    }

    /**
     * @return bool
     */
    protected function severalFilesNeeded()
    {
        $itemCount = $this->flatRowBuffer->count();
        $hasHeader = empty($this->flatRowBuffer->getHeaders());

        $totalLineCount  = $hasHeader ? ++$itemCount : $itemCount;
        $fileCountNeeded = ceil($totalLineCount / $this->getLinesPerFiles());

        return $fileCountNeeded > 1;
    }

    /**
     * Add a %fileNb% pattern in the file name
     */
    protected function enforceFileName()
    {
        $ext = strrchr($this->filePath, '.');
        $filePath = strstr($this->filePath, $ext, true);
        $this->setFilePath($filePath . '%fileNb%' . $ext);
    }

    /**
     * @param array $headers
     *
     * @return WriterInterface
     */
    protected function getWriter()
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($this->getPath());

        return $writer;
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [
            'filePath' => [
                'options' => [
                    'label' => 'pim_connector.export.filePath.label',
                    'help'  => 'pim_connector.export.filePath.help',
                ],
            ],
            'linesPerFiles' => [
                'type'    => 'number',
                'options' => [
                    'label' => 'pim_connector.export.lines_per_files.label',
                    'help'  => 'pim_connector.export.lines_per_files.help'
                ]
            ],
            'withHeader' => [
                'type'    => 'switch',
                'options' => [
                    'label' => 'pim_connector.export.withHeader.label',
                    'help'  => 'pim_connector.export.withHeader.help',
                ],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function isWithHeader()
    {
        return $this->withHeader;
    }

    /**
     * @param bool $withHeader
     */
    public function setWithHeader($withHeader)
    {
        $this->withHeader = $withHeader;
    }

    /**
     * @return TODO
     */
    public function getLinesPerFiles()
    {
        return $this->linesPerFiles;
    }

    /**
     * @param TODO $linesPerFiles
     */
    public function setLinesPerFiles($linesPerFiles)
    {
        $this->linesPerFiles = $linesPerFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config)
    {
        parent::setConfiguration($config);

        if (!isset($config['mainContext'])) {
            return;
        }

        $this->updateFilePathResolverOptions($config['mainContext']);
    }
}
