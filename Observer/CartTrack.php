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

class CartTrack extends Base
{
	const SIRIO_REGISTRY_NAME = 'sirio_cart';

    /**
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {	
		if(isset($_COOKIE['sirio_cart'])){
			setcookie('sirio_cart', "", 1);
		}

		if ($this->registry->registry(self::SIRIO_REGISTRY_NAME)) {
			return;
		}
		
		if (!$this->isEnabled(SirioConfig::XML_PATH_SIRIO_ENABLED)) {
            return;
        }

		
		$this->observer = $observer;
        $this->observerType = $observer->getEvent()->getname();
		$this->registry->register(self::SIRIO_REGISTRY_NAME, true);
		
		if($this->observerType == 'layout_load_before'){
			if(isset($_SERVER['HTTP_REFERER']) && !in_array($this->request->getFullActionName(), array('checkout_cart_index'))){
				return;
			}
		}
		
		try {
			$this->createEvent();
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
		
		try {
			
			$quoteItem = $this->observer->getQuoteItem();
			if($quoteItem==null){
				$quoteItem = $this->observer->getItem();
				if($quoteItem==null){
					$cart = $this->getCart();
				}
				else{
					$cart = $quoteItem->getQuote();
				}
			}
			else{
				$cart = $quoteItem->getQuote();
			}
			
        	
			
			$items = $cart->getAllItems();

			$itemArray = $this->makeItemArray($items);
			
			
			$coupon = $cart->getCouponCode();
			$shipping = $cart->getShippingAddress()->getBaseShippingInclTax();
			$subtotal = $cart->getBaseSubtotal();
			$total = $cart->getBaseGrandTotal();
			$discount = $subtotal - $cart->getBaseSubtotalWithDiscount();
			
			/*
					quando questa funzione viene chiamata:
					metto in sirio_cart il carrello attuale
			*/
			$products = array();
			foreach($itemArray as $item){
				
				$products[] = array(
					"item_id"=>$item['item_id'],
					"sku"=>$item['sku'] ,
					"product_options"=>$item['product_options'],
					"price"=>$item['price'],
					"qty"=>$item['qty'],
					"name"=>$item['name'],
					"discount_amount"=>$item['discount_amount']
				);
			}

			$cart_full = '{"action_type":"'.$this->getActionType().'","cart_total":'.$total.',"cart_subtotal":'.$subtotal.',"shipping":'.$shipping.',"coupon_code":"'.$coupon.'","discount_amount":'.$discount.',"cart_products":'.json_encode($products).'}';
			if(isset($_COOKIE['sirio_cart'])){
				setcookie('sirio_cart', "", 1);
			}
			setcookie('sirio_cart', base64_encode($cart_full), time() + (86400 * 30), "/");
			
		} catch (\Exception $exception) {
			$this->logError($exception->getMessage());
		}
		return;
	}
}
