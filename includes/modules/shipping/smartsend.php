
<?php
/**
 * @package shippingMethod
 * @copyright Copyright 2003-2009 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: storepickup.php 14498 2009-10-01 20:16:16Z ajeh $
 */
/**
 * Store-Pickup / Will-Call shipping method
 *
 */
class smartsend extends base {
  /**
   * $code determines the internal 'code' name used to designate "this" payment module
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
   */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
   */
  var $description;
  /**
   * module's icon
   *
   * @var string
   */
  var $icon;
  /**
   * $enabled determines whether this module shows or not... during checkout.
   *
   * @var boolean
   */
  var $enabled;
  /**
   * constructor
   *
   * @return storepickup
   */
  function smartsend() {
    global $order, $db;
    
        
    $this->code = 'smartsend';
    $this->title = MODULE_SHIPPING_SMARTSEND_TEXT_TITLE;
    $this->description = MODULE_SHIPPING_SMARTSEND_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_SHIPPING_STOREPICKUP_SORT_ORDER;
    $this->icon = '';
    
    
    $this->enabled = ((MODULE_SHIPPING_SMARTSEND_STATUS == 'True') ? true : false);


    if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_SMARTSEND_ZONE > 0) ) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . "
                             where geo_zone_id = '" . MODULE_SHIPPING_SMARTSEND_ZONE . "'
                             and zone_country_id = '" . $order->delivery['country']['id'] . "'
                             order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->delivery['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
      

    }
    
  }
  /**
   * Obtain quote from shipping system/calculations
   *
   * @param string $method
   * @return array
   */
  function quote($method = '') {
    global $order,$cart, $shipping_weight, $shipping_num_boxes, $total_weight, $currencies,$db;
    $this->quotes = array();
    
    $topostcode     = str_replace(" ","",($order->delivery['postcode']));
    $tocountrycode  = $order->delivery['country']['iso_code_2'];
    $tosuburb       = $order->delivery['suburb'];
    $sweight        = $shipping_weight;

    if($tosuburb == ''){
        $tosuburb       = $order->delivery['city'];
    }
    
    $post_url = "http://api.dev.smartsend.com.au/";
    
    
    # POST PARAMETER VALUES
    
    $post_param_values["METHOD"]                = "GetQuote";
    $post_param_values["FROMCOUNTRYCODE"]       = "AU";
    $post_param_values["FROMPOSTCODE"]          = "2000";
    $post_param_values["FROMSUBURB"]            = "SYDNEY";
    $post_param_values["TOCOUNTRYCODE"]         = $tocountrycode;
    $post_param_values["TOPOSTCODE"]            = $topostcode;
    $post_param_values["TOSUBURB"]              = $tosuburb;
    $post_param_values["RECEIPTEDDELIVERY"]     = MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY;
    $post_param_values["TRANSPORTASSURANCE"]    = MODULE_SHIPPING_SMARTSEND_TRANSPORTASSURANCE;
        
                
    
    # POST ITEMS VALUE
    foreach($order->products as $key => $data){
        $i = intval($data['id']);
             
        $products = $db->Execute("SELECT depth,length,height,description FROM smartsend_products WHERE id={$i}");    
        $products = $products->fields;
                
        $post_value_items["ITEM({$key})_HEIGHT"]         =  $products['height'];
        $post_value_items["ITEM({$key})_LENGTH"]         =  $products['length'];
        $post_value_items["ITEM({$key})_DEPTH"]          =  $products['depth'];
        $post_value_items["ITEM({$key})_WEIGHT"]         =  $data['weight'];
        $post_value_items["ITEM({$key})_DESCRIPTION"]    =  $products['description'];
       
    }

    
    $post_final_values = array_merge($post_param_values,$post_value_items);
    
    # POST PARAMETER AND ITEMS VALUE URLENCODE
    $post_string = "";
    foreach( $post_final_values as $key => $value )
            { $post_string .= "$key=" . urlencode( $value ) . "&"; }
    $post_string = rtrim( $post_string, "& " );

   //echo $post_url."?".$post_string;
    
    /*
    # START CURL PROCESS
    $request = curl_init($post_url); 
    curl_setopt($request, CURLOPT_HEADER, 0); 
    curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
    $post_response = curl_exec($request); 
    curl_close ($request); // close curl object    
    var_dump($post_response);
    */
    
    # test response
    $str_resp = "ACK=Success&QUOTE(0)_TOTAL=26.47&QUOTE(0)_SERVICE=Road&QUOTE(0)_ESTIMATEDTRANSITTIME=1-2%20business%20days&QUOTE(0)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(0)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTE(1)_TOTAL=102.92&QUOTE(1)_SERVICE=Overnight&QUOTE(1)_ESTIMATEDTRANSITTIME=Next%20business%20day&QUOTE(1)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(1)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTE(2)_TOTAL=150.15&QUOTE(2)_SERVICE=Overnight%20by%209am&QUOTE(2)_ESTIMATEDTRANSITTIME=Next%20business%20day%20delivered%20by%209am&QUOTE(2)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(2)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTECOUNT=3";
    
    # parse output
    parse_str($str_resp, $arr_resp);
    
    $quote_count = ((int) $arr_resp["QUOTECOUNT"]) - 1;
        
    # JAVASCRIPT MANIPULATION
    $script='<script src="includes/smartsend.js"></script>';
    
    # Initialise our arrays
    $this->quotes = array('id' => $this->code, 'module' => $this->title);
    $methods = array() ;
    
    # ASSIGNING VALUES TO ARRAY METHODS    
    for ($x=0; $x<=$quote_count; $x++)
    {
      $methods[] = array( 'id' => "quote{$x}",  'title' => "{$arr_resp["QUOTE({$x})_SERVICE"]}"." <label>{$arr_resp["QUOTE({$x})_ESTIMATEDTRANSITTIME"]}</label>".$script,'cost' => $arr_resp["QUOTE({$x})_TOTAL"] ) ;      
    }
   
    $sarray[]   = array(); 
    $resultarr  = array() ;

    foreach($methods as $key => $value) {
            $sarray[ $key ] = $value['cost'] ;
    }

    asort( $sarray ) ;

    foreach($sarray as $key => $value) {
            $resultarr[ $key ] = $methods[ $key ] ;
    }

    # ASSIGN QUOTES OF METHOD ARRAY VALUES
    $this->quotes['methods'] = array_values($resultarr) ;   // set it

    # SORT THE CHEAPEST
    if ($method) {

        foreach($methods as $temp) {
         $search = array_search("$method", $temp) ;
         if (strlen($search) > 0 && $search >= 0) {
             break;
            }
          } ;

        $this->quotes = array('id' => $this->code, 'module' => $this->title,'methods' => array( array('id' => $method,'title' => $temp['title'],'cost' => $temp['cost'] )));
    }    
    
    if ($this->tax_class > 0) {
      $this->quotes['tax'] = zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
    }

    if (zen_not_null($this->icon)) $this->quotes['icon'] = zen_image($this->icon, $this->title);

    return $this->quotes;  
    
  }
  
  /**
   * Check to see whether module is installed
   *
   * @return boolean
   */
  function check() {
    global $db;
    

    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_SMARTSEND_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    
        
    return $this->_check;

    
    
  }
  /**
   * Install the shipping module and its configuration settings
   *
   */
  function install() {
    global $db;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
        values ('Enable Smart Send', 'MODULE_SHIPPING_SMARTSEND_STATUS', 'True', 'Do you want to offer Smart Send plugin?', '66', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
   
    # USERCODE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('USER CODE', 'MODULE_SHIPPING_SMARTSEND_USERCODE', '', '', '66', '0', now())");

    # USERTYPE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('USER TYPE', 'MODULE_SHIPPING_SMARTSEND_USERTYPE', '', '', '66', '0', now())");

    # TRANSPORTASSURANCE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('TRANSPORT ASSURANCE', 'MODULE_SHIPPING_SMARTSEND_TRANSPORTASSURANCE', '0.00', '', '66', '0', now())");
    
    # TAILLIFT
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('TAIL LIFT', 'MODULE_SHIPPING_SMARTSEND_TAILLIFT', '0', '', '66', '0', 'zen_get_tail_class_title', 'zen_cfg_pull_down_tail_classes(',  now())");
                
    # RECEIPTEDDELIVERY
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('RECEIPTED DELIVERY', 'MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY', '', '', '66', '0', now())");

    
    # SERVICE TYPE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('SERVICE TYPE', 'MODULE_SHIPPING_SMARTSEND_SERVICETYPE', '0', '', '66', '0', 'zen_get_service_class_title', 'zen_cfg_pull_down_service_classes(',  now())");

    
    # RETURNURL
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('RETURN URL', 'MODULE_SHIPPING_SMARTSEND_RETURNURL', '', '', '66', '0', now())");

    # CANCELURL
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('CANCEL URL', 'MODULE_SHIPPING_SMARTSEND_CANCELURL', '', '', '66', '0', now())");

    # NOTIFYURL
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('NOTIFY URL', 'MODULE_SHIPPING_SMARTSEND_NOTIFYURL', '', '', '66', '0', now())");

    /*
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (
        configuration_title, 
        configuration_key, 
        configuration_value, 
        configuration_description, 
        configuration_group_id, 
        sort_order, 
        use_function, 
        set_function, 
        date_added)        
    values (
        'Tax Class', 
        'MODULE_SHIPPING_STOREPICKUP_TAX_CLASS', 
        '0', 
        'Use the following tax class on the shipping fee.', 
        '6', 
        '0', 
        'zen_get_tax_class_title', 
        'zen_cfg_pull_down_tax_classes(', 
        now())");
    */
    
    
    # install smartsend quotes table    
    $tables = $db->Execute("SHOW TABLES like 'smartsend_quotes'");
    if ($tables->RecordCount() <= 0) {
        $db->Execute("
            CREATE TABLE `smartsend_quotes` (
            `quotes_id` INT( 11 ) NOT NULL ,
            `order_id` INT( 11 ) NOT NULL ,
            `total` FLOAT NOT NULL ,
            `service` VARCHAR( 256 ) NOT NULL ,
            `transtime` VARCHAR( 256 ) NOT NULL ,
            `estimate_max` INT NOT NULL ,
            `estimate_min` INT NOT NULL ,
            PRIMARY KEY ( `quotes_id` )
            ) ENGINE = MYISAM");
    }
    
    $tables = $db->Execute("SHOW TABLES like 'smartsend_products'");    
    if ($tables->RecordCount() <= 0) {
        $db->Execute(" 
        CREATE TABLE IF NOT EXISTS `smartsend_products` (
          `description` varchar(20) NOT NULL,
          `id` int(11) NOT NULL,
          `depth` int(11) NOT NULL,
          `length` int(11) NOT NULL,
          `height` int(11) NOT NULL,
          KEY `id` (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
    }
    
    
  }
  /**
   * Remove the module and all its settings
   *
   */
  function remove() {
    global $db;
    #$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_SHIPPING\_SMARTSEND\_%'");
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_group_id = 66");
  }
  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
   */
  function keys() {      
      
    return array(
        'MODULE_SHIPPING_SMARTSEND_STATUS', 
        'MODULE_SHIPPING_SMARTSEND_USERCODE',
        'MODULE_SHIPPING_SMARTSEND_USERTYPE',
        'MODULE_SHIPPING_SMARTSEND_TRANSPORTASSURANCE',
        'MODULE_SHIPPING_SMARTSEND_TAILLIFT',
        'MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY',
        'MODULE_SHIPPING_SMARTSEND_SERVICETYPE',
        'MODULE_SHIPPING_SMARTSEND_RETURNURL',
        'MODULE_SHIPPING_SMARTSEND_CANCELURL',
        'MODULE_SHIPPING_SMARTSEND_NOTIFYURL');   
  }
  
  

  
  
}


/* ************************* ADDITIONAL FUNCTION **************************** */

/* Name  : TAIL LIFT
 * Desc  : set the tail lift value in admin
 * Found : 'admin->shipping module'
 * 
 * How to access the value : just call 'MODULE_SHIPPING_SMARTSEND_TAILLIFT'
 */

  # Set func TAIL LIFT
  function zen_cfg_pull_down_tail_classes($id, $key = '') {
    global $db;
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    
    $taillift[] = Array ("id" => "none","text" => "NO");
    $taillift[] = Array ("id" =>  "atpickup" ,"text" => "Yes - At Pickup");
    $taillift[] = Array ("id" =>  "atdestination","text" => "Yes - At Delivery");
    $taillift[] = Array ("id" =>  "both","text" => "Yes - At Pickup and Delivery");
    
    return zen_draw_pull_down_menu($name, $taillift , $id);  
    
  }    
  
  # Use func TAIL LIFT
  function zen_get_tail_class_title($id) {
    global $db;    
    
    $taillift[] = Array ("id" => "none","text" => "NO");
    $taillift[] = Array ("id" =>  "atpickup" ,"text" => "Yes - At Pickup");
    $taillift[] = Array ("id" =>  "atdestination","text" => "Yes - At Delivery");
    $taillift[] = Array ("id" =>  "both","text" => "Yes - At Pickup and Delivery");
    
    foreach($taillift as $val){
        if($val["id"] == $id){
          return $val['text'];      
        }
    }
    
  }
  
  
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ <new func> ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  
  
  
/* Name  : SERVICE TYPE
 * Desc  : set the service type value in admin
 * Found : 'admin->shipping module'
 * 
 * How to access the value : just call 'MODULE_SHIPPING_SMARTSEND_SERVICETYPE'
 */
  # Set func SERVICE TYPE
  function zen_cfg_pull_down_service_classes($id, $key = '') {
    global $db;
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    
    $service[] = Array ("id" =>  "ALL","text" => "ALL");
    $service[] = Array ("id" =>  "ROAD" ,"text" => "ROAD");
    $service[] = Array ("id" =>  "EXPRESS","text" => "EXPRESS");
    $service[] = Array ("id" =>  "ALLIEDEXPRESSROAD","text" => "ALLIEDEXPRESSROAD");
    $service[] = Array ("id" =>  "ALLIEDEXPRESSOVERNIGHT","text" => "ALLIEDEXPRESSOVERNIGHT");
    $service[] = Array ("id" =>  "MAINFREIGHTROAD" ,"text" => "MAINFREIGHTROAD");
    $service[] = Array ("id" =>  "TNTROAD","text" => "TNTROAD");
    $service[] = Array ("id" =>  "TNTOVERNIGHT","text" => "TNTOVERNIGHT");
    $service[] = Array ("id" =>  "TNTOVERNIGHTAM","text" => "TNTOVERNIGHTAM");
    $service[] = Array ("id" =>  "TNTNEXTFLIGHT" ,"text" => "TNTNEXTFLIGHT");
    $service[] = Array ("id" =>  "DHLROAD","text" => "DHLROAD");
    $service[] = Array ("id" =>  "DHLEXPRESS","text" => "DHLEXPRESS");
    $service[] = Array ("id" =>  "AAEEXPRESSECONOMY","text" => "AAEEXPRESSECONOMY");
    $service[] = Array ("id" =>  "AAEEXPRESSPREMIUM","text" => "AAEEXPRESSPREMIUM");
    

    
    return zen_draw_pull_down_menu($name, $service , $id);  
    
  }    
  
  # Use func SERVICE TYPE
  function zen_get_service_class_title($id) {
    global $db;    
    
    $service[] = Array ("id" => "ALL","text" => "ALL");
    $service[] = Array ("id" =>  "ROAD" ,"text" => "ROAD");
    $service[] = Array ("id" =>  "EXPRESS","text" => "EXPRESS");
    $service[] = Array ("id" =>  "ALLIEDEXPRESSROAD","text" => "ALLIEDEXPRESSROAD");
    $service[] = Array ("id" => "ALLIEDEXPRESSOVERNIGHT","text" => "ALLIEDEXPRESSOVERNIGHT");
    $service[] = Array ("id" =>  "MAINFREIGHTROAD" ,"text" => "MAINFREIGHTROAD");
    $service[] = Array ("id" =>  "TNTROAD","text" => "TNTROAD");
    $service[] = Array ("id" =>  "TNTOVERNIGHT","text" => "TNTOVERNIGHT");
    $service[] = Array ("id" => "TNTOVERNIGHTAM","text" => "TNTOVERNIGHTAM");
    $service[] = Array ("id" =>  "TNTNEXTFLIGHT" ,"text" => "TNTNEXTFLIGHT");
    $service[] = Array ("id" =>  "DHLROAD","text" => "DHLROAD");
    $service[] = Array ("id" =>  "DHLEXPRESS","text" => "DHLEXPRESS");
    $service[] = Array ("id" =>  "AAEEXPRESSECONOMY","text" => "AAEEXPRESSECONOMY");
    $service[] = Array ("id" =>  "AAEEXPRESSPREMIUM","text" => "AAEEXPRESSPREMIUM");
    
    
    foreach($service as $val){
        if($val["id"] == $id){
          return $val['text'];      
        }
    }
    
  }
  
  
?>