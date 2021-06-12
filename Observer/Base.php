<?php
/**
 * @category Chiron
 * @package Chiron_Sirio
 * @version 0.1.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
	
namespace Chiron\Sirio\Observer;

use \Magento\Checkout\Model\Session;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Directory\Model\CountryFactory;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Directory\Model\CurrencyFactory;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\Locale\Resolver;
use Magento\Search\Model\QueryFactory;
use \Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Chiron\Sirio\Registry\EventsRegistry;
use Chiron\Sirio\Model\Config as SirioConfig;

abstract class Base implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var ConfigProvider $configProvider
     */
    protected $configProvider;

    /**
     * @var Observer $observer
     */
    protected $observer;

    
    /**
     * @var StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var LoggerInterface $loggerInterface
     */
    protected $loggerInterface;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var EventsRegistry
     */
    protected $eventList;
	
	/**
	 * @var PageFactory
	 */
	protected $pageFactory;
 
	/**
	 * @var SirioConfig
	 */
	protected $sirioConfig;
	
	/**
	 * @var \Magento\Framework\Registry
	 */
	protected $registry;
	
	/**
	 * @var StoreManagerInterface
	 */
	private $storeConfig;
	
	/**
	 * @var CurrencyFactory
	 */
	private $currencyCode;
	
	/**
	 * @var RequestInterface
	 */
	protected $request;
	
	/**
	 * @var Resolver
	 */
	protected $locale;
	
	/**
	 * @var QueryFactory
	 */
	protected $query;
	
	
	
	
	/**
     * Base constructor.
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param StoreManagerInterface $storeManagerInterface
     * @param LoggerInterface $loggerInterface
     * @param CountryFactory $countryFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param OrderRepositoryInterface $orderRepository
	 * @param SirioConfig $sirioConfig
	 * @param StoreManagerInterface $storeConfig
	 * @param CurrencyFactory $currencyFactory
	 * @param RequestInterface $request
	 * @param Resolver $request
     */
    public function __construct(
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        StoreManagerInterface $storeManagerInterface,
        LoggerInterface $loggerInterface,
        CountryFactory $countryFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        OrderRepositoryInterface $orderRepository,
        EventsRegistry $eventList,
		PageFactory $pageFactory,
		Registry $registry,
		SirioConfig $sirioConfig,
		StoreManagerInterface $storeConfig,
		CurrencyFactory $currencyFactory,
		RequestInterface $request,
		Resolver $locale,
		QueryFactory $query
	
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->loggerInterface = $loggerInterface;
        $this->countryFactory = $countryFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->orderRepository = $orderRepository;
        $this->eventList = $eventList;
        $this->pageFactory = $pageFactory;
		$this->sirioConfig = $sirioConfig;
		$this->registry = $registry;
		$this->storeConfig = $storeConfig;
		$this->currencyCode = $currencyFactory->create();
		$this->request = $request;
		$this->locale = $locale;
		$this->query = $query;
		
    }

    /**
     * @param string $xmlPath
     * @return bool
     */
    protected function isEnabled($path)
    {
        return (bool) $this->sirioConfig->isEnabled($path);
    }

    protected function createEvent($observer = null)
    {
        if ($this->eventList->get()) {
            return;
        }
        $this->eventList->set($this->observer);
        return $this->getSirioEvent();
    }

    /**
     * @return \Magento\Quote\Model\Quote|null
     */
    protected function getCart()
    {
        try {
            return $this->checkoutSession->getQuote();
        } catch (\Exception $exception) {
            $this->logError($exception->getMessage());
            return null;
        }
    }


    /**
     * @param $items
     * @return array
     */
    protected function makeItemArray($items)
    {
        $itemArray = [];
        foreach ($items as $item) {
            if (!$item->getParentItem()) {
                $data = [
                    'title' => $item->getName(),
                    'price' => round($item->getBaseRowTotalInclTax()/$item->getQty(),2),
					"sku"=>$item->getSku(),
					"qty"=>round($item->getQty()),
					"name"=>$item->getName(),
					"discount_amount"=>$item->getBaseDiscountAmount()
                ];
                $itemArray [] = $data;
            }
        }

        return $itemArray;
    }

    
	protected function getHeaders(){
		$header_request = getallheaders();
		$header_response = headers_list();
		$header_response_status_code = http_response_code();
		
		$header_response_filtered = array();
		
		foreach ($header_response as $response) {
			$explode_pos = strpos($response,':');
			$key = substr($response, 0, $explode_pos);
			if($key !== 'Link'){
				$value = substr($response, $explode_pos);
				$header_response_filtered[] = array($key, $value);
			}
		}
		
		$headers = array(
			'request'=>array(
				'Accept-Encoding'=>$header_request['Accept-Encoding'],
				'Accept-Language'=>$header_request['Accept-Language'],
				'Cookie'=>$header_request['Cookie']
			),
			'response'=>array(
				$header_response_filtered,
				'status_code'=>$header_response_status_code
			)
		);
		
		$script = 'var sirioCustomObject = {};
	 				 sirioCustomObject.headers = '.json_encode($headers).';';
		
		return $script;
	}
	
	
	
	protected function getLocale(){
		$currentCode = $this->locale->getLocale();
		return strstr($currentCode, '_', true);
	}
	
	public function getLimit()
	{
		return $this->sirioConfig->getConfig('catalog/frontend/grid_per_page');
	}
	
	/**
	 * @return string
	 */
	public function getCurrentCurrencyCode()
	{
		$currentCurrency = $this->storeConfig->getStore()->getCurrentCurrencyCode();
		return $currentCurrency;
	}
	
	/**
	 * @param $message
	 */
	protected function logError($message)
	{
		if ($this->isEnabled(SirioConfig::XML_PATH_DEBUG_ENABLED)) {
			$this->loggerInterface->error($message);
		}
	}
   

}
