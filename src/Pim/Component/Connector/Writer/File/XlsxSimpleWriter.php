<?php

namespace Pim\Component\Connector\Writer\File;

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;

/**
 * Write simple data into a XLSX file on the local filesystem
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class XlsxSimpleWriter extends AbstractFileWriter
{
    /** @var bool */
    protected $withHeader;

    /** @var FlatItemBuffer */
    protected $flatRowBuffer;

    /** @var TODO int ? string ? */
    protected $linesPerFiles;

    /** @var int */
    protected $writtenLinesCount;

    /**
     * @param FilePathResolverInterface $filePathResolver
     * @param FlatItemBuffer            $flatRowBuffer
     */
    public function __construct(FilePathResolverInterface $filePathResolver, FlatItemBuffer $flatRowBuffer)
    {
        parent::__construct($filePathResolver);

        $this->flatRowBuffer     = $flatRowBuffer;
        $this->writtenLinesCount = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $exportFolder = dirname($this->getPath());
        if (!is_dir($exportFolder)) {
            $this->localFs->mkdir($exportFolder);
        }

        $this->flatRowBuffer->write($items, $this->isWithHeader());
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($this->getPath());

        $headers    = $this->flatRowBuffer->getHeaders();
        $hollowItem = array_fill_keys($headers, '');
        $writer->addRow($headers);
        foreach ($this->flatRowBuffer->getBuffer() as $incompleteItem) {
            $item = array_replace($hollowItem, $incompleteItem);
            $writer->addRow($item);

            if (null !== $this->stepExecution) {
                $this->stepExecution->incrementSummaryInfo('write');
            }
        }

        $writer->close();
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
}
