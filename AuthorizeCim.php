<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
	By: 
  Spicer Matthews <spicer@cloudmanic.com>
  Cloudmanic Labs, LLC
  http://www.cloudmanic.com
  
	Todo:
		- Better library function argument validation.
		- Better Documention
		- Better function defaults
		- Finish providing full support for the API defined in CIM_XML_guide.pdf
				Missing -> (createCustomerShippingRequest, deleteCustomerShippingRequest, updateCustomerShippingRequest,
										deleteCustomerPaymentProfileRequest, validateCustomerPaymentProfileRequest, 
										getCustomerPaymentProfileRequest, getCustomerShippingAddressRequest, validateCustomerPaymentProfileRequest)
		- Some API calls are missing some optional data sets.
		- Complete all the different Transaction calls. Only have done; Auth, Auth & Capture, Refund
**/

class AuthorizeCim
{
	//
	// Constructior needs to get values from config.
	//
	function AuthorizeCim()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('authorizenet');
		$this->loginname = $this->CI->config->item('authorizenetname');
		$this->loginkey = $this->CI->config->item('authorizenetkey');
		$this->loginhost = $this->CI->config->item('authorizenethost');
		$this->loginpath = $this->CI->config->item('authorizenetcimpath');
		$this->response = "";
		$this->errormsgs = array();
		$this->parsedresponse = array();
	}

	//
	// This function will create a profile based on the data passed in.
	// A profile needs at least one of the three vars.
	//
	function submit_profile($customerid = "", $description = "", $email = "")
	{
		if(empty($customerid) && empty($description) && empty($email))
			return 0;
			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<createCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					$this->_create_profile($customerid, 0, $description, $email);
		$content .= "</createCustomerProfileRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) {
			return $this->parsedresponse->customerProfileId;
		} else 
			return 0;
	}
	
	//
	// This function will update a complete profile based on the data passed in.
	// A profile needs at least one of the three vars.
	//
	function update_profile($profileid, $customerid = "", $description = "", $email = "")
	{
		if(empty($customerid) && empty($description) && empty($email))
			return 0;
			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<updateCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					$this->_create_profile($customerid, $profileid, $description, $email);
		$content .= "</updateCustomerProfileRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) {
			return 1;
		} else 
			return 0;
	}
	
	//
	// This function will update a payment profile based on the data passed in.
	//
	// Returns new customer Payment Profile Id
	//
	function update_paymentprofile($customerid, $paymentid, $cardnum, $expire, $cardcode = "", $mode = "none")
	{			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<updateCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					"<customerProfileId>$customerid</customerProfileId>" . 
					$this->_create_payment_profile($cardnum, $expire, $cardcode, $mode, $paymentid);
		$content .= "</updateCustomerPaymentProfileRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) 
			return 1;
		else 
			return 0;
	}

	
	//
	// This function will create a payment profile based on the data passed in.
	//
	// Returns new customer Payment Profile Id
	//
	function submit_paymentprofile($customerid, $cardnum, $expire, $cardcode = "", $mode = "none")
	{			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<createCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					"<customerProfileId>$customerid</customerProfileId>" .
					$this->_create_payment_profile($cardnum, $expire, $cardcode, $mode);
		$content .= "</createCustomerPaymentProfileRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) 
			return $this->parsedresponse->customerPaymentProfileId;
		else 
			return 0;
	}
	
	//
	// This function will submit a transaction, for Auth only.
	//
	// Returns new customer Payment Profile Id
	//
	function submit_transaction_authonly($customerid, $paymentid, $amount, $ccode = "")
	{			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					$this->_create_transaction_authonly($customerid, $paymentid, $amount, $ccode);
		$content .= "</createCustomerProfileTransactionRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) {  
			$tmp = explode(",", $this->parsedresponse->directResponse);
			return $tmp[6];
		} else 
			return 0;
	}

	//
	// This function will submit a transaction, for Auth & Capture.
	//
	// Returns new customer Payment Profile Id
	//
	function submit_transaction_authcap($customerid, $paymentid, $amount, $ccode = "")
	{			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					$this->_create_transaction_authcap($customerid, $paymentid, $amount, $ccode);
		$content .= "</createCustomerProfileTransactionRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) { 
			$tmp = explode(",", $this->parsedresponse->directResponse);
			return $tmp[6];
		} else 
			return 0;
	}
	
	function submit_transaction_refund($customerid, $paymentid, $amount, $tranid, $ccode = "")
	{			
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					$this->_create_transaction_refund($customerid, $paymentid, $amount, $tranid, $ccode);
		$content .= "</createCustomerProfileTransactionRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) { 
			$tmp = explode(",", $this->parsedresponse->directResponse);
			return $tmp[6];
		} else 
			return 0;
	}

	
	//
	// Query authorize.net and get a list of id's for all the already stored customer ids.
	//
	function get_customers()
	{
		$data = array();
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<getCustomerProfileIdsRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
			"</getCustomerProfileIdsRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content)) {
			foreach($this->parsedresponse->ids->numericString AS $key => $val)
				$data[] = $val[0];
		}
		return $data;	
	}

	//
	// Query authorize.net and get all the non-sensitive data on a customer.
	//
	function get_customer($id)
	{
		$data = array();
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<getCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					"<customerProfileId>$id</customerProfileId>" .
			"</getCustomerProfileRequest>";
			
		// Sent request to be processed.
		if($this->_send_request($content))
			$data = $this->parsedresponse;

		return $data;	
	}
	
	//
	// Query authorize.net and delete all the data on a customer.
	//
	function delete_customer($id)
	{
		$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<deleteCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					"<customerProfileId>$id</customerProfileId>" .
			"</deleteCustomerProfileRequest>";
			
		// Sent request to be processed.
		return $this->_send_request($content);
	}
	
	
	// 
	/*** ---------------------- Private / Helper Functions -------------- ***/
	//
	
	
	//
	// Use curl to sent the request to the authorize.net server.
	//
	function _send_request($content)
	{
		$posturl = "https://" . $this->loginhost . $this->loginpath;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $posturl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$this->response = curl_exec($ch);
		return $this->_parse_response();
	}

	//
	// Function to parse the api response
	// The code uses SimpleXML. http://us.php.net/manual/en/book.simplexml.php 
	// There are also other ways to parse xml in PHP depending on the version and what is installed.
	//
	function _parse_response()
	{
		$this->parsedresponse = simplexml_load_string($this->response, "SimpleXMLElement", LIBXML_NOWARNING);
		if ("Ok" != $this->parsedresponse->messages->resultCode) {
			foreach ($this->parsedresponse->messages->message as $msg) 
				$this->errormsgs[] = array('code' => htmlspecialchars($msg->code), 'msg' => htmlspecialchars($msg->text));
			return 0;
		}
		return 1;
	}

	//
	// Creates a payment profile for the request. *** (should come back and add more feilds) ***
	//
	function _create_payment_profile($cnumber, $cexpire, $ccode, $mode, $paymentid = "")
	{
		$content =
			"<paymentProfile>".
				"<payment>".
	 				"<creditCard>".
	  				"<cardNumber>$cnumber</cardNumber>".
	  				"<expirationDate>$cexpire</expirationDate>"; // required format for API is YYYY-MM
	 					if(! empty($ccode))
	 						$content .= "<cardCode>$ccode</cardCode>";
	 				$content .= "</creditCard>".
				"</payment>";
				if(! empty($paymentid))
	 				$content .= "<customerPaymentProfileId>$paymentid</customerPaymentProfileId>";
			$content .= "</paymentProfile>".
			"<validationMode>$mode</validationMode>"; // or testMode
			return $content;
	}

	//
	// Create the xml for a profile transaction for Auth Only
	//
	function _create_transaction_authonly($custid, $paymentid, $amount, $cardcode)
	{
		$content =
			"<transaction>".
				"<profileTransAuthOnly>".
					"<amount>" . $amount . "</amount>" . // should include tax, shipping, and everything.
					"<customerProfileId>" . $custid . "</customerProfileId>".
					"<customerPaymentProfileId>" . $paymentid. "</customerPaymentProfileId>";
					if(! empty($ccode))
	 						$content .= "<cardCode>$ccode</cardCode>";
				$content .= "</profileTransAuthOnly>".
			"</transaction>";
		return $content;
	}
	
	//
	// Create the xml for a profile transaction for a refund
	//
	function _create_transaction_refund($custid, $paymentid, $amount, $tranid, $cardcode)
	{
		$content =
			"<transaction>".
				"<profileTransRefund>".
					"<amount>" . $amount . "</amount>" . // should include tax, shipping, and everything.
					"<customerProfileId>" . $custid . "</customerProfileId>".
					"<customerPaymentProfileId>" . $paymentid. "</customerPaymentProfileId>";
					if(! empty($ccode))
	 						$content .= "<cardCode>$ccode</cardCode>";
	 		$content .= "<transId>$tranid</transId>" .
										"</profileTransRefund>".
			"</transaction>";
		return $content;
	}

	//
	// Create the xml for a profile transaction for Auth Capture.
	//
	function _create_transaction_authcap($custid, $paymentid, $amount, $cardcode)
	{
		$content =
			"<transaction>".
				"<profileTransAuthCapture>".
					"<amount>" . $amount . "</amount>" . // should include tax, shipping, and everything.
					"<customerProfileId>" . $custid . "</customerProfileId>".
					"<customerPaymentProfileId>" . $paymentid. "</customerPaymentProfileId>";
					if(! empty($ccode))
	 						$content .= "<cardCode>$ccode</cardCode>";
				$content .= "</profileTransAuthCapture>".
			"</transaction>";
		return $content;
	}


	//
	// Create the xml for for the create profile request. We are forcing a customer id. 
	// This function could be modified to force anyone of the three. Authorize.net just needs one.
	//
	function _create_profile($customerid, $update = 0, $description = "", $email = "")
	{
		$content = 
			"<profile>" . 
				"<merchantCustomerId>$customerid</merchantCustomerId>". // Your own identifier for the customer.
				"<description>$description</description>".
				"<email>$email</email>";
				if($update)
					$content .= "<customerProfileId>$update</customerProfileId>";
		$content .= "</profile>";
		return $content;
	}


	//
	// Creates the XML needed for the merchant Auth.
	//
	function _merchant_authentication_block() 
	{
		return
        "<merchantAuthentication>".
        "<name>" . $this->loginname . "</name>".
        "<transactionKey>" . $this->loginkey . "</transactionKey>".
        "</merchantAuthentication>";
	}	
}
?>