<?php

	//作用：随机数
	function createNoncestr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
		  $str.= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

	// 作用：array转xml
	function arrayToXml($arr)
	{
		$xml = "<xml>";
		foreach ($arr as $key=>$val)
		{
		   if (is_numeric($val)){
		      $xml.="<".$key.">".$val."</".$key.">"; 
		   }else
		      $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
		}
		$xml.="</xml>";
		return $xml; 
	}


	//作用：将XML转为array
	function xmlToArray($xml)
	{        

		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
		return $array_data;
	}

	//作用：将JSON转为array
	function jsonToArray($arr){
		return json_decode($arr, true);
	}



?>