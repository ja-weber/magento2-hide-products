<?php
namespace WeberInformatics\HideProducts\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Class LoadProductsObserver
 * @package VendorName\HideProducts\Observer
 */
class LoadProductsObserver implements ObserverInterface {
    /**
     * @var Reader
    */
    protected $moduleReader;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $hideRules;

    /**
     * LoadProductsObserver constructor.
     * @param Session $customerSession ,
     * @param Reader $moduleReader
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Session $customerSession,
        Reader $moduleReader,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->moduleReader = $moduleReader;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return Observer|void
     */
    public function execute(Observer $observer)
    {$customerGroupId = $this->customerSession->getCustomer()->getGroupId();
        /** @var Collection $collection */
        $collection = $observer->getCollection();
        
        if ($collection === null) {
            return $observer;
        }
        
        if (!($collection instanceof Collection)) {
            return $observer;
        }
        
        $productIdsToHide = $this->getProductIdsToHide();
        
        if (count($productIdsToHide) > 0) {
            $identifierField = $collection->getProductEntityMetadata()->getIdentifierField();
            $collection->getSelect()->where("e." .$identifierField . " NOT IN (?)", $productIdsToHide);
        }
        
        return $observer;
    }

    /**
     * @return array
     */
    private function getProductIdsToHide() {
        $productIdsToHide = [];
        $hideRules = $this->getHideRules();
        
        if (count($hideRules) === 0) {
            return $productIdsToHide;
        }

        $customerGroupId = $this->customerSession->getCustomer()->getGroupId();
        
        foreach ($hideRules as $hideRule) {
            if (!isset($hideRule['productId']) || !isset($hideRule['customerGroupIds']) || !is_array($hideRule['customerGroupIds'])) {
                continue;
            }

            if (in_array($customerGroupId, $hideRule['customerGroupIds'])) {
                $productIdsToHide[] = $hideRule['productId'];
            }
        }

        return $productIdsToHide;
    }
    
    private function getHideRules() {
        if (!isset($this->hideRules)) {
            $this->hideRules = [];

            try {
                $moduleEtcDirectory = $this->moduleReader->getModuleDir(
                    \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
                    'VendorName_HideProducts'
                );

                $configFileContent = \file_get_contents($moduleEtcDirectory . DIRECTORY_SEPARATOR . 'config.json');

                $configFileMap = $this->jsonSerializer->unserialize($configFileContent);

                if (!isset($configFileMap['hideRules']) || !is_array($configFileMap['hideRules'])) {
                    throw new ConfigurationMismatchException(__('Variable "hideRules" is missing.'));
                }

                $this->hideRules = $configFileMap['hideRules'];

            } catch (\Exception $e) {
                $this->logger->error('Could not get products and customer groups configuration. ' . PHP_EOL . $e->getMessage());
            }
        }
        
        return $this->hideRules;
    }
}