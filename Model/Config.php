<?php

namespace Chiron\Sirio\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Config
{
    const MODULE_NAME = 'Chiron_Sirio';

    //= General Settings


    private $allStoreIds = [0 => null, 1 => null];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var DateTimeFactory
     */
    private $datetimeFactory;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @method __construct
     * @param  StoreManagerInterface    $storeManager
     * @param  ScopeConfigInterface     $scopeConfig
     * @param  ResourceConfig           $resourceConfig
     * @param  EncryptorInterface       $encryptor
     * @param  DateTimeFactory          $datetimeFactory
     * @param  ModuleListInterface      $moduleList
     * @param  ProductMetadataInterface $productMetadata
     * @param  LoggerInterface          $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        EncryptorInterface $encryptor,
        DateTimeFactory $datetimeFactory,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->datetimeFactory = $datetimeFactory;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->logger = $logger;
    }

    /**
     * @method getStoreManager
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
    * @method getWebsiteIdByStoreId
    * @param int $storeId
    * @return int
    */
    public function getWebsiteIdByStoreId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($configPath);
        }
        return $this->scopeConfig->getValue($configPath, $scope ?: ScopeInterface::SCOPE_STORE, is_null($scopeId) ? $this->storeManager->getStore()->getId() : $scopeId);
    }

    /**
     * @return boolean
     */
    public function isEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isDebugMode($scope = null, $scopeId = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $scope, $scopeId)) ? true : false;
    }



    /**
     * Log to system.log
     * @method log
     * @param  mixed  $message
     * @param  string $type
     * @param  array  $data
     * @return $this
     */
    public function log($message, $type = "debug", $data = [], $prefix = '[Chiron Log] ')
    {
        if ($type !== 'debug' || $this->isDebugMode()) {
            if (!isset($data['store_id'])) {
                $data['store_id'] = $this->getCurrentStoreId();
            }
            if (!isset($data['app_key'])) {
                $data['app_key'] = $this->getAppKey();
            }
            switch ($type) {
                case 'error':
                    $this->logger->error($prefix . json_encode($message), $data);
                    break;
                case 'info':
                    $this->logger->info($prefix . json_encode($message), $data);
                    break;
                case 'debug':
                default:
                    $this->logger->debug($prefix . json_encode($message), $data);
                    break;
            }
        }
        return $this;
    }



    /**
     * @method getCurrentStoreId
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @method getAllStoreIds
     * @param  boolean $withDefault
     * @param  boolean $onlyActive
     * @return array
     */
    public function getAllStoreIds($withDefault = false, $onlyActive = true)
    {
        $cacheKey = ($withDefault) ? 1 : 0;
        if ($this->allStoreIds[$cacheKey] === null) {
            $this->allStoreIds[$cacheKey] = [];
            foreach ($this->storeManager->getStores($withDefault) as $store) {
                if ($onlyActive && !$store->isActive()) {
                    continue;
                }
                $this->allStoreIds[$cacheKey][] = $store->getId();
            }
        }
        return $this->allStoreIds[$cacheKey];
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getMagentoPlatformName()
    {
        return $this->productMetadata->getName();
    }

    public function getMagentoPlatformEdition()
    {
        return $this->productMetadata->getEdition();
    }

    public function getMagentoPlatformVersion()
    {
        return $this->productMetadata->getVersion();
    }
}
