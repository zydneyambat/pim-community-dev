<?php

namespace Context;

use Behat\Behat\Event\StepEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\MinkExtension\Context\MinkContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Performance oriented feature context
 *
 * @author    Benoit Jacquemont <benoit@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PerformanceFeatureContext extends MinkContext
{
    /** @staticvar int */
    protected $timeout = "5000";

    /** @var HPBrowserMobProxy_Client */
    protected $bmpClient;

    /** @var array */
    protected $lastWaitData;

    /** @var array */
    protected $elementConditions = [
        "dashboard" =>
            'document.querySelector("div.current-version") != undefined',
        "product grid" =>
            'document.querySelector("div.grid-container") != undefined',
        "product edit form" =>
            'document.querySelector("div.pim-add-attributes-multiselect") != undefined'
    ];

    /**
     * @param array $parameters
     */
    public function __construct($parameters)
    {
        $this->windowWidth  = $parameters['window_width'];
        $this->windowHeight = $parameters['window_height'];
    }

    /**
     * @BeforeScenario
     */
    public function maximize()
    {
        try {
            $this->getSession()->resizeWindow($this->windowWidth, $this->windowHeight);
        } catch (UnsupportedDriverActionException $e) {
        }
    }

    /**
     * @BeforeScenario
     */
    public function enableProxy(ScenarioEvent $event)
    {
        $this->bmpClient = new \PHPBrowserMobProxy_Client("localhost:8080");

        $this->bmpClient->hostname = "localhost";
        $this->bmpClient->port = "8081";

        $this->bmpClient->url = $this->bmpClient->hostname . ":" . $this->bmpClient->port;

        $this->bmpClient->limits([
            "downstreamKbps" => 1024,
            "upstreamKbps" => 512,
            "latency" => 50
        ]);

        $this->bmpClient->newHar($event->getScenario()->getTitle());
    }

    /**
     * @BeforeStep
     */
    public function cleanLastWaitData()
    {
        $this->lastWaitData = null;
    }

    /**
     * @BeforeStep
     */
    public function cleanHarData(StepEvent $event)
    {
        $harReference =
            $event->getLogicalParent()->getTitle()." - ".
            $event->getStep()->getText();

        $this->bmpClient->newHar($harReference);
    }

    /**
     * @AfterStep
     */
    public function saveStepHarData(StepEvent $event)
    {
        $harData = $this->bmpClient->har;

        $featurePrefix = strstr($event->getLogicalParent()->getFile(), 'features/');
        $featurePrefix = str_replace('/', '__', $featurePrefix);
        $scenarioLine =  $event->getLogicalParent()->getLine();
        $stepLabel = $event->getStep()->getLine();

        $filename = sprintf('%s-%s-%s-har.json', $featurePrefix, $scenarioLine, $stepLabel);

        file_put_contents("/tmp/".$filename, json_encode($harData, JSON_PRETTY_PRINT));
    }

    /**
     * @AfterStep
     */
    public function saveWaitData(StepEvent $event)
    {
        if (null !== $this->lastWaitData) {
            $featurePrefix = strstr($event->getLogicalParent()->getFile(), 'features/');
            $featurePrefix = str_replace('/', '__', $featurePrefix);
            $scenarioLine =  $event->getLogicalParent()->getLine();
            $stepLabel = $event->getStep()->getLine();

            $filename = sprintf('%s-%s-%s-wait.json', $featurePrefix, $scenarioLine, $stepLabel);

            file_put_contents("/tmp/".$filename, json_encode($this->lastWaitData, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @Given /^I am on the login screen$/
     */
    public function iAmOnTheLoginScreen()
    {
        $this->iAmOnHomepage();

        if (!$this->getSession()->getPage()->find('css', '.form-signin')) {
            $this->getMainContext()->getSubcontext('navigation')->iLogout();
        }
    }

    /**
     * @Given /^I wait for the "([^"]*)" to appear$/
     */
    public function iWaitForTheElementToAppear($elementName)
    {
        $stopwatch = new Stopwatch();

        $stopwatch->start('waitingForElement');

        $elementCondition = $this->elementConditions[$elementName];

        $success = $this->getSession()->wait($this->timeout, $elementCondition);

        if (!$success) {
            throw new Spin\TimeoutException(
                sprintf("Waiting for %s failed after %s ms timeout", $elementName, $this->timeout)
            );
        }

        $this->lastWaitData = [
            "element" => $elementName,
            "time"    => $stopwatch->stop('waitingForElement')->getDuration()
        ];
    }

    /**
     * @Given /^I click on the first line of the product grid$/
     */
    public function iClickOnTheFirstLineOfTheProductGrid()
    {
        $firstRow = $this->getSession()->getPage()->find('xpath', '//*[@id="grid-product-grid"]/div[2]/div[2]/div/table/tbody/tr[1]');

        $firstRow->click();
    }
}
