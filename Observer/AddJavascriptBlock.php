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
	
	const LAYOUT_BLOCK_NAME_FIRST = 'head.additional';
	const LAYOUT_BLOCK_NAME_SECOND = 'after.body.start';
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
		
		try {
        	$this->layout = $observer->getLayout();
			
			$blockContent = $this->createEvent($observer);
			$block = $this->layout->createBlock('Magento\Framework\View\Element\Text');
			$block->setText($blockContent);
			$head = $this->layout->getBlock(self::LAYOUT_BLOCK_NAME_FIRST);
			if($head){
				$head->append($block);
				
			}
			else{
				$head = $this->layout->getBlock(self::LAYOUT_BLOCK_NAME_SECOND);
				if($head){
					$head->append($block);
				}
			}

			$this->registry->register(self::SIRIO_REGISTRY_NAME, true);
						
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
		$this->getHeaders();
        $this->getIpAddress();
        $this->getCurrency();
        $this->getLocale();
		
			
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
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     //]]>
                 </script>';
		
	}

    private function appendHomeJS() {
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.pageType = "home";
                     //]]>
                 </script>';
		
	}
	private function appendProductJS() {
		$current_product = $this->registry->registry('current_product');
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.productDetails = {"sku":"'.$current_product->getSku().'","name":"'.$current_product->getName().'","image":"'.$current_product->getImageUrl().'","description":"'.$this->cleanTextProduct($current_product->getDescription()).'","price":"'.$current_product->getPrice().'","special_price":"'.$current_product->getSpecialPrice().'"};
                     sirioCustomObject.pageType = "product";
                     //]]>
                 </script>';
	}
	
	private function appendProductCategoryJS() {
		$limit = $this->getLimit();
		$page = $this->request->getParam('p')?$this->request->getParam('p'):1;
		$current_category = $this->registry->registry('current_category');
		$products_count = $limit;
		$max_product_count = $current_category->getProductCollection()->count();
		//$max_product_count = $current_category->getProductCount();
		
		if($max_product_count % $limit > 0){
			$pages = (int)($max_product_count / $limit) + 1 ;
		}
		else{
			$pages = $max_product_count / $limit ;
		}
		if($page == $pages){
			$products_count = $max_product_count % $limit;
		}
		
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.categoryDetails = {"name":"'.$current_category->getName().'","image":"'.$current_category->getImageUrl().'","description":"'.$this->cleanTextCategory($current_category->getDescription()).'"};
                     sirioCustomObject.pageType = "category";
                     sirioCustomObject.numProducts = '.$products_count.';
                     sirioCustomObject.pages = '.$pages.';
                     sirioCustomObject.currentPage = '.$page.';
                     //]]>
                 </script>';
	}
	
	private function appendProductSearchJS() {
		$limit = $this->getLimit();
		$page = $this->request->getParam('p')?$this->request->getParam('p'):1;
		$products_count = $limit;
		$max_product_count = $this->query->get()->getNumResults();
		if($max_product_count % $limit > 0){
			$pages = (int)($max_product_count / $limit) + 1 ;
		}
		else{
			$pages = $max_product_count / $limit ;
		}
		if($page == $pages){
			$products_count = $max_product_count % $limit;
		}
		if ($this->request->getParam('q')) {
			$this->script.='sirioCustomObject.query = "' . $this->request->getParam('q') . '";';
		}
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.pageType = "search";
                     sirioCustomObject.numProducts = '.$products_count.';
                     sirioCustomObject.pages = '.$pages.';
                     sirioCustomObject.currentPage = '.$page.';
                     //]]>
                 </script>';
	}
	
	
	private function appendCheckoutJS() {
		
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.pageType = "checkout";
                     //]]>
                 </script>';
	}
	
	private function appendCheckoutSuccessJS() {
		if(isset($_COOKIE['sirio_cart'])){
			unset($_COOKIE['sirio_cart']);
		}
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.pageType = "checkout_success";
                     //]]>
                 </script>';
	}
	
	private function appendCheckoutFailureJS() {
		if(isset($_COOKIE['sirio_cart'])){
			setcookie('sirio_cart', "", 1);
		}
		return $this->getProfiling().'<script type="text/javascript">
                     //<![CDATA[
                     '.$this->script.'
                     sirioCustomObject.pageType = "checkout_failure";
                     //]]>
                 </script>';
		
	}

	
}
