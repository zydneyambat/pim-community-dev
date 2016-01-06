<?php

namespace Pim\Bundle\DataGridBundle\Controller;

use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Pim\Bundle\DataGridBundle\Datagrid\Configuration\ConfiguratorInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Datagrid controller
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DatagridController
{
    /** @var EngineInterface */
    protected $templating;

    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var ConfiguratorInterface */
    protected $contextConfigurator;

    /** @var ConfiguratorInterface */
    protected $filtersConfigurator;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /**
     * @param EngineInterface $templating
     */
    public function __construct(
        EngineInterface $templating,
        ConfigurationProviderInterface $configurationProvider,
        ConfiguratorInterface $contextConfigurator,
        ConfiguratorInterface $filtersConfigurator,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->templating = $templating;
        $this->configurationProvider = $configurationProvider;
        $this->contextConfigurator = $contextConfigurator;
        $this->filtersConfigurator = $filtersConfigurator;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Load a datagrid
     *
     * @param Request $request
     * @param string  $alias
     *
     * @return JsonResponse
     */
    public function loadAction(Request $request, $alias)
    {
        return $this->templating->renderResponse(
            'PimDataGridBundle:Datagrid:load.json.twig',
            [
                'alias'  => $alias,
                'params' => $request->get('params', [])
            ],
            new JsonResponse()
        );
    }

    public function getFiltersAction($alias, $search)
    {
        $config = $this->configurationProvider->getConfiguration($alias);

        $staticFilters = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        $staticFilters = array_filter($staticFilters, function ($filter) use ($search) {
            return false !== stripos($filter['label'], $search);
        });
        $config->offsetSetByPath(Configuration::COLUMNS_PATH, $staticFilters);

        $limit = 10 - count($staticFilters);
        $results = $this->attributeRepository->findBySearch($search, ['localeCode' => 'en_US', 'limit' => $limit]);

        $attributes = [];
        if ($results) {
            $attributeIds = [];
            foreach ($results as $attribute) {
                $attributeIds[] = $attribute->getId();
            }
            $attributes = $this->attributeRepository->getAttributesAsArray(true, 'en_US', $attributeIds);
        }

        $config->offsetSetByPath('[source][attributes_configuration]', $attributes);
        $this->filtersConfigurator->configure($config);
        $filtersConfig = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        return new JsonResponse($filtersConfig);
    }
}
