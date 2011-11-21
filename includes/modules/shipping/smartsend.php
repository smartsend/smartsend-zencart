
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
    global $order, $db , $messageStack;
    
        
    $this->code = 'smartsend';
    $this->title = MODULE_SHIPPING_SMARTSEND_TEXT_TITLE;
    $this->description = MODULE_SHIPPING_SMARTSEND_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_SHIPPING_STOREPICKUP_SORT_ORDER;
    $this->icon = '';
    
    
    $this->enabled = ((MODULE_SHIPPING_SMARTSEND_STATUS == 'True') ? true : false);
    
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
    $tosuburb       = $order->delivery['city'];
    $sweight        = $shipping_weight;

    
    # POST PARAMETER VALUES    
    $post_url = "http://api.dev.smartsend.com.au/";    
    
    $post_param_values["METHOD"]                = "GetQuote";
<<<<<<< HEAD
    $post_param_values["FROMCOUNTRYCODE"]       = "AU";
    $post_param_values["FROMPOSTCODE"]          = "3000";
    $post_param_values["FROMSUBURB"]            = "Melbourne";
=======
    $post_param_values["FROMCOUNTRYCODE"]       = MODULE_SHIPPING_SMARTSEND_COUNTRYCODE;
    $post_param_values["FROMPOSTCODE"]          = MODULE_SHIPPING_SMARTSEND_POSTCODE; //"2000";
    $post_param_values["FROMSUBURB"]            = MODULE_SHIPPING_SMARTSEND_SUBURB; //"SYDNEY";
>>>>>>> a36bab1dc29b7e5577075f1ec16e319be49cb393
    $post_param_values["TOCOUNTRYCODE"]         = $tocountrycode;
    $post_param_values["TOPOSTCODE"]            = $topostcode;
    $post_param_values["TOSUBURB"]              = $tosuburb;
    $post_param_values["RECEIPTEDDELIVERY"]     = MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY;
    $post_param_values["TRANSPORTASSURANCE"]    = $order->info["total"];

        
    # tail lift - init    
    $taillift = array();
    
    # POST ITEMS VALUE
    foreach($order->products as $key => $data){
        $i = intval($data['id']);
             
        $products = $db->Execute("SELECT depth,length,height,description,taillift FROM smartsend_products WHERE id={$i}");    
        
        $products = $products->fields;              
        
        $post_value_items["ITEM({$key})_HEIGHT"]         =  $products['height'];
        $post_value_items["ITEM({$key})_LENGTH"]         =  $products['length'];
        $post_value_items["ITEM({$key})_DEPTH"]          =  $products['depth'];
        $post_value_items["ITEM({$key})_WEIGHT"]         =  $data['weight'];
        $post_value_items["ITEM({$key})_DESCRIPTION"]    =  $products['description'];
       
                    # tail lift - assigns value
                    switch($products['taillift']){
                        case 'none':
                            $taillift[] = "none";break;
                        case 'atpickup':
                            $taillift[] = "atpickup";break;    
                        case 'atdestination':
                            $taillift[] = "atdestination";break;                                                         
                        case 'both':
                            $taillift[] = "both";break;                                                         
                    }
     }
              
    # tail lift - choose appropriate value
    $post_param_values["TAILLIFT"] = "none";            
    if (in_array("none",  $taillift))                                               $post_param_values["TAILLIFT"]      = "none";           
    if (in_array("atpickup",  $taillift))                                           $post_param_values["TAILLIFT"]      = "atpickup";
    if (in_array("atdestination",  $taillift))                                      $post_param_values["TAILLIFT"]      = "atdestination";
    if (in_array("atpickup",  $taillift) && in_array("atdestination",  $taillift))  $post_param_values["TAILLIFT"]      = "both";
    if (in_array("both",  $taillift))                                               $post_param_values["TAILLIFT"]      = "both";   
       
    $post_final_values = array_merge($post_param_values,$post_value_items);
    
    # POST PARAMETER AND ITEMS VALUE URLENCODE
    $post_string = "";
    foreach( $post_final_values as $key => $value )
            { $post_string .= "$key=" . urlencode( $value ) . "&"; }
    $post_string = rtrim( $post_string, "& " );

<<<<<<< HEAD
   echo $post_url."?".$post_string;
