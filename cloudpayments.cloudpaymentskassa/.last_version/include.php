<?
use \Bitrix\Main\Localization\Loc;
use \cloudpayments\CloudpaymentsKassa;
$arClassesList = array(
    "cloudpayments\\CloudpaymentsKassa\\CKassaTable"					=> "lib/CKassa.php",
);
\Bitrix\Main\Loader::registerAutoLoadClasses("cloudpayments.cloudpaymentskassa",$arClassesList);


Loc::loadMessages(__FILE__);
class CCloudpaymentskassa{
    const MODULE_ID="cloudpayments.cloudpaymentskassa";

    const ACTIVE_URL="https://api.cloudpayments.ru/kkt/receipt";

    public function GetOptions($site){
        return unserialize(\Bitrix\Main\Config\Option::get(self::MODULE_ID,'SETTINGS',"",$site));
    }

    public function OnSaleOrderPaid($ENTITY){
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
    public function GetResult($order_ID,$TYPE){
        $data=array();
            $order=\Bitrix\Sale\Order::load($order_ID);
            $option=self::GetOptions($order->getField("LID"));
            $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
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

                if($PRICE_INDEX>0 && $option['POINTS']!='Y') $PRICE=$basketItem->getField('PRICE')*$PRICE_INDEX;
                else $PRICE=$basketItem->getField('PRICE');

                $number=number_format($PRICE,2,".",'');

                $items[]=array(
                    'label'=>$basketItem->getField('NAME'),
                    'price'=>(float)number_format($number,2,".",''),
                    'quantity'=>$basketItem->getQuantity(),
                    'amount'=>(float)number_format($PRICE*$basketItem->getQuantity(),2,".",''),
                    'vat'=>$nds,
                );
                unset($PRICE);  
            }
            

            //Добавляем доставку
            if ($order->getDeliveryPrice() > 0 && $order->getField("DELIVERY_ID")) 
            {
                if($PRICE_INDEX>0 && $order->getDeliveryPrice()>0 && $option['POINTS']!='Y') $PRODUCT_PRICE_DELIVERY=$order->getDeliveryPrice()*$PRICE_INDEX;
                else $PRODUCT_PRICE_DELIVERY=$order->getDeliveryPrice(); 
                
                if ($option['VAT_DELIVERY'.$order->getField("DELIVERY_ID")]) $nds_delivery=$option['VAT_DELIVERY'.$order->getField("DELIVERY_ID")];
                else $nds_delivery=$nds;
                
                $items[] = array(
                    'label' => Loc::getMessage('DELIVERY_TXT'),
                    'price' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                    'quantity' => 1,
                    'amount' => number_format($PRODUCT_PRICE_DELIVERY, 2, ".", ''),
                    'vat' => $nds_delivery,  
                );
                unset($PRODUCT_PRICE_DELIVERY);
                unset($PRODUCT_PRICE);
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
       //// }

    }


    function addError2($txt)
    {
          $file = $_SERVER['DOCUMENT_ROOT'].'/cloud_log3.txt';
          $current = file_get_contents($file);
          $current .= $txt."\n";
          file_put_contents($file, $current);
    }
    

    public function SendData($data,$option){
        $request=array();
        $error='';

        $request=array(
            'Inn'=>$option['INN'],
            'InvoiceId'=>$data['ORDER_ID'],
            'AccountId'=>$data['USER_ID'],
            'Type'=>$data['TYPE'],
            'CustomerReceipt'=>array('Items'=>$data['ITEMS'],'taxationSystem'=>intval($option['NALOG_TYPE']),'email'=>$data['EMAIL'],'phone'=>$data['PHONE'] ? $data['PHONE'] : ''),

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
        $reque=md5($str);
        $ch = curl_init(self::ACTIVE_URL);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch,CURLOPT_USERPWD,trim($option['APIKEY']).":".trim($option['APIPSW']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","X-Request-ID:".$reque));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
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
            \cloudpayments\CloudpaymentsKassa\CKassaTable::add($fields);
        }else{
            $error .= $out['Message'];
        }

    }
    private function Object_to_array($data)
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


    public function CheckHMac($APIPASS,$server){

        $headers = self::detallheaders($server);
        if (!((!isset($headers['Content-HMAC'])) and (!isset($headers['Content-Hmac'])))) {
            $message = file_get_contents('php://input');
            $s = hash_hmac('sha256', $message, $APIPASS, true);
            $hmac = base64_encode($s);
            return (!array_key_exists('Content-HMAC',$headers) && !array_key_exists('Content-Hmac',$headers) || (array_key_exists('Content-HMAC',$headers) && $headers['Content-HMAC'] != $hmac) || (array_key_exists('Content-Hmac',$headers) && $headers['Content-Hmac'] != $hmac));

        }


    }
    private function detallheaders($server){
        if (!is_array($server)) {
            return array();
        }
        $headers = array();
        foreach ($server as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public static function OnAdminContextMenuShowHandler(&$items) {
        $income=true;$ReFund=true;
        if ($GLOBALS['APPLICATION']->GetCurPage()=='/bitrix/admin/sale_order_view.php' || $GLOBALS['APPLICATION']->GetCurPage()=='/bitrix/admin/sale_order_edit.php') {
            if (array_key_exists('ID', $_REQUEST) && $_REQUEST['ID']>0 && \Bitrix\Main\Loader::includeModule('sale')) {
                $post = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQueryList()->toArray();
                $order=\Bitrix\Sale\Order::load($_REQUEST['ID']);
                $TYPE=$order->isPaid() ? 'Y' : "N";
                $res=\cloudpayments\CloudpaymentsKassa\CKassaTable::getList(array(
                    'filter'=>array('ORDER_ID'=>$_REQUEST['ID'],'LID'=>$order->getField("LID")),
                    'select'=>array("ID","TYPE"),
                ))->fetchAll();
                foreach($res as $r){
                    if($r['TYPE']=='Income'){
                        $income=false; $ReFund=true;
                    }
                    if($r['TYPE']=='IncomeReturn'){
                        $ReFund=false;$income=true;
                    }
                }
                if ($post["itr_cld_pay"] == "y" && check_bitrix_sessid())
                {
                    self::GetResult($_REQUEST['ID'],'Y');

                }elseif($post["itr_cld_refund"] == "y" && check_bitrix_sessid()){
                    self::GetResult($_REQUEST['ID'],'N');
                }
                $newItem = array(
                    'TEXT' =>Loc::getMessage('VBCH_CLD_KASSA_ORDER_BUTTON'),
                    'TITLE' =>Loc::getMessage('VBCH_CLD_KASSA_ORDER_BUTTON_DESC'),
                    'LINK' => '#',
                    'ICON' => 'btn_settings',
                    'MENU' => array(),
                );
              //  if($income && !$ReFund)
                $newItem['MENU'][]=array('ICON' => '',
                            'TEXT' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK'),
                            'TITLE' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK'),
                            'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('itr_cld_pay=y&'.bitrix_sessid_get(), array('itr_cld_pay','itr_cld_refund', 'sessid')),
                        );
//                if($ReFund && !$income)
                $newItem['MENU'][] = array('ICON' => '',
                            'TEXT' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK_REFUND'),
                            'TITLE' => Loc::getMessage('VBCH_CLD_KASSA_ORDER_CREATE_CHECK_REFUND'),
                            'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('itr_cld_refund=y&'.bitrix_sessid_get(), array('itr_cld_pay','itr_cld_refund', 'sessid')),
                        );
                    $items[]=$newItem;

                }
            }
        }

}