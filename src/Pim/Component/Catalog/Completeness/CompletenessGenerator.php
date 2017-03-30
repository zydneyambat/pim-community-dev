<?php

namespace Pim\Component\Catalog\Completeness;

use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\FamilyInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Query\ProductQueryBuilderFactoryInterface;
use Pim\Component\Catalog\Query\ProductQueryBuilderInterface;

/**
 * Simple object version of the completeness generator.
 *
 * @author    Julien Janvier (j.janvier@akeneo.com)
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class CompletenessGenerator implements CompletenessGeneratorInterface
{
    const BULK_SIZE = 50;

    /** @var ProductQueryBuilderFactoryInterface */
    protected $pqbFactory;

    /** @var CompletenessCalculatorInterface */
    protected $completenessCalculator;

    /** @var SaverInterface */
    protected $productSaver;

    /** @var BulkSaverInterface */
    protected $productBulkSaver;

    /** @var BulkRemoverInterface */
    private $completenessRemover;

    /**
     * @param ProductQueryBuilderFactoryInterface $pqbFactory
     * @param CompletenessCalculatorInterface     $completenessCalculator
     * @param SaverInterface                      $productSaver
     * @param BulkSaverInterface                  $productBulkSaver
     * @param BulkRemoverInterface                $completenessRemover
     */
    public function __construct(
        ProductQueryBuilderFactoryInterface $pqbFactory,
        CompletenessCalculatorInterface $completenessCalculator,
        SaverInterface $productSaver,
        BulkSaverInterface $productBulkSaver,
        BulkRemoverInterface $completenessRemover
    ) {
        $this->pqbFactory = $pqbFactory;
        $this->completenessCalculator = $completenessCalculator;
        $this->productSaver = $productSaver;
        $this->productBulkSaver = $productBulkSaver;
        $this->completenessRemover = $completenessRemover;
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForProduct(ProductInterface $product)
    {
        $this->completenessCalculator->calculate($product);
        $this->productSaver->save($product);
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForChannel(ChannelInterface $channel)
    {
        $products = $this->createProductQueryBuilderForMissings($channel)->execute();
        $this->calculateAndBulkSave($products);
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissing()
    {
        $products = $this->createProductQueryBuilderForMissings()->execute();
        $this->calculateAndBulkSave($products);
    }

    /**
     * {@inheritdoc}
     */
    public function schedule(ProductInterface $product)
    {
        $product->getCompletenesses()->clear();
        $this->productSaver->save($product);
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleForFamily(FamilyInterface $family)
    {
        $familyFilter = ['field' => 'family', 'operator' => Operators::IN_LIST, 'value' => [$family->getCode()]];
        $products = $this->createProductQueryBuilder(null, null, [$familyFilter])->execute();

        $this->bulkRemoveCompletenessesAndIndexProducts($products);
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleForChannelAndLocale(ChannelInterface $channel, LocaleInterface $locale)
    {
        $products = $this->createProductQueryBuilder($channel, $locale)->execute();

        $this->bulkRemoveCompletenessesAndIndexProducts($products);
    }

    /**
     * @param ChannelInterface $channel
     * @param LocaleInterface  $locale
     *
     * @return ProductQueryBuilderInterface
     */
    protected function createProductQueryBuilderForMissings(
        ChannelInterface $channel = null,
        LocaleInterface $locale = null
    ) {
        $options = [
            'filters' => [['field' => 'completeness', 'operator' => Operators::IS_EMPTY, 'value' => null]]
        ];

        if (null !== $channel) {
            $options['default_scope'] = $channel->getCode();
        }
        if (null !== $locale) {
            $options['default_locale'] = $locale->getCode();
        }

        return $this->pqbFactory->create($options);
    }

    /**
     * @param ChannelInterface $channel
     * @param LocaleInterface  $locale
     * @param array            $filters
     *
     * @return ProductQueryBuilderInterface
     */
    protected function createProductQueryBuilder(
        ChannelInterface $channel = null,
        LocaleInterface $locale = null,
        array $filters = []
    ) {
        $options = [
            'filters' => $filters
        ];

        if (null !== $channel) {
            $options['default_scope'] = $channel->getCode();
        }
        if (null !== $locale) {
            $options['default_locale'] = $locale->getCode();
        }

        return $this->pqbFactory->create($options);
    }

    /**
     * @param CursorInterface $products
     */
    protected function calculateAndBulkSave(CursorInterface $products)
    {
        $bulkedProducts = [];
        $bulkCounter = 0;

        foreach ($products as $product) {
            $this->completenessCalculator->calculate($product);
            if (self::BULK_SIZE === $bulkCounter) {
                $this->productBulkSaver->saveAll($bulkedProducts);
                $bulkedProducts = [];
                $bulkCounter = 0;
            }
            else {
                $bulkedProducts[] = $product;
                $bulkCounter++;
            }
        }

        $this->productBulkSaver->saveAll($bulkedProducts);
    }

    /**
     * @param CursorInterface $products
     */
    protected function bulkRemoveCompletenessesAndIndexProducts(CursorInterface $products)
    {
        $bulkedCompletenesses = [];
        $bulkedProducts = [];
        $bulkCounter = 0;

        foreach ($products as $product) {
            $bulkedCompletenesses = array_merge(
                $bulkedCompletenesses,
                $product->getCompletenesses()->toArray()
            );
            $product->getCompletenesses()->clear();

            if (self::BULK_SIZE === $bulkCounter) {
                $this->completenessRemover->removeAll($bulkedCompletenesses);
                $bulkedCompletenesses = [];
                $bulkedProducts = [];
                $bulkCounter = 0;
            }
            else {
                $bulkedProducts[] = $product;
                $bulkCounter++;
            }
        }

        $this->completenessRemover->removeAll($bulkedCompletenesses);
    }
}