=======
    //echo $post_url."?".$post_string;
>>>>>>> a36bab1dc29b7e5577075f1ec16e319be49cb393
    
    
    # START CURL PROCESS
    $request = curl_init($post_url); 
    curl_setopt($request, CURLOPT_HEADER, 0); 
    curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
    $post_response = curl_exec($request); 
    curl_close ($request); // close curl object    
<<<<<<< HEAD
    var_dump($post_response);
    
    
    # test response
    //$str_resp = "ACK=Success&QUOTE(0)_TOTAL=26.47&QUOTE(0)_SERVICE=Road&QUOTE(0)_ESTIMATEDTRANSITTIME=1-2%20business%20days&QUOTE(0)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(0)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTE(1)_TOTAL=102.92&QUOTE(1)_SERVICE=Overnight&QUOTE(1)_ESTIMATEDTRANSITTIME=Next%20business%20day&QUOTE(1)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(1)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTE(2)_TOTAL=150.15&QUOTE(2)_SERVICE=Overnight%20by%209am&QUOTE(2)_ESTIMATEDTRANSITTIME=Next%20business%20day%20delivered%20by%209am&QUOTE(2)_ESTIMATEDTRANSITTIME_MINDAYS=1&QUOTE(2)_ESTIMATEDTRANSITTIME_MAXDAYS=1&QUOTECOUNT=3";
    
=======
          
