<?php

namespace spec\Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\ProductIndexer;
use Pim\Bundle\CatalogBundle\EventSubscriber\IndexProductsSubscriber;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\CompletenessInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;

class IndexProductsSubscriberSpec extends ObjectBehavior
{
    function let(ProductIndexer $indexer)
    {
        $this->beConstructedWith($indexer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(IndexProductsSubscriber::class);
    }

    function it_subscribe_to_the_events()
    {
        $events = $this->getSubscribedEvents();
        $events->shouldHaveCount(3);
        $events->shouldHaveKey(StorageEvents::POST_SAVE);
        $events->shouldHaveKey(StorageEvents::POST_SAVE_ALL);
        $events->shouldHaveKey(StorageEvents::POST_REMOVE_ALL);
    }

    function it_does_not_index_a_non_product_entity($indexer, GenericEvent $event, \stdClass $subject)
    {
        $event->getSubject()->willReturn($subject);
        $indexer->index(Argument::cetera())->shouldNotBeCalled();

        $this->indexProduct($event);
    }

    function it_does_not_index_a_non_unitary_save_of_a_product(
        $indexer,
        GenericEvent $event,
        ProductInterface $product
    ) {
        $event->getSubject()->willReturn($product);
        $event->hasArgument('unitary')->willReturn(true);
        $event->getArgument('unitary')->willReturn(false);

        $indexer->index(Argument::any())->shouldNotBeCalled();

        $this->indexProduct($event);
    }

    function it_does_not_index_a_non_unitary_save_of_a_product_bis(
        $indexer,
        GenericEvent $event,
        ProductInterface $product
    ) {
        $event->getSubject()->willReturn($product);
        $event->hasArgument('unitary')->willReturn(false);

        $indexer->index(Argument::any())->shouldNotBeCalled();

        $this->indexProduct($event);
    }

    function it_does_not_bulk_index_non_product_entities(
        $indexer,
        GenericEvent $event,
        \stdClass $subject1
    ) {
        $event->getSubject()->willReturn([$subject1]);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProducts($event);
    }

    function it_does_not_bulk_index_non_collections($indexer, GenericEvent $event, \stdClass $subject1)
    {
        $event->getSubject()->willReturn($subject1);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProducts($event);
    }

    function it_does_not_bulk_index_on_completenesses_removal_non_completeness_entities(
        $indexer,
        GenericEvent $event,
        \stdClass $subject1
    ) {
        $event->getSubject()->willReturn([$subject1]);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProductsOnCompletenessesRemoval($event);
    }

    function it_does_not_bulk_index_on_completenesses_removal_non_collections($indexer, GenericEvent $event, \stdClass $subject1)
    {
        $event->getSubject()->willReturn($subject1);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProductsOnCompletenessesRemoval($event);
    }

    function it_indexes_a_single_product($indexer, GenericEvent $event, ProductInterface $product)
    {
        $event->getSubject()->willReturn($product);
        $event->hasArgument('unitary')->willReturn(true);
        $event->getArgument('unitary')->willReturn(true);

        $indexer->index($product)->shouldBeCalled();

        $this->indexProduct($event);
    }

    function it_bulk_indexes_products(
        $indexer,
        GenericEvent $event,
        ProductInterface $product1,
        ProductInterface $product2
    ) {
        $event->getSubject()->willReturn([$product1, $product2]);

        $indexer->indexAll([$product1, $product2])->shouldBeCalled();

        $this->bulkIndexProducts($event);
    }

    function it_bulk_indexes_products_on_completenesses_removal(
        $indexer,
        GenericEvent $event,
        ProductInterface $product1,
        CompletenessInterface $completeness1a,
        CompletenessInterface $completeness1b,
        ProductInterface $product2,
        CompletenessInterface $completeness2a
    ) {
        $event->getSubject()->willReturn([$completeness1a, $completeness1b, $completeness2a]);

        $completeness1a->getProduct()->willReturn($product1);
        $completeness1b->getProduct()->willReturn($product1);
        $completeness2a->getProduct()->willReturn($product2);

        $indexer->indexAll([$product1, $product2])->shouldBeCalled();

        $this->bulkIndexProductsOnCompletenessesRemoval($event);
    }
}
