<?php
/**
 * @category Chiron
 * @package Chiron_Sirio
 * @version 0.1.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
	
namespace Chiron\Sirio\Observer;

use \Magento\Framework\Event\Observer;
use Chiron\Sirio\Model\Config as SirioConfig;

class AddJavascriptBlock extends Base
{
	
	protected $layout;
	
	const LAYOUT_BLOCK_NAME = 'head.additional';
	const SIRIO_REGISTRY_NAME = 'sirio_head';
	
    /**
     * Event name: layout_generate_blocks_after
     *
     * @param Observer $observer
     */
	public function execute(Observer $observer)
    {
		if ($this->registry->registry(self::SIRIO_REGISTRY_NAME)) {
			return;
		}
		
		if (!$this->isEnabled(SirioConfig::XML_PATH_SIRIO_ENABLED)) {
            return;
        }
		
        $this->observer = $observer;
        $this->observerType = $observer->getEvent()->getName();
		$this->registry->register(self::SIRIO_REGISTRY_NAME, true);
        
        try {
        	$this->layout = $observer->getLayout();
			$blockContent = $this->createEvent($observer);
			$block = $this->layout->createBlock('Magento\Framework\View\Element\Text');
			$block->setText($blockContent);
			$head = $this->layout->getBlock(self::LAYOUT_BLOCK_NAME);
			$head->append($block);
						
        } catch (\Exception $exception) {
        	$this->logError($exception->getMessage());
            return;
        }
    }
	
	
	/**
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function getSirioEvent() {
	
		$this->headers = $this->getHeaders();
		
		$route_name = $this->request->getRouteName();
		$controller_name = $this->request->getControllerName();
		$controller_action = $this->request->getActionName();
		
		if($route_name == 'cms' && $this->request->getFullActionName() == 'cms_index_index') {
			return $this->appendHomeJS();
		}
		else if ($controller_name === 'product' && $controller_action === 'view') {
			return $this->appendProductJS();
		}
		else if ($controller_name === 'category' && $controller_action === 'view') {
			return $this->appendProductCategoryJS();
		}
		else if ($controller_name === 'result' && $controller_action === 'index') {
			return $this->appendProductSearchJS();
		}
		else if (($route_name === 'onepage' || $route_name === 'opc') && $controller_name === 'index' && $controller_action === 'index') {
			return $this->appendCheckoutJS();
		}
		else if ($controller_name === 'onepage' && $controller_action === 'success') {
			return $this->appendCheckoutSuccessJS();
		}
		else if ($controller_name === 'onepage' && $controller_action === 'failure') {
			return $this->appendCheckoutFailureJS();
		}
		else{
			return $this->appendDefaultJS();
		}
 	}
	
	private function appendDefaultJS() {
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
		
	}

    private function appendHomeJS() {
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.pageType = "home";
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
		
	}
	private function appendProductJS() {
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		$current_product = $this->registry->registry('current_product');
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.productDetails = {"sku":"'.$current_product->getSku().'","name":"'.$current_product->getName().'","image":"'.$current_product->getImageUrl().'","description":"'.$current_product->getDescription().'","price":"'.$current_product->getPrice().'","special_price":"'.$current_product->getSpecialPrice().'"};
                     sirioCustomObject.pageType = "product";
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
	}
	
	private function appendProductCategoryJS() {
		$locale = $this->getLocale();
		$limit = $this->getLimit();
		$page = $this->request->getParam('p')?$this->request->getParam('p'):1;
		$current_category = $this->registry->registry('current_category');
		$products_count = $limit;
		$max_product_count = $current_category->getProductCount();
		$currency_code = $this->getCurrentCurrencyCode();
		
		if($max_product_count % $limit > 0){
			$pages = (int)($max_product_count / $limit) + 1 ;
		}
		else{
			$pages = $max_product_count / $limit ;
		}
		if($page == $pages){
			$products_count = $max_product_count % $limit;
		}
		
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.categoryDetails = {"name":"'.$current_category->getName().'","image":"'.$current_category->getImageUrl().'","description":"'.$current_category->getDescription().'"};
                     sirioCustomObject.pageType = "category";
                     sirioCustomObject.numProducts = '.$products_count.';
                     sirioCustomObject.pages = '.$pages.';
                     sirioCustomObject.currentPage = '.$page.';
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
	}
	
	private function appendProductSearchJS() {
		$locale = $this->getLocale();
		$limit = $this->getLimit();
		$page = $this->request->getParam('p')?$this->request->getParam('p'):1;
		$products_count = $limit;
		$max_product_count = $this->query->get()->getNumResults();;
		$currency_code = $this->getCurrentCurrencyCode();
		if($max_product_count % $limit > 0){
			$pages = (int)($max_product_count / $limit) + 1 ;
		}
		else{
			$pages = $max_product_count / $limit ;
		}
		if($page == $pages){
			$products_count = $max_product_count % $limit;
		}
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.pageType = "search";
                     sirioCustomObject.numProducts = '.$products_count.';
                     sirioCustomObject.pages = '.$pages.';
                     sirioCustomObject.currentPage = '.$page.';
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
	}
	
	
	private function appendCheckoutJS() {
		
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.pageType = "checkout";
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
	}
	
	private function appendCheckoutSuccessJS() {
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		if(isset($_COOKIE['cart_new'])){
			unset($_COOKIE['cart_new']);
		}
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.pageType = "checkout_success";
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
	}
	
	private function appendCheckoutFailureJS() {
		$locale = $this->getLocale();
		$currency_code = $this->getCurrentCurrencyCode();
		if(isset($_COOKIE['cart_new'])){
			setcookie('cart_new', "", 1);
		}
		return
			'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->headers.'
                     sirioCustomObject.pageType = "checkout_failure";
                     sirioCustomObject.locale = "'.$locale.'";
                     sirioCustomObject.currency = "'.$currency_code.'";
                     //]]>
                 </script>';
		
	}
}
