<?php

namespace spec\Pim\Component\Catalog\Completeness;

use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Doctrine\Common\Collections\Collection;
use Pim\Component\Catalog\Completeness\CompletenessCalculatorInterface;
use Pim\Component\Catalog\Completeness\CompletenessGenerator;
use Pim\Component\Catalog\Model\CompletenessInterface;
use Pim\Component\Catalog\Model\FamilyInterface;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Query\ProductQueryBuilderFactoryInterface;
use Pim\Component\Catalog\Query\ProductQueryBuilderInterface;
use Prophecy\Argument;

class CompletenessGeneratorSpec extends ObjectBehavior
{
    function let(
        ProductQueryBuilderFactoryInterface $pqbFactory,
        CompletenessCalculatorInterface $calculator,
        SaverInterface $productSaver,
        BulkSaverInterface $bulkProductSaver,
        BulkRemoverInterface $completenessRemover
    ) {
        $this->beConstructedWith($pqbFactory, $calculator, $productSaver, $bulkProductSaver, $completenessRemover);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CompletenessGenerator::class);
    }

    function it_generates_missing_completeness_for_a_product($calculator, $productSaver, ProductInterface $product)
    {
        $calculator->calculate($product)->shouldBeCalled();
        $productSaver->save($product)->shouldBeCalled();

        $this->generateMissingForProduct($product);
    }

    function it_generates_missing_completenesses_for_a_channel(
        $pqbFactory,
        $calculator,
        $bulkProductSaver,
        ProductQueryBuilderInterface $pqb,
        ProductInterface $product1,
        ProductInterface $product2,
        ChannelInterface $channel,
        CursorInterface $products
    ) {
        $products->rewind()->shouldBeCalled();
        $products->valid()->willReturn(true, true, false);
        $products->current()->willReturn($product1, $product2);
        $products->next()->shouldBeCalled();

        $channel->getCode()->willReturn('ecommerce');

        $pqbFactory->create(
            [
                'filters' => [['field' => 'completeness', 'operator' => Operators::IS_EMPTY, 'value' => null]],
                'default_scope' => 'ecommerce'
            ]
        )->willReturn($pqb);

        $pqb->execute()->willReturn($products);

        $calculator->calculate($product1)->shouldBeCalled();
        $calculator->calculate($product2)->shouldBeCalled();
        $bulkProductSaver->saveAll([$product1, $product2])->shouldBeCalled();

        $this->generateMissingForChannel($channel);
    }

    function it_generates_missing_completenesses(
        $pqbFactory,
        $calculator,
        $bulkProductSaver,
        ProductQueryBuilderInterface $pqb,
        ProductInterface $product1,
        ProductInterface $product2,
        CursorInterface $products
    ) {
        $products->rewind()->shouldBeCalled();
        $products->valid()->willReturn(true, true, false);
        $products->current()->willReturn($product1, $product2);
        $products->next()->shouldBeCalled();

        $pqbFactory->create(
            [
                'filters' => [['field' => 'completeness', 'operator' => Operators::IS_EMPTY, 'value' => null]],
            ]
        )->willReturn($pqb);

        $pqb->execute()->willReturn($products);

        $calculator->calculate($product1)->shouldBeCalled();
        $calculator->calculate($product2)->shouldBeCalled();
        $bulkProductSaver->saveAll([$product1, $product2])->shouldBeCalled();

        $this->generateMissing();
    }

    function it_deletes_completeness_of_a_product($productSaver, ProductInterface $product, Collection $completenesses)
    {
        $product->getCompletenesses()->willReturn($completenesses);
        $completenesses->clear()->shouldBeCalled();
        $productSaver->save($product);

        $this->schedule($product);
    }

    function it_deletes_completeness_of_a_family(
        $completenessRemover,
        $pqbFactory,
        ProductQueryBuilderInterface $pqb,
        ProductInterface $product1,
        ProductInterface $product2,
        Collection $completenesses1,
        CompletenessInterface $completeness1a,
        CompletenessInterface $completeness1b,
        Collection $completenesses2,
        CompletenessInterface $completeness2a,
        FamilyInterface $family,
        CursorInterface $products
    ) {
        $products->rewind()->shouldBeCalled();
        $products->valid()->willReturn(true, true, false);
        $products->current()->willReturn($product1, $product2);
        $products->next()->shouldBeCalled();

        $family->getCode()->willReturn('pants');

        $pqbFactory->create(
            [
                'filters' => [
                    ['field' => 'family', 'operator' => Operators::IN_LIST, 'value' => ['pants']],
                ],
            ]
        )->willReturn($pqb);

        $pqb->execute()->willReturn($products);

        $product1->getCompletenesses()->willReturn($completenesses1);
        $completenesses1->clear()->shouldBeCalled();
        $completenesses1->toArray()->willReturn([$completeness1a, $completeness1b]);

        $product2->getCompletenesses()->willReturn($completenesses2);
        $completenesses2->clear()->shouldBeCalled();
        $completenesses2->toArray()->willReturn([$completeness2a]);

        $completenessRemover->removeAll([$completeness1a, $completeness1b, $completeness2a])->shouldBeCalled();

        $this->scheduleForFamily($family);
    }

    function it_deletes_completeness_of_a_channel_locale(
        $completenessRemover,
        $pqbFactory,
        ProductQueryBuilderInterface $pqb,
        ProductInterface $product1,
        ProductInterface $product2,
        Collection $completenesses1,
        CompletenessInterface $completeness1a,
        CompletenessInterface $completeness1b,
        Collection $completenesses2,
        CompletenessInterface $completeness2a,
        ChannelInterface $channel,
        LocaleInterface $locale,
        CursorInterface $products
    ) {
        $products->rewind()->shouldBeCalled();
        $products->valid()->willReturn(true, true, false);
        $products->current()->willReturn($product1, $product2);
        $products->next()->shouldBeCalled();

        $channel->getCode()->willReturn('ecommerce');
        $locale->getCode()->willReturn('en_US');

        $pqbFactory->create(
            [
                'filters' => [],
                'default_scope' => 'ecommerce',
                'default_locale' => 'en_US',
            ]
        )->willReturn($pqb);

        $pqb->execute()->willReturn($products);

        $product1->getCompletenesses()->willReturn($completenesses1);
        $completenesses1->clear()->shouldBeCalled();
        $completenesses1->toArray()->willReturn([$completeness1a, $completeness1b]);

        $product2->getCompletenesses()->willReturn($completenesses2);
        $completenesses2->clear()->shouldBeCalled();
        $completenesses2->toArray()->willReturn([$completeness2a]);

        $completenessRemover->removeAll([$completeness1a, $completeness1b, $completeness2a])->shouldBeCalled();

        $this->scheduleForChannelAndLocale($channel, $locale);
    }
}
