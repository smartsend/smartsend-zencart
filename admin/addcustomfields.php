<?php
	require('includes/application_top.php');
	require('includes/configure.php');
	if($_SESSION['securityToken']){
	function array_pluck($key, $array)
	{
		 if (is_array($key) || !is_array($array)) return array();
		 $funct = create_function('$e', 'return is_array($e) && array_key_exists("'.$key.'",$e) ? $e["'. $key .'"] : null;');
		 return array_map($funct, $array);
	}
	
	function getConfig($cfg){
		global $db;
		$config = $db->Execute("SELECT configuration_value FROM configuration WHERE configuration_key='{$cfg}'");
		$config = $config->fields;
		return $config["configuration_value"];
	}
		
		if($_POST["action"]=="getorder"){
			$stateMap = array(array("australian capital territory"=>"ACT", "new south wales"=>"NSW", "northern territory"=>"NT", "queensland"=>"QLD", "south australia"=>"SA", "tasmania"=>"TAS", "victoria"=>"VIC", "western australia"=>"WA"));
			
			$items = explode("|", $_POST["items"]);
			
			// init calls
			$post_param_values["METHOD"] = "SETBOOKING";
			$post_param_values["USERTYPE"] = getConfig("MODULE_SHIPPING_SMARTSEND_USERTYPE");
			$post_param_values["USERCODE"] = getConfig("MODULE_SHIPPING_SMARTSEND_USERCODE");
			$post_param_values["USERCODE"] = getConfig("MODULE_SHIPPING_SMARTSEND_USERCODE");
			
			$post_param_values["RETURNURL"] = $_POST["url"]."?returnurl=1";
			$post_param_values["CANCELURL"] = $_POST["url"]."?cancelurl=1";
			$post_param_values["NOTIFYURL"] = $_POST["url"]."?notifyurl=1";
			
			$post_url = "http://api.dev.smartsend.com.au/";
			$bookingCount=0;
			foreach($items as $item){
				$itemCount=0;
				$tl_none=0;
				$tl_atpickup=0;
				$tl_atdestination=0;
				$tl_both=0;
				
				$customerInfos = $db->Execute("SELECT customers_company, customers_city, customers_name, customers_street_address, customers_telephone, customers_city, customers_postcode, customers_state, order_total FROM orders WHERE orders_id={$item}");
				$customerInfos = $customerInfos->fields;
				$state=array_pluck(strtolower($customerInfos["customers_state"]), $stateMap);
				$post_param_values["BOOKING({$bookingCount})_CONTACTCOMPANY"] = getConfig("MODULE_SHIPPING_SMARTSEND_CONTACTCOMPANY");
				$post_param_values["BOOKING({$bookingCount})_CONTACTNAME"] = getConfig("MODULE_SHIPPING_SMARTSEND_CONTACTNAME");
				$post_param_values["BOOKING({$bookingCount})_CONTACTPHONE"] = getConfig("MODULE_SHIPPING_SMARTSEND_CONTACTPHONE");
				$post_param_values["BOOKING({$bookingCount})_CONTACTEMAIL"] = getConfig("MODULE_SHIPPING_SMARTSEND_CONTACTEMAIL");
				$post_param_values["BOOKING({$bookingCount})_CONTACTEMAIL"] = getConfig("MODULE_SHIPPING_SMARTSEND_CONTACTEMAIL");
				
				$post_param_values["BOOKING({$bookingCount})_PICKUPCOMPANY"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPCOMPANY");
				$post_param_values["BOOKING({$bookingCount})_PICKUPCONTACT"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPCONTACT");
				$post_param_values["BOOKING({$bookingCount})_PICKUPADDRESS1"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS1");
				$post_param_values["BOOKING({$bookingCount})_PICKUPADDRESS2"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPADDRESS2");
				$post_param_values["BOOKING({$bookingCount})_PICKUPPHONE"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPPHONE");
				$post_param_values["BOOKING({$bookingCount})_PICKUPSUBURB"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPSUBURB");
				$post_param_values["BOOKING({$bookingCount})_PICKUPPOSTCODE"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPPOSTCODE");
				$post_param_values["BOOKING({$bookingCount})_PICKUPSTATE"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPSTATE");
				//$post_param_values["BOOKING({$bookingCount})_PICKUPDATE"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPDATE");
				//$post_param_values["BOOKING({$bookingCount})_PICKUPTIME"] = getConfig("MODULE_SHIPPING_SMARTSEND_PICKUPTIME");
				
				$post_param_values["BOOKING({$bookingCount})_DESTCOMPANY"] = $customerInfos["customers_company"];
				$post_param_values["BOOKING({$bookingCount})_DESTCONTACT"] = $customerInfos["customers_name"];
				$post_param_values["BOOKING({$bookingCount})_DESTADDRESS1"] = $customerInfos["customers_street_address"];
				$post_param_values["BOOKING({$bookingCount})_DESTPHONE"] = $customerInfos["customers_telephone"];
				$post_param_values["BOOKING({$bookingCount})_DESTSUBURB"] = $customerInfos["customers_city"];
				$post_param_values["BOOKING({$bookingCount})_DESTPOSTCODE"] = $customerInfos["customers_postcode"];
				$post_param_values["BOOKING({$bookingCount})_DESTSTATE"] = $state[0];
				
				$post_param_values["BOOKING({$bookingCount})_TRANSPORTASSURANCE"] = $customerInfos["order_total"];
				
				//item init
				$result = $db->ExecuteRandomMulti("SELECT products_quantity, products_id FROM orders_products WHERE orders_id={$item}");
				while($row = mysql_fetch_array($result->resource)){
					
					$result2 = $db->ExecuteRandomMulti("SELECT description, depth, height, length, taillift FROM smartsend_products WHERE id={$row['products_id']}");
					//item loop
					
					while($row2 = mysql_fetch_array($result2->resource)){
						for($j=0;$j<$row["products_quantity"];$j++){
							$post_value_items["BOOKING({$bookingCount})_ITEM($itemCount)_DESCRIPTION"] = $row2["description"];
							$post_value_items["BOOKING({$bookingCount})_ITEM($itemCount)_DEPTH"] = $row2["depth"];
							$post_value_items["BOOKING({$bookingCount})_ITEM($itemCount)_HEIGHT"] = $row2["height"];
							$post_value_items["BOOKING({$bookingCount})_ITEM($itemCount)_LENGTH"] = $row2["length"];
							
							if($row2["taillift"]=="none")
								$tl_none=1;
								
							if($row2["taillift"]=="atpickup")
								$tl_atpickup=1;
							
							if($row2["taillift"]=="atdestination")
								$tl_atdestination=1;
								
							if($row2["taillift"]=="both")
								$tl_both=1;
							
							$itemCount++;
						}
					}
					
				if($tl_none==1)
					$dTaillift="none";
				if($tl_atpickup==1 && $tl_atdestination==0)
					$dTaillift="atpickup";
				if($tl_atpickup==0 && $tl_atdestination==1)
					$dTaillift="atdestination";
				if($tl_atpickup==1 && $tl_atdestination==0)
					$dTaillift="atpickup";
				if($tl_atpickup==1 && $tl_atdestination==1)
					$dTaillift="both";
				if($tl_both==1)
					$dTaillift="both";
				
				$post_param_values["BOOKING({$bookingCount})_TAILLIFT"] = $dTaillift;
				}
				
				$bookingCount++;
			}
			
			foreach( $post_value_items as $key => $value ){
				echo "$key - $value\n";
			}
			
			foreach( $post_param_values as $key => $value ){
				echo "$key - $value\n";
			}
					
			# POST PARAMETER VALUES
				
						
			
			# POST ITEMS VALUE
			/*foreach($order->products as $key => $data){
				$i = intval($data['id']);
					 
				$products = $db->Execute("SELECT depth,length,height,description FROM smartsend_products WHERE id={$i}");    
				$products = $products->fields;
						
				$post_value_items["ITEM({$key})_HEIGHT"]         =  $products['height'];
				$post_value_items["ITEM({$key})_LENGTH"]         =  $products['length'];
				$post_value_items["ITEM({$key})_DEPTH"]          =  $products['depth'];
				$post_value_items["ITEM({$key})_WEIGHT"]         =  $data['weight'];
				$post_value_items["ITEM({$key})_DESCRIPTION"]    =  $products['description'];
			   
			}
		
			
			$post_final_values = array_merge($post_param_values,$post_value_items);*/
			
			# POST PARAMETER AND ITEMS VALUE URLENCODE
			/*$post_string = "";
			foreach( $post_final_values as $key => $value )
					{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
			$post_string = rtrim( $post_string, "& " );*/
		
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
			
			/*$products = $db->Execute("select configuration_key from configuration where configuration_id = '61'");
			$products = $products->fields;*/
			//echo $products['configuration_key'];
		}
		
		if($_POST["action"]=="add"){
		$sql = "SHOW TABLE STATUS LIKE '".DB_PREFIX."products'";
		$result = mysql_query($sql);

		$row = mysql_fetch_array($result);
		$next_id = $row['Auto_increment']-1;
		
		$depth=$_POST["depth"];
		$length=$_POST["length"];
		$height=$_POST["height"];
		$desc=$_POST["description"];
		$taillift=$_POST["taillift"];
		mysql_query("INSERT INTO ".DB_PREFIX."smartsend_products (description, id, depth, length, height, taillift) VALUES('$desc', '$next_id', '$depth', '$length', '$height','$taillift') ") or die(mysql_error());
		}

		if($_POST["action"]=="edit"){
			$depth=$_POST["depth"];
			$length=$_POST["length"];
			$height=$_POST["height"];
			$desc=$_POST["description"];
			$taillift=$_POST["taillift"];
			$pID=$_POST["pID"];
			$update = mysql_query("UPDATE ".DB_PREFIX."smartsend_products SET depth = '$depth', length = '$length', height='$height', description='$desc', taillift='$taillift' WHERE id='$pID'") 
			or die(mysql_error()); 
			echo mysql_affected_rows();
			if(mysql_affected_rows()==0){
				mysql_query("INSERT INTO ".DB_PREFIX."smartsend_products (description, id, depth, length, height, taillift) VALUES('$desc', '$pID', '$depth', '$length', '$height', '$taillift') ") or die(mysql_error());
			}
		}
		
		if($_GET["action"]=="attr"){
			header("Content-type: text/javascript");
			$pID=$_GET["pID"];
			$result = mysql_query("SELECT * FROM ".DB_PREFIX."smartsend_products WHERE id='$pID'") 
			or die(mysql_error());
			
			$row = mysql_fetch_array( $result );
			$height = $row["height"];
			$length = $row["length"];
			$depth = $row["depth"];
			$desc = $row["description"];
			$taillift = $row["taillift"];
			
			echo '$("input[name=\'products_height\']").val("'.$height.'");';
			echo '$("input[name=\'products_length\']").val("'.$length.'");';
			echo '$("input[name=\'products_depth\']").val("'.$depth.'");';
			echo '
			desc="'.$desc.'";
			var ItemTypeMap = {
					"envelope" : 0,
					"carton" : 2, 
					"satchel" : 3,
					"bag" : 3,
					"tube" : 4,
					"skid" : 5, 
					"pallet" : 6, 
					"crate" : 7, 
					"flatpack" : 8, 
					"roll" : 9, 
					"length" : 10, 
					"tyre" : 12,
					"wheel" : 12, 
					"furniture" : 13, 
					"bedding" : 13
				}[desc];
				$("select[name=\'description\'] option[value=\'"+ItemTypeMap+"\']").attr("selected", true);
			var tl="'.$taillift.'";
			var TailLiftTypeID = { 
			"none" : 0, 
			"atpickup" : 1, 
			"atdestination" : 2, 
			"both" : 3}[tl.toLowerCase()];
				$("select[name=\'TailLift\'] option[value=\'"+TailLiftTypeID+"\']").attr("selected", true)
			';
			
		}
		
		if($_GET["action"]=="alertscr"){
			header("Content-type: text/javascript");
			$i=0;
			$result = mysql_query("SELECT ".DB_PREFIX."products_description.products_id AS did, ".DB_PREFIX."products_description.products_name AS name, ".DB_PREFIX."products.products_type AS type FROM ".DB_PREFIX."products_description, ".DB_PREFIX."products WHERE ".DB_PREFIX."products_description.products_id=".DB_PREFIX."products.products_id AND ".DB_PREFIX."products_description.products_id NOT IN (SELECT ".DB_PREFIX."smartsend_products.id FROM ".DB_PREFIX."smartsend_products)");
			while($row = mysql_fetch_array($result)){
				$id=$row["did"];
				$name=addslashes($row["name"]);
				$type=$row["type"];
				echo "msgTitle='Please update the depth, length, height and best packing method for the following products';";
				echo "sItems[$i]=[$id,'$name','$type'];";
				
				$i++;
			}
			
			if($i==0){
				$result = mysql_query("SELECT ".DB_PREFIX."products_description.products_id AS id, ".DB_PREFIX."products_description.products_name AS name, ".DB_PREFIX."products.products_type AS type FROM ".DB_PREFIX."products_description, ".DB_PREFIX."products WHERE ".DB_PREFIX."products_description.products_id=".DB_PREFIX."products.products_id AND ".DB_PREFIX."products_description.products_id IN (SELECT ".DB_PREFIX."products.products_id FROM ".DB_PREFIX."products WHERE ".DB_PREFIX."products.products_weight='0.00')");
				while($row = mysql_fetch_array($result)){
					$id=$row["id"];
					$name=addslashes($row["name"]);
					$type=$row["type"];
					echo "msgTitle='Please update the weight for following products';";
					echo "sItems[$i]=[$id,'$name','$type'];";
					$i++;
				}
			}
			
		}
	}
?>