>>>>>>> a36bab1dc29b7e5577075f1ec16e319be49cb393
    # parse output
    parse_str($post_response, $arr_resp);
    
    $quote_count = ((int) $arr_resp["QUOTECOUNT"]) - 1;
        
    # JAVASCRIPT MANIPULATION
    $script='<script src="includes/smartsend.js"></script>';
    
    # Initialise our arrays
    $this->quotes = array('id' => $this->code, 'module' => $this->title);
    $methods = array() ;
    
    # ASSIGNING VALUES TO ARRAY METHODS    
    for ($x=0; $x<=$quote_count; $x++)
    {
        $methods[] = array( 
                'id' => "quote{$x}",  
                'title' => "{$arr_resp["QUOTE({$x})_SERVICE"]}"." <label>{$arr_resp["QUOTE({$x})_ESTIMATEDTRANSITTIME"]}</label>".$script,
                'cost' => $arr_resp["QUOTE({$x})_TOTAL"] 
         ) ;              
    }
   
    $sarray[]   = array(); 
    $resultarr  = array();

    foreach($methods as $key => $value) {
            $sarray[ $key ] = $value['cost'] ;
    }

    asort( $sarray );

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

        $this->quotes = array(
                'id' => $this->code, 
                'module' => $this->title,
                'methods' => array( array('id' => $method,'title' => $temp['title'],'cost' => $temp['cost'] ))
        );
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
        values ('Enable Smart Send', 'MODULE_SHIPPING_SMARTSEND_STATUS', 'True', 
        'Do you want to offer Smart Send plugin?', 
        '66', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
   
    # USERCODE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('USER CODE', 'MODULE_SHIPPING_SMARTSEND_USERCODE', '', 
        '(Optional) The code corresponding to the USERTYPE value. ', 
        '66', '0', now())");

    # USERTYPE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('USER TYPE', 'MODULE_SHIPPING_SMARTSEND_USERTYPE', '',
        '(Optional) The user type making the quote request. Used in conjunction with USERCODE if appropriate. Valid values are , ebay, corporate, promotion.', 
        '66', '0', now())");

    /*
    # TRANSPORTASSURANCE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('TRANSPORT ASSURANCE', 'MODULE_SHIPPING_SMARTSEND_TRANSPORTASSURANCE', '0.00', 
        '(Optional) The wholesale value of the goods, specified in AUS $ for the purposes of transport assurance cover. Maximum value 10000.00', 
        '66', '0', now())");
    
    # TAILLIFT
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('TAIL LIFT', 'MODULE_SHIPPING_SMARTSEND_TAILLIFT', '0', 
        '(Optional) Specifies whether a tail lift service is required. Acceptable values are None, AtPickup, AtDestination, Both', 
        '66', '0', 'zen_get_tail_class_title', 'zen_cfg_pull_down_tail_classes(',  now())");      
     */            
    
    
    # RECEIPTEDDELIVERY
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('RECEIPTED DELIVERY', 'MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY', '0', 
        '(Optional) Yes / No  that specifies whether or not recipient is required to sign for the consignment', 
        '66', '0', 'zen_get_rdelivery_class_title', 'zen_cfg_pull_down_rdelivery_classes(',  now())");
    
    
    # SERVICE TYPE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('SERVICE TYPE', 'MODULE_SHIPPING_SMARTSEND_SERVICETYPE', '0', 
        '(Optional)', 
        '66', '0', 'zen_get_service_class_title', 'zen_cfg_pull_down_service_classes(',  now())");
    
    
    # COUNTRY CODE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('COUNTRY CODE', 'MODULE_SHIPPING_SMARTSEND_COUNTRYCODE', 'AU', 
        '(Optional) The 2 letter country code (ISO-3166) where the consignment will be picked up (Default AU).', 
        '66', '0', now())");

    # POSTCODE
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('POST CODE', 'MODULE_SHIPPING_SMARTSEND_POSTCODE', '', 
        '<span style=\'color:red\'>(Required)</span> The post code where the consignment will be picked up. ', 
        '66', '0', now())");

    # SUBURBAN
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('SUBURBAN', 'MODULE_SHIPPING_SMARTSEND_SUBURB', 'sydney', 
        '<span style=\'color:red\'>(Required)</span> The suburb/city where the consignment will be picked up. Must be valid when combined with FROMPOSTCODE otherwise an error will be returned. ', 
        '66', '0', now())");

    # CONTACT COMPANY
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('CONTACT COMPANY', 'MODULE_SHIPPING_SMARTSEND_CONTACTCOMPANY', '', 
        '(Optional) The contact company responsible for the booking. ', 
        '66', '0', now())");

    # CONTACT NAME    
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('CONTACT NAME', 'MODULE_SHIPPING_SMARTSEND_CONTACTNAME', '', 
        '(Optional) The name of the contact person responsible for the booking. ', 
        '66', '0', now())");
    
    # CONTACT PHONE 
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('CONTACT PHONE', 'MODULE_SHIPPING_SMARTSEND_CONTACTPHONE', '', 
        '<span style=\'color:red\'>(Required)</span> Contact phone of the person responsible for the booking (10 digits - area code included); critical for verification purposes. ', 
        '66', '0', now())");
    
    # CONTACT EMAIL    
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('CONTACT EMAIL', 'MODULE_SHIPPING_SMARTSEND_CONTACTEMAIL', '', 
        '<span style=\'color:red\'>(Required)</span> The email address of the person to be contacted regarding the booking if required; critical for verification purposes. ', 
        '66', '0', now())");
    
    # PICKUP COMPANY
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP COMPANY', 'MODULE_SHIPPING_SMARTSEND_PICKUPCOMPANY', '', 
        '(Optional) Name of the company at the pickup location. ', 
        '66', '0', now())");

    # PICKUP CONTACT     
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP CONTACT', 'MODULE_SHIPPING_SMARTSEND_PICKUPCONTACT', '', 
        '<span style=\'color:red\'>(Required)</span> Name of the contact person at the pickup location. ', 
        '66', '0', now())");

    
    # PICKUP ADDRESS1
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP ADDRESS1', 'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS1', '', 
        '<span style=\'color:red\'>(Required)</span>  Address line 1 of the pickup location. ', 
        '66', '0', now())");
     
    # PICKUP ADDRESS2     
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP ADDRESS2', 'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS2', '', 
        '(Optional) Address line 2 of the pickup location. ', 
        '66', '0', now())");
     
    # PICKUP PHONE          
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP PHONE', 'MODULE_SHIPPING_SMARTSEND_PICKUPPHONE', '', 
        '<span style=\'color:red\'>(Required)</span> Contact phone of the person at the pickup location (10 digits - area code included). ', 
        '66', '0', now())");
     
    
    # PICKUP SUBURB
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP SUBURB', 'MODULE_SHIPPING_SMARTSEND_PICKUPSUBURB', '', 
        '<span style=\'color:red\'>(Required)</span> Suburb of the pickup location. ', 
        '66', '0', now())");
    
    # PICKUP POSTCODE
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP POSTCODE', 'MODULE_SHIPPING_SMARTSEND_PICKUPPOSTCODE', '', 
        '<span style=\'color:red\'>(Required)</span> Post code of the pickup location. ', 
        '66', '0', now())");
     
    # PICKUP STATE     
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP STATE', 'MODULE_SHIPPING_SMARTSEND_PICKUPSTATE', '', 
        '<span style=\'color:red\'>(Required)</span> State of the pickup location (use abbreviation e.g. NSW) ', 
        '66', '0', now())");

