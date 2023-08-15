<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Web\HttpClient;
use \Bitrix\Main\Config\Option;
use \Bitrix\Sale\Order;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \cloudpayments\CloudpaymentsKassa\CKassaTable;

$arClassesList = array(
    "cloudpayments\\CloudpaymentsKassa\\CKassaTable"					=> "lib/CKassa.php",
);
Loader::registerAutoLoadClasses("cloudpayments.cloudpaymentskassa",$arClassesList);
Loc::loadMessages(__FILE__);
class CCloudpaymentskassa{
    const MODULE_ID="cloudpayments.cloudpaymentskassa";

    const ACTIVE_URL="https://api.cloudpayments.ru/kkt/receipt";

    public static function GetOptions($site){
        return unserialize(Option::get(self::MODULE_ID,'SETTINGS',"",$site));
    }

    public static function OnSaleOrderPaid($ENTITY){
        $order=$ENTITY->getParameter('ENTITY');
        $arFields=array(
            'PAY_ID'=>$order->getField('ID'),
            'VALUE'=>$order->getField('PAYED'),
        );
        $PAY_SYSTEM_ID=$order->getField('PAY_SYSTEM_ID');
        $option=self::GetOptions($order->getField("LID"));
        if($arFields['VALUE']=='Y' && in_array($PAY_SYSTEM_ID,$option['PAY_SYSTEM_ID'])){
            self::GetResult($order->getField('ID'),$arFields['VALUE']);
        }
    }
    public static function GetResult($order_ID,$TYPE){
        $data=array();
            $order= Order::load($order_ID);
            $option= self::GetOptions($order->getField("LID"));
            $basket = $order->getBusket();
            $basketItems = $basket->getBasketItems();
            $Property=$order->getPropertyCollection()->getArray();
            foreach($Property['properties'] as $prop){
                if($prop['IS_EMAIL']=='Y' && $option['EMAIL']=='Y'){
                    $data['EMAIL']=current($prop['VALUE']);
                }
                if($prop['IS_PHONE']=='Y' && $option['PHONE']=='Y'){
                    $data['PHONE']=current($prop['VALUE']);
                }
            }
            $total=0;
            $POINTS=0;
            
            if(empty($option['PAY_SYSTEM_ID_OUT'])) $option['PAY_SYSTEM_ID_OUT']=9;
            
            $paymentCollection = $order->getPaymentCollection();
            foreach ($paymentCollection as $payment)
            {
              if($payment->getPaymentSystemId()==$option['PAY_SYSTEM_ID_OUT'] && $payment->isPaid()):
                $POINTS=$POINTS+$payment->getSum();
              endif;
            }
            
            $items=array();
            foreach ($basketItems as $basketItem) {
                $prD=\Bitrix\Catalog\ProductTable::getList(
                    array(
                        'filter'=>array('ID'=>$basketItem->getField('PRODUCT_ID')),
                        'select'=>array('VAT_ID'),
                    )
                )->fetch();
                $PRICE=$basketItem->getField('PRICE');
                $total=$total+($PRICE*$basketItem->getQuantity());
                
            }
            
            //Добавляем доставку
            if ($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID"))
            {
                $PRODUCT_PRICE_DELIVERY=$order->getDeliveryPrice();
                $total=$total+$PRODUCT_PRICE_DELIVERY;
            }
            
        /** Оплата баллам (внутренний счет покупателя) **/
            if($total>0 && $POINTS>0 && $option['POINTS']!='Y'):
              $PRICE_INDEX = 1-($POINTS/$total);
            endif;
            
            $object = Option::get("cloudpayments.cloudpaymentskassa", "PREDMET_RASCHETA1", "") ?: 0;
            $method = Option::get("cloudpayments.cloudpaymentskassa", "SPOSOB_RASCHETA1", "") ?: 0;
            
            foreach ($basketItems as $basketItem) {
                $prD=\Bitrix\Catalog\ProductTable::getList(
                    array(
                        'filter'=>array('ID'=>$basketItem->getField('PRODUCT_ID')),
                        'select'=>array('VAT_ID'),
                    )
                )->fetch();
                if($prD){
                    if($prD['VAT_ID']==0){
                        $nds=null;
                    }
                    else{
                        $nds=floatval($basketItem->getField('VAT_RATE'))==0 ? 0 : $basketItem->getField('VAT_RATE')*100;
                    }
                }else{
                    $nds=null;
                }
                
                $items[]=array(
                    'label'=>$basketItem->getField('NAME'),
                    'price'=>(float)number_format($basketItem->getField('PRICE'),2,".",''),
                    'quantity'=>$basketItem->getQuantity(),
                    'amount'=>(float)number_format($basketItem->getField('PRICE')*$basketItem->getQuantity(),2,".",''),
                    'vat'=>$nds,
                    "object" => $object,
                    "method" => $method
                );
                unset($PRICE);
            }
            

            //Добавляем доставку
            if($order->getDeliveryPrice() > 0 and $order->getField("DELIVERY_ID")) {
              $items[] = array(
                'label' => GetMessage("DELIVERY_TXT"),
                'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                'quantity' => 1,
                'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
                'vat' => $option['VAT_DELIVERY'.$order->getField("DELIVERY_ID")] ?: NULL,
                'object' => "4",
                'method' => $method,
              );
            }
          
            
            $l = $paymentCollection[0];
            $payid=$l->getField("ID");
            $fl=$order->getPaymentCollection()->getPaidSum();
            $data['ITEMS']=$items;
            $data['ORDER_ID']=$order->getField("ID");
            $data['TYPE']=$TYPE=='Y' ? 'Income' : 'IncomeReturn';
            $data['USER_ID']=$order->getField('USER_ID');
            $data['LID']=$order->getField("LID");
            $data['PAY_ID']=$payid;
            $data['SUMMA']=$fl;
            $data['PRICE_INDEX']=$PRICE_INDEX;
            $data['TOTAL']=$total;
            $data['POINTS']=$POINTS;
            
            self::SendData($data,$option);
    }


  public static function OnCloudpaymentKassaStatusUpdate($ORDER_ID, $STATUS_ID)
  {
    if (empty($ORDER_ID))
      return;
    
    $STATUS_TWOCHECK = Option::get("cloudpayments.cloudpaymentskassa", "STATUS_TWOCHECK", "");
    
    if (empty($STATUS_TWOCHECK))
      return;
    
    if($STATUS_ID == $STATUS_TWOCHECK)
      self::GetResult($ORDER_ID, "Y",4);
    
  }

  public static function SendData($data,$option){
        $error='';

        $request=array(
            'Inn'=>$option['INN'],
            'InvoiceId'=>$data['ORDER_ID'],
            'AccountId'=>$data['USER_ID'],
            'Type'=>$data['TYPE'],
            'CustomerReceipt'=>array('Items'=>$data['ITEMS'],'calculationPlace' => strval($option['CALCPLACE']),'taxationSystem'=>intval($option['NALOG_TYPE']),'email'=>$data['EMAIL'],'phone'=>$data['PHONE'] ?: ''),

        );
        
        /** Оплата баллам (внутренний счет покупателя) **/
        if ($option['POINTS']=='Y'):
          $request['CustomerReceipt']["amounts"]=array(
            "electronic"=>number_format($data['TOTAL']-$data['POINTS'], 2, ".", ''),
    		    "advancePayment"=>0, 
    		    "credit"=>0, 
    		    "provision"=>number_format($data['POINTS'], 2, ".", '')
          );
        else:
          $request['CustomerReceipt']["amounts"]=array(
            "electronic"=>number_format($data['TOTAL']-$data['POINTS'], 2, ".", ''),
    		    "advancePayment"=>0, 
    		    "credit"=>0, 
    		    "provision"=>0
          );
        endif;
        
        $request=json_encode($request,JSON_UNESCAPED_UNICODE);
        $str=$data['TYPE'].$data['ORDER_ID'].$data['USER_ID'].$data['EMAIL'];
        
        $httpClient = new HttpClient();
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('X-Request-ID', md5($str), true);
        $httpClient->setAuthorization(trim($option['APIKEY']), trim($option['APIPSW']));
        $content = $httpClient->post(self::ACTIVE_URL, $request);
        $out=self::Object_to_array(json_decode($content));
        if ($out['Success'] !== false){
            $fields=array(
                'LID'=>$data['LID'],
                'ORDER_ID'=>$data['ORDER_ID'],
                'PAY_ID'=>$data['PAY_ID'],
                'STATUS'=>'Y',
                'SUMMA'=>$data['SUMMA'],
                'TYPE'=>$data['TYPE'],
            );
            CKassaTable::add($fields);
        }else{
            $error .= $out['Message'];
        }

    }
    public static function Object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = self::Object_to_array($value);
            }
            return $result;
        }
        return $data;
    }


    public static function CheckHMac($APIPASS,$server){

        $headers = self::detallheaders($server);
        if (!((!isset($headers['Content-HMAC'])) and (!isset($headers['Content-Hmac'])))) {
            $message = file_get_contents('php://input');
            $s = hash_hmac('sha256', $message, $APIPASS, true);
            $hmac = base64_encode($s);
            return (!array_key_exists('Content-HMAC',$headers) && !array_key_exists('Content-Hmac',$headers) || (array_key_exists('Content-HMAC',$headers) && $headers['Content-HMAC'] != $hmac) || (array_key_exists('Content-Hmac',$headers) && $headers['Content-Hmac'] != $hmac));

        }


    }
    public static function detallheaders($server){
        if (!is_array($server))
            return array();
        
        $headers = array();
        foreach ($server as $name => $value)
            if (substr($name, 0, 5) == 'HTTP_')
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        
        return $headers;
    }

    public static function OnAdminContextMenuShowHandler(&$items) {
        if (
          $GLOBALS['APPLICATION']->GetCurPage() !='/bitrix/admin/sale_order_view.php' or
          $GLOBALS['APPLICATION']->GetCurPage() !='/bitrix/admin/sale_order_edit.php'
        ) return;

        if (
          array_key_exists('ID', $_REQUEST) and
          $_REQUEST['ID']>0 && Loader::includeModule('sale')
        ) {
            $post = Application::getInstance()->getContext()->getRequest()->getQueryList()->toArray();
            $order = Order::load($_REQUEST['ID']);
            
            if ($post["itr_cld_pay"] == "y" && check_bitrix_sessid())
              self::GetResult($_REQUEST['ID'],'Y');
            elseif($post["itr_cld_refund"] == "y" && check_bitrix_sessid())
                self::GetResult($_REQUEST['ID'],'N');
            
            $newItem = array(
                'TEXT' =>Loc::getMessage('VBCH_CLD_KASSA_ORDER_BUTTON'),
                'TITLE' =>Loc::getMessage('VBCH_CLD_KASSA_ORDER_BUTTON_DESC'),
                'LINK' => '#',
                'ICON' => 'btn_settings',
                'MENU' => array(),
            );
          
            $newItem['MENU'][]=array('ICON' => '',
                        'TEXT' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK'),
                        'TITLE' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK'),
                        'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('itr_cld_pay=y&'.bitrix_sessid_get(), array('itr_cld_pay','itr_cld_refund', 'sessid')),
                    );

            $newItem['MENU'][] = array('ICON' => '',
                        'TEXT' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK_REFUND'),
                        'TITLE' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK_REFUND'),
                        'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('itr_cld_refund=y&'.bitrix_sessid_get(), array('itr_cld_pay','itr_cld_refund', 'sessid')),
                    );
            
            $items[]=$newItem;
            }
        }
        

}