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

    /**
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->isEnabled(SirioConfig::XML_PATH_SIRIO_ENABLED)) {
            return;
        }
        
        $this->observer = $observer;
        $this->observerType = $observer->getEvent()->getname();

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
			
			$cart = $this->getCart();
			$items = $cart->getItems();
			$itemArray = $this->makeItemArray($items);
			
			$coupon = $cart->getCouponCode();
			$shipping = $cart->getShippingAddress()->getBaseShippingInclTax();
			$subtotal = $cart->getBaseSubtotal();
			$total = $cart->getBaseGrandTotal();
			$discount = $subtotal - $cart->getBaseSubtotalWithDiscount();
			
			/*
					quando questa funzione viene chiamata:
					metto in cart_new il carrello attuale
			*/
			$products = array();
			foreach($itemArray as $item){
				$products[] = array(
					"sku"=>$item['sku'] ,
					"price"=>$item['price'],
					"qty"=>$item['qty'],
					"name"=>$item['name'],
					"discount_amount"=>$item['discount_amount']
				);
			}
			$cart_full = '{"cart_total":'.$total.', "cart_subtotal":'.$subtotal.', "shipping":'.$shipping.', "coupon_code":'.$coupon.', "discount_amount":'.$discount.', "cart_products":'.json_encode($products).'}';
			if(isset($_COOKIE['cart_new'])){
				setcookie('cart_new', "", 1);
			}
			setcookie('cart_new', base64_encode($cart_full), time() + (86400 * 30), "/");
		} catch (\Exception $exception) {
			$this->logError($exception->getMessage());
		}
		return;
	}
}
