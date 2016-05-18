<?php

namespace Pim\Component\Connector\Processor\Denormalization;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;
use Pim\Component\Connector\ArrayConverter\StandardArrayConverterInterface;

class JsonProcessor extends AbstractConfigurableStepElement implements ItemProcessorInterface
{
    /** @var StandardArrayConverterInterface */
    protected $arrayConverter;

    /** @var string */
    protected $categoriesColumn = 'categories';

    /** @var string */
    protected $familyColumn  = 'family';

    /** @var string */
    protected $groupsColumn  = 'groups';

    /** @var bool */
    protected $enabled = true;

    /**
     * @param StandardArrayConverterInterface $arrayConverter array converter
     */
    public function __construct(StandardArrayConverterInterface $arrayConverter)
    {
        $this->arrayConverter  = $arrayConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        return $this->convertItemData($item);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [];
    }

    /**
     * @param array $item
     *
     * @return array
     */
    protected function convertItemData(array $item)
    {
        return $this->arrayConverter->convert($item, $this->getArrayConverterOptions());
    }

    /**
     * @return array
     */
    protected function getArrayConverterOptions()
    {
        return [
            'mapping'           => $this->getMapping(),
            'default_values'    => $this->getDefaultValues(),
            'with_associations' => true
        ];
    }

    /**
     * @return array
     */
    protected function getMapping()
    {
        return [
            $this->familyColumn     => 'family',
            $this->categoriesColumn => 'categories',
            $this->groupsColumn     => 'groups'
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultValues()
    {
        return ['enabled' => $this->enabled];
    }
}