/*
    # PICKUP DATE   
     $desc_date = mysql_real_escape_string('<span style=\'color:red\'>(Required)</span> Sets the pickup date. (format dd/mm/yyyy. e.g. 25/07/2010)');    
     $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
        values ('PICKUP DATE', 'MODULE_SHIPPING_SMARTSEND_PICKUPDATE', '', 
        '{$desc_date}', 
        '66', '0', now())");
     
    # PICKUP TIME   
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,use_function, set_function,  date_added) 
        values ('PICKUPTIME', 'MODULE_SHIPPING_SMARTSEND_PICKUPTIME', '0', 
        '<span style=\'color:red\'>(Required)</span> Sets the pickup time window. Valid values are 1 (between 12pm and 4pm) and 2 (between 1pm and 5pm). ', 
        '66', '0', 'zen_get_picktime_class_title', 'zen_cfg_pull_down_picktime_classes(',  now())");
    
*/
     
     
    $tables = $db->Execute("SHOW TABLES like 'smartsend_products'");    
    if ($tables->RecordCount() <= 0) {
        $db->Execute(" 
        CREATE TABLE IF NOT EXISTS `smartsend_products` (
          `description` varchar(20) NOT NULL,
          `id` int(11) NOT NULL,
          `depth` int(11) NOT NULL,
          `length` int(11) NOT NULL,
          `height` int(11) NOT NULL,
          `taillift` varchar(20) NOT NULL,          
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
      /*
    return array(
        'MODULE_SHIPPING_SMARTSEND_STATUS', 
        'MODULE_SHIPPING_SMARTSEND_USERCODE',
        'MODULE_SHIPPING_SMARTSEND_USERTYPE',
        'MODULE_SHIPPING_SMARTSEND_TRANSPORTASSURANCE',
        'MODULE_SHIPPING_SMARTSEND_TAILLIFT',
        'MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY',
        'MODULE_SHIPPING_SMARTSEND_COUNTRYCODE',
        'MODULE_SHIPPING_SMARTSEND_POSTCODE',
        'MODULE_SHIPPING_SMARTSEND_SUBURB',
        'MODULE_SHIPPING_SMARTSEND_CONTACTCOMPANY',
        'MODULE_SHIPPING_SMARTSEND_CONTACTNAME',
        'MODULE_SHIPPING_SMARTSEND_CONTACTPHONE',
        'MODULE_SHIPPING_SMARTSEND_CONTACTEMAIL',
        'MODULE_SHIPPING_SMARTSEND_PICKUPCONTACT',
        'MODULE_SHIPPING_SMARTSEND_PICKUPCOMPANY',
        'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS1',
        'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS2',                    
        'MODULE_SHIPPING_SMARTSEND_PICKUPPHONE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPSUBURB',
        'MODULE_SHIPPING_SMARTSEND_PICKUPPOSTCODE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPSTATE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPDATE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPTIME');  
        */
      
    return array(
        'MODULE_SHIPPING_SMARTSEND_STATUS', 
        'MODULE_SHIPPING_SMARTSEND_USERCODE',
        'MODULE_SHIPPING_SMARTSEND_USERTYPE',
        'MODULE_SHIPPING_SMARTSEND_RECEIPTEDDELIVERY',
        'MODULE_SHIPPING_SMARTSEND_COUNTRYCODE',
        'MODULE_SHIPPING_SMARTSEND_POSTCODE',
        'MODULE_SHIPPING_SMARTSEND_SUBURB',
        'MODULE_SHIPPING_SMARTSEND_CONTACTCOMPANY',
        'MODULE_SHIPPING_SMARTSEND_CONTACTNAME',
        'MODULE_SHIPPING_SMARTSEND_CONTACTPHONE',
        'MODULE_SHIPPING_SMARTSEND_CONTACTEMAIL',
        'MODULE_SHIPPING_SMARTSEND_PICKUPCONTACT',
        'MODULE_SHIPPING_SMARTSEND_PICKUPCOMPANY',
        'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS1',
        'MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS2',                    
        'MODULE_SHIPPING_SMARTSEND_PICKUPPHONE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPSUBURB',
        'MODULE_SHIPPING_SMARTSEND_PICKUPPOSTCODE',
        'MODULE_SHIPPING_SMARTSEND_PICKUPSTATE');            
  }
  
  

  
  
}


/* ************************* ADDITIONAL FUNCTION **************************** */
/* Name  : TAIL LIFT
 * Desc  : set the tail lift value in admin
 * Found : 'admin->shipping module'
 * 
 * How to access the value : just call 'MODULE_SHIPPING_SMARTSEND_TAILLIFT'
 */

  # Set array taillift
  function zen_arr_taillift(){
    $taillift[] = Array ("id" => "none","text" => "NO");
    $taillift[] = Array ("id" =>  "atpickup" ,"text" => "Yes - At Pickup");
    $taillift[] = Array ("id" =>  "atdestination","text" => "Yes - At Delivery");
    $taillift[] = Array ("id" =>  "both","text" => "Yes - At Pickup and Delivery");      
    return $taillift;
  }  
  
  # Set func TAIL LIFT
  function zen_cfg_pull_down_tail_classes($id, $key = '') {
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    return zen_draw_pull_down_menu($name, zen_arr_taillift() , $id);  
  }    
  
  # Use func TAIL LIFT
  function zen_get_tail_class_title($id) {   
    $taillift = zen_arr_taillift();    
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
  
  # Set array service type
  function zen_arr_servicetype(){
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
    
    return $service;
  }

  # Set func SERVICE TYPE
  function zen_cfg_pull_down_service_classes($id, $key = '') {
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    return zen_draw_pull_down_menu($name, zen_arr_servicetype() , $id);      
  }    
  
  # Use func SERVICE TYPE
  function zen_get_service_class_title($id) {   
    $service = zen_arr_servicetype();        
    foreach($service as $val){
        if($val["id"] == $id){
          return $val['text'];      
        }
    }
    
  }
  
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ <new func> ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
/* Name  : PICKUP TIME
 * Desc  : set the PICKUP TIME value in admin
 * Found : 'admin->shipping module'
 * 
 * How to access the value : just call 'MODULE_SHIPPING_SMARTSEND_TAILLIFT'
 */
  # Set array pickup time
  function zen_arr_pickuptime(){
    $ptime[] = Array ("id" =>  "1" ,"text" => "between 12pm and 4pm");
    $ptime[] = Array ("id" =>  "2","text" => "between 1pm and 5pm");
    return $ptime;
  }
  
  # Set func PICKUP TIME
  function zen_cfg_pull_down_picktime_classes($id, $key = '') {
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    return zen_draw_pull_down_menu($name, zen_arr_pickuptime() , $id);      
  }    
  
  # Use func PICKUP TIME
  function zen_get_picktime_class_title($id) {
    $ptime = zen_arr_pickuptime();
    foreach($ptime as $val){
        if($val["id"] == $id){
          return $val['text'];      
        }
    }
    
  }
  
  
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ <new func> ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
/* Name  : RECEIPTED DELIVERY
 * Desc  : set the PICKUP TIME value in admin
 * Found : 'admin->shipping module'
 * 
 * How to access the value : just call 'MODULE_SHIPPING_SMARTSEND_TAILLIFT'
 */

  # Set array RECEIPTED DELIVERY
  function zen_arr_rdelivery(){
    $ptime[] = Array ("id" =>  "true" ,"text" => "YES");
    $ptime[] = Array ("id" =>  "false","text" => "NO");
    return $ptime;
  }
  
  # Set func RECEIPTED DELIVERY
  function zen_cfg_pull_down_rdelivery_classes($id, $key = '') {
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    return zen_draw_pull_down_menu($name, zen_arr_rdelivery() , $id);      
  }    
  
  # Use func RECEIPTED DELIVERY
  function zen_get_rdelivery_class_title($id) {
    $ptime = zen_arr_rdelivery();
    foreach($ptime as $val){
        if($val["id"] == $id){
          return $val['text'];      
        }
    }
    
  }  
  
?>