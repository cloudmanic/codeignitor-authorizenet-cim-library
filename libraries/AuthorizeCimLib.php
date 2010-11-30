<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
By: Spicer Matthews <spicer@cloudmanic.com>
Company: Cloudmanic Labs, LLC
Website: http://www.cloudmanic.com

Based On Work From: 
- John Conde <johnny@johnconde.net>
- http://www.communitymx.com/content/article.cfm?page=4&cid=FDB14
*/

class AuthorizeCimLib
{
	private $_CI;
	private	$_loginname;
	private $_loginkey;
	private $_response;
	private $_resultCode;
	private $_responseCode;
	private $_responseText;
	private $_success;
	private $_error;
	private $_url;
	private $_parsedresponse;
	private $_xml;
	private $_call;
	private $_responsecall;
	private $_directresponse;
	private $_items = array();
	private $_params = array();
	private $_validationmode = 'liveMode';
	private $_errormsg = '';
	private $_loginhost = 'api.authorize.net';
	private $_testhost = 'apitest.authorize.net';
	private $_loginpath = '/xml/v1/request.api';
	
	//
	// Construct..... 
	//
	function __construct()
	{
		$this->_CI =& get_instance();
		$this->_set_url();
		$this->_set_default_params();
		
		// If the config is setup properly use that to initialize
		$this->_CI->config->load('authorizenet');
		
		if($this->_CI->config->item('authorizenetname') && 
				$this->_CI->config->item('authorizenetkey') &&
				$this->_CI->config->item('authorizenettestmode'))
		{
			$this->initialize($this->_CI->config->item('authorizenetname'), 
												$this->_CI->config->item('authorizenetkey'), 
												$this->_CI->config->item('authorizenettestmode'));
		}

		log_message('debug', "AuthorizeCimLib Class Initialized");
	}
	
	//
	// Call this function to setup the library variables. Such as API keys.
	//
	public function initialize($name, $key, $testmode = FALSE)
	{
		// Are we in test mode??
		if($testmode)
		{
			$this->_set_testmode();
		}

		// Setup login names and keys.
		$this->_loginname = $name;
		$this->_loginkey = $key;
	}
	
	//
	// Set validation mode.
	//
	public function set_validationmode($mode)
	{
		$types = array('none', 'testMode', 'liveMode', 'oldLiveMode');
		
		if(in_array($mode, $types))
		{
			$this->_validationmode = $mode;
			return 1;
		} 
		else
		{
			log_message('debug', "AuthorizeCimLib Not A Valid Test Mode");
			return 0;
		}
	}

	//
	// Get validation mode.
	//
	public function get_validationmode()
	{
		return $this->_validationmode;
	}
	
	//
	// Set Parameters to send to Authorize.net
	//
	public function set_data($field, $value)
	{
		$this->_params[$field] = $value;
	}

	//
	// C;ear Parameters data
	//
	public function clear_data()
	{
		$this->_params = array();
		$this->_set_default_params();
	}
	
	//
	// Create a customer profile.
	//
	public function create_customer_profile()
	{
		$this->_call = 'createCustomerProfileRequest';
		$this->_build_customer_profile_xml();
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse[$this->_responsecall]['customerProfileId'];
		}
		
		return 0;
	}
	
	//
	// Update a customer profile.
	//
	public function update_customer_profile($id)
	{
		$this->_call = 'updateCustomerProfileRequest';
		$this->_build_customer_profile_xml($id);
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return 1;
		}
		
		return 0;
	}
	
	//
	// Create a customer payment profile.
	//
	public function create_customer_payment_profile()
	{
		$this->_call = 'createCustomerPaymentProfileRequest';
		$this->_build_payment_profile_xml();
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			if(isset($this->_parsedresponse[$this->_responsecall]['validationDirectResponse']))
			{
				$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['validationDirectResponse'];
			}
			
			return $this->_parsedresponse[$this->_responsecall]['customerPaymentProfileId'];
		}
		
		// Error with card.
		if(isset($this->_parsedresponse[$this->_responsecall]['validationDirectResponse']))
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['validationDirectResponse'];
			return -1;	
		}
		
		return 0;
	}

	//
	// Update a customer payment profile.
	//
	public function update_customer_payment_profile($custid, $payid)
	{
		$this->_call = 'updateCustomerPaymentProfileRequest';
		$this->_build_payment_profile_xml($custid, $payid);
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['validationDirectResponse'];
			return $this->get_direct_response();
		}
		
		// Error with card.
		if(isset($this->_parsedresponse[$this->_responsecall]['validationDirectResponse']))
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['validationDirectResponse'];
			return $this->get_direct_response();	
		}
		
		return 0;
	}

	//
	// Create a customer shipping profile.
	//
	public function create_customer_shipping_profile()
	{
		$this->_call = 'createCustomerShippingAddressRequest';
		$this->_build_shipping_profile_xml();
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse[$this->_responsecall]['customerAddressId'];
		}
		
		return 0;
	}

	//
	// Update a customer shipping profile.
	//
	public function update_customer_shipping_profile($custid, $shipid)
	{
		$this->_call = 'updateCustomerShippingAddressRequest';
		$this->_build_shipping_profile_xml($custid, $shipid);
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return 1;
		}
		
		return 0;
	}
	
	//
	// Create a customer transaction profile.
	//
	public function create_customer_transaction_profile($type = 'profileTransAuthCapture')
	{
		$types = array('profileTransAuthCapture', 'profileTransCaptureOnly', 'profileTransAuthOnly');
		if(! in_array($type, $types))
		{
			$this->errormsg = 'create_customer_transaction_profile() parameter must be "profileTransAuthCapture", 
													"profileTransCaptureOnly", "profileTransAuthOnly", or empty';
			return 0;
		}
	
		$this->_call = 'createCustomerProfileTransactionRequest';
		$this->_build_transaction_profile_xml($type);
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['directResponse'];
			return $this->get_direct_response();
		}
		
		// Error with card.
		if(isset($this->_parsedresponse[$this->_responsecall]['directResponse']))
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['directResponse'];
			return $this->get_direct_response();	
		}
		
		return 0;
	}
	
	//
	// Get all Customers (just their Id's).
	//
	public function get_customer_ids()
	{
		$this->_call = 'getCustomerProfileIdsRequest';
		$this->_buildXML();
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse[$this->_responsecall]['ids']['numericString'];
		}
		
		return 0;
	}
	
	//
	// Get customer by id.
	//
	public function get_customer($id)
	{
		$this->_call = 'getCustomerProfileRequest';
		$this->_buildXML('<customerProfileId>'. $id . '</customerProfileId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse;
		}
		
		return 0;
	}
	
	//
	// Delete a customer profile.
	//
	public function delete_customer_profile($id)
	{
		$this->_call = 'deleteCustomerProfileRequest';
		$this->_buildXML('<customerProfileId>'. $id . '</customerProfileId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return 1;
		}
		
		return 0;
	}

	//
	// Delete a customer payment profile.
	//
	public function delete_payment_profile($custid, $paymentid)
	{
		$this->_call = 'deleteCustomerPaymentProfileRequest';
		$this->_buildXML('<customerProfileId>'. $custid . '</customerProfileId>' . 
											'<customerPaymentProfileId>' . $paymentid . '</customerPaymentProfileId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return 1;
		}
		
		return 0;
	}
	
	//
	// Delete a customer shipping profile.
	//
	public function delete_shipping_profile($custid, $shipid)
	{
		$this->_call = 'deleteCustomerShippingAddressRequest';
		$this->_buildXML('<customerProfileId>'. $custid . '</customerProfileId>' . 
											'<customerAddressId>' . $shipid . '</customerAddressId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return 1;
		}
		
		return 0;
	}
	
	//
	// Get a Payment profile for a particular customer.
	//
	public function get_payment_profile($custid, $payid)
	{
		$this->_call = 'getCustomerPaymentProfileRequest';
		$this->_buildXML('<customerProfileId>'. $custid . '</customerProfileId>' . 
											'<customerPaymentProfileId>' . $payid . '</customerPaymentProfileId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse[$this->_responsecall]['paymentProfile'];
		}
		
		return 0;
	}
	
	//
	// Get a Shipping profile for a particular customer.
	//
	public function get_shipping_profile($custid, $shipid)
	{
		$this->_call = 'getCustomerShippingAddressRequest';
		$this->_buildXML('<customerProfileId>'. $custid . '</customerProfileId>' . 
											'<customerAddressId>' . $shipid . '</customerAddressId>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			return $this->_parsedresponse[$this->_responsecall]['address'];
		}
		
		return 0;
	}
	
	//
	// Validate a profile that is already saved. Just to make sure the card is still good.
	//
	public function validate_profile($custid, $payid, $shipid)
	{
		$this->_call = 'validateCustomerPaymentProfileRequest';
		$this->_buildXML('<customerProfileId>'. $custid . '</customerProfileId>' . 
											'<customerPaymentProfileId>' . $payid . '</customerPaymentProfileId>' .
											'<customerShippingAddressId>' . $shipid . '</customerShippingAddressId>' .
											'<validationMode>' . $this->_validationmode . '</validationMode>');
		$this->_process();
		
		if($this->_parsedresponse && (! $this->_error)) 
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['directResponse'];
			return $this->get_direct_response();
		}
		
		// Error with card.
		if(isset($this->_parsedresponse[$this->_responsecall]['directResponse']))
		{
			$this->_directresponse = $this->_parsedresponse[$this->_responsecall]['directResponse'];
			return $this->get_direct_response();	
		}
		
		return 0;
	}
	
	//
	// Get direct response.
	//
	public function get_direct_response()
	{
		return explode(',', $this->_directresponse);
	}
	
	//
	// Set a line item for an order.
	//
	public function setLineItem($itemId, $name, $description, $quantity, $unitprice, $taxable = 'false')
	{
		$this->_items[] = array('itemId' => $itemId, 'name' => $name, 'description' => $description, 'quantity' => $quantity, 'unitPrice' => $unitprice, 'taxable' => $taxable);
	}

	//
	// Returns any error message.
	//
	public function get_error_msg()
	{
		return $this->errormsg;
	}
	
	// ----------------- Private functions -------------- //

	//
	// Set default params.
	//
	private function _set_default_params()
	{
		$list = array('merchantCustomerId', 'description', 'email', 'cardNumber', 'customerType', 'billToFirstName', 
									'billToLastName', 'billToCompany', 'billToAddress', 'billToCity', 'billToState', 'billToZip', 
									'billToCountry', 'billToPhoneNumber', 'billToFaxNumber', 'expirationDate', 'accountType',
									'nameOnAccount', 'echeckType', 'bankName', 'routingNumber', 'accountNumber', 'dlState', 'dlNumber',
									'dlDateOfBirth', 'customerProfileId', 'shipToFirstName', 'shipToLastName', 'shipToCompany', 'shipToAddress', 
									'shipToCity', 'shipToState', 'shipToZip', 'shipToCountry', 'shipToPhoneNumber', 'shipToFaxNumber', 'refId',
									'amount', 'taxAmount', 'taxName', 'taxDescription', 'shipAmount', 'shipName', 'shipDescription', 'dutyAmount', 
									'dutyName', 'dutyDescription', 'orderInvoiceNumber', 'description', 'purchaseOrderNumber', 'taxExempt', 'recurringBilling', 
									'cardCode', 'orderInvoiceNumber', 'approvalCode');
		foreach($list AS $row)
		{
			$this->_params[$row] = '';
		}
		
		$this->_items = array();
		$this->_params['customerType']	= 'individual';
		$this->_directresponse = '';
	}

	//
	// Build Customer Profile XML.
	//
	private function _build_customer_profile_xml($id = NULL)
	{
		$body =	'<profile>';

		$body .= '<merchantCustomerId>' . $this->_params['merchantCustomerId'] . '</merchantCustomerId>' .
						'<description>' . $this->_params['description'] . '</description>' .
 						'<email>' . $this->_params['email'] . '</email>';
 		
		if(! is_null($id))				
 			$body .= '<customerProfileId>' . $id . '</customerProfileId>';
 	
 		$body .= '</profile>';
 						
 		$this->_buildXML($body);
	}
	
	//
	// Build a payment profile.
	//
	private function _build_payment_profile_xml($custid = NULL, $payid = NULL)
	{
		$body = '<customerProfileId>'. $this->_params['customerProfileId'] . '</customerProfileId>
						<paymentProfile>
							<customerType>'. $this->_params['customerType'] . '</customerType>
							<billTo>
								<firstName>' . $this->_params['billToFirstName'] . '</firstName>
								<lastName>' . $this->_params['billToLastName'] . '</lastName>
								<company>' . $this->_params['billToCompany'] . '</company>
								<address>' . $this->_params['billToAddress'] . '</address>
								<city>' . $this->_params['billToCity'] . '</city>
								<state>' . $this->_params['billToState'] . '</state>
								<zip>' . $this->_params['billToZip'] . '</zip>
								<country>'. $this->_params['billToCountry'] . '</country>
								<phoneNumber>' . $this->_params['billToPhoneNumber'] . '</phoneNumber>
								<faxNumber>' . $this->_params['billToFaxNumber'] . '</faxNumber>
							</billTo>
							<payment>';
            
		if(! empty($this->_params['cardNumber']))
		{
			$body .= '<creditCard>
				<cardNumber>'. $this->_params['cardNumber'].'</cardNumber>
				<expirationDate>'.$this->_params['expirationDate'].'</expirationDate>
			</creditCard>';
		}
		else if(! empty($this->_params['accountNumber']))
		{
			$body .= '<bankAccount>
				    <accountType>'.$this->_params['accountType'].'</accountType>
				    <nameOnAccount>'.$this->_params['nameOnAccount'].'</nameOnAccount>
				    <echeckType>'. $this->_params['echeckType'].'</echeckType>
				    <bankName>'. $this->_params['bankName'].'</bankName>
				    <routingNumber>'.$this->_params['routingNumber'].'</routingNumber>
				    <accountNumber>'.$this->_params['accountNumber'].'</accountNumber>
				</bankAccount>
				<driversLicense>
				    <dlState>'. $this->_params['dlState'].'</dlState>
				    <dlNumber>'. $this->_params['dlNumber'].'</dlNumber>
				    <dlDateOfBirth>'.$this->_params['dlDateOfBirth'].'</dlDateOfBirth>
				</driversLicense>';
		
		}
            
		$body .= '</payment>';
		
		if(! is_null($custid) && ! is_null($payid))
		{
			$body .= '<customerPaymentProfileId>' . $payid . '</customerPaymentProfileId>';
		}
		
		$body	.= '</paymentProfile>';
		
		$body .= '<validationMode>' . $this->_validationmode . '</validationMode>';

 		$this->_buildXML($body);
	}
	
	//
	// Build a Shipping profile.
	//
	private function _build_shipping_profile_xml($custid = NULL, $shipid = NULL)
	{
		$body =	'<customerProfileId>'. $this->_params['customerProfileId'] . '</customerProfileId>
				<address>
			    <firstName>' . $this->_params['shipToFirstName'] . '</firstName>
			    <lastName>' . $this->_params['shipToLastName'] . '</lastName>
			    <company>' . $this->_params['shipToCompany'] . '</company>
			    <address>' . $this->_params['shipToAddress'] . '</address>
			    <city>' . $this->_params['shipToCity'] . '</city>
			    <state>' . $this->_params['shipToState'] . '</state>
			    <zip>' . $this->_params['shipToZip'] . '</zip>
			    <country>' . $this->_params['shipToCountry'] . '</country>
			    <phoneNumber>'. $this->_params['shipToPhoneNumber'] . '</phoneNumber>
			    <faxNumber>'. $this->_params['shipToFaxNumber'] . '</faxNumber>';
			    
		if(! is_null($custid) && ! is_null($shipid))
		{
			$body .= '<customerAddressId>' . $shipid . '</customerAddressId>';
		}
			
		$body .= '</address>';
			
		$this->_buildXML($body);	
	}
	
	
	//
	// Build a Transaction Profile.
	//
	private function _build_transaction_profile_xml($type)
	{
		$body = '<transaction>
				<' . $type . '>
				<amount>' . $this->_params['amount'] . '</amount>';
        
		if(! empty($this->_params['taxAmount']))
		{
		  $body .= '<tax>
		  	<amount>' . $this->_params['taxAmount'] . '</amount>
		  	<name>' . $this->_params['taxName'] . '</name>
		  	<description>' . $this->_params['taxDescription'] . '</description>
		  </tax>';
		}
		
		if(! empty($this->_params['shipAmount']))
		{
		  $body .= '<shipping>
		  	<amount>'. $this->_params['shipAmount'].'</amount>
		  	<name>'. $this->_params['shipName'] .'</name>
		  	<description>'.$this->_params['shipDescription'].'</description>
		  </shipping>';
		}
		
		if(! empty($this->_params['dutyAmount']))
		{
		  $body .= '<duty>
		  	<amount>'. $this->_params['dutyAmount'].'</amount>
		  	<name>'. $this->_params['dutyName'] .'</name>
		  	<description>'.$this->_params['dutyDescription'].'</description>
		  </duty>';
		}
		
		$body .= $this->_getLineItems();
		
		$body .= '<customerProfileId>' . $this->_params['customerProfileId'] . '</customerProfileId>
		  				<customerPaymentProfileId>' . $this->_params['customerPaymentProfileId'] . '</customerPaymentProfileId>';
		
		if(! empty($this->_params['customerShippingAddressId']))
		{
		  $body .= '<customerShippingAddressId>' . $this->_params['customerShippingAddressId'] . '</customerShippingAddressId>';
		}
		
		if(! empty($this->_params['orderInvoiceNumber']))
		{
		  $body .= '<order>
		  	<invoiceNumber>' . $this->_params['invoiceNumber'] . '</orderInvoiceNumber>
		  	<description>' . $this->_params['description'] . '</orderDescription>
		  	<purchaseOrderNumber>' . $this->_params['purchaseOrderNumber'] . '</orderPurchaseOrderNumber>
		  </order>';
		}
		
		if(! empty($this->_params['taxExempt']))
		{
			$body .= '<taxExempt>' . $this->_params['taxExempt'] . '</taxExempt>';
		}
		  
		if(! empty($this->_params['recurringBilling']))
		{
			$body .= '<recurringBilling>' . $this->_params['recurringBilling'] . '</recurringBilling>';
		}
		
		if(! empty($this->_params['cardCode']))
		{
			$body .= '<cardCode>' . $this->_params['cardCode'] . '</cardCode>';
		}
		
		if(! empty($this->_params['orderInvoiceNumber']))
		{
		  $body .= '<approvalCode>' . $this->_params['approvalCode'] . '</approvalCode>';
		}
        
		$body .= '</' . $type . '>
				</transaction>';
                      
		$this->_buildXML($body);
	}

	//
	// Build out XMl for line items
	//
	private function _getLineItems()
	{
		$tempXml = '';
	    
		foreach ($this->_items as $item)
		{
			$tempXml .= '<lineItems>';
			
			foreach ($item as $key => $value)
			{
				$tempXml .= "    " . '<' . $key . '>' . $value . '</' . $key . '>' . "\n";
			}
	    
			$tempXml .= '</lineItems>';
		}
	    
		return $tempXml;
	}

	//
	// Build Standard XML....
	//
	private function _buildXML($body = '')
	{
		$this->_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
			"<$this->_call xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					$this->_merchant_authentication_block() .
					'<refId>' . $this->_params['refId'] . '</refId>' . $body .
			"</$this->_call>";
	}
	
	//
	// Put this class into test mode. You do that by changing the request url's subdomain.
	//
	private function _set_testmode()
	{
		$this->_loginhost = $this->_testhost;
		$this->_set_url();
		
		log_message('debug', "AuthorizeCimLib Set To Test Mode");
	}
	
	//
	// Set the full URL for requests.
	//
	private function _set_url()
	{
		$this->_url = 'https://' . $this->_loginhost . $this->_loginpath;
	}
	
	//
	// Send request to Authorize Servers.
	//
	private function _process()
	{	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_xml);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$this->_response = curl_exec($ch);
		
		// If there was an error. 
		if(curl_exec($ch) === false)
		{
			$this->_success = FALSE;
			$this->_error = TRUE;
			$this->errormsg = curl_error($ch);
			
			log_message('error', 'AuthorizedCim Error On Curl - ' . $this->errormsg);
		}
		else // Success from curl now process
		{
			$this->_parseResults();
			if($this->_resultCode === 'Ok')
			{
				$this->errormsg = '';
				$this->_success = TRUE;
				$this->_error   = FALSE;
			}
			else
			{
				$this->errormsg = $this->_responseText;
				$this->_success = FALSE;
				$this->_error   = TRUE;
			}
		}
		
		curl_close($ch);
		unset($ch);
	}
	
	//
	// Parse Authorize.net's response.
	//
	private function _parseResults()
	{		
		$response = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $this->_response);
		$this->_parsedresponse = $this->_xml2array($response);
		$this->_responsecall = str_ireplace('Request', 'Response', $this->_call);

		if(isset($this->_parsedresponse['ErrorResponse']))
		{
			$this->_responsecall = 'ErrorResponse';		
		}

		$this->_resultCode = $this->_parsedresponse[$this->_responsecall]['messages']['resultCode'];
    $this->_responseCode = $this->_parsedresponse[$this->_responsecall]['messages']['message']['code'];
    $this->_responseText = $this->_parsedresponse[$this->_responsecall]['messages']['message']['text'];
	}
	
	//
	// Creates the XML needed for the merchant Auth.
	//
	private function _merchant_authentication_block() 
	{
		return
        "<merchantAuthentication>".
        "<name>" . $this->_loginname . "</name>".
        "<transactionKey>" . $this->_loginkey . "</transactionKey>".
        "</merchantAuthentication>";
	}
	
	// ------------- Helper Functions --------------------- //
	
	// 
	// xml2array() will convert the given XML text to an array in the XML structure. 
	// Link: http://www.bin-co.com/php/scripts/xml2array/ 
	//
	private function _xml2array($contents, $get_attributes=1, $priority = 'tag') 
	{ 
		if(!$contents) return array(); 
		
		if(!function_exists('xml_parser_create')) { 
		    //print "'xml_parser_create()' function not found!"; 
		    return array(); 
		} 
		
		//Get the XML parser of PHP - PHP must have this module for the parser to work 
		$parser = xml_parser_create(''); 
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss 
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
		xml_parse_into_struct($parser, trim($contents), $xml_values); 
		xml_parser_free($parser); 
		
		if(!$xml_values) return;//Hmm... 
		
		//Initializations 
		$xml_array = array(); 
		$parents = array(); 
		$opened_tags = array(); 
		$arr = array(); 
		
		$current = &$xml_array; //Refference 
		
		//Go through the tags. 
		$repeated_tag_index = array();//Multiple tags with same name will be turned into an array 
		foreach($xml_values as $data) { 
		    unset($attributes,$value);//Remove existing values, or there will be trouble 
		
		    //This command will extract these variables into the foreach scope 
		    // tag(string), type(string), level(int), attributes(array). 
		    extract($data);//We could use the array by itself, but this cooler. 
		
		    $result = array(); 
		    $attributes_data = array(); 
		     
		    if(isset($value)) { 
		        if($priority == 'tag') $result = $value; 
		        else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode 
		    } 
		
		    //Set the attributes too. 
		    if(isset($attributes) and $get_attributes) { 
		        foreach($attributes as $attr => $val) { 
		            if($priority == 'tag') $attributes_data[$attr] = $val; 
		            else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr' 
		        } 
		    } 
		
		    //See tag status and do the needed. 
		    if($type == "open") {//The starting of the tag '<tag>' 
		        $parent[$level-1] = &$current; 
		        if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag 
		            $current[$tag] = $result; 
		            if($attributes_data) $current[$tag. '_attr'] = $attributes_data; 
		            $repeated_tag_index[$tag.'_'.$level] = 1; 
		
		            $current = &$current[$tag]; 
		
		        } else { //There was another element with the same tag name 
		
		            if(isset($current[$tag][0])) {//If there is a 0th element it is already an array 
		                $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
		                $repeated_tag_index[$tag.'_'.$level]++; 
		            } else {//This section will make the value an array if multiple tags with the same name appear together
		                $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
		                $repeated_tag_index[$tag.'_'.$level] = 2; 
		                 
		                if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
		                    $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
		                    unset($current[$tag.'_attr']); 
		                } 
		
		            } 
		            $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1; 
		            $current = &$current[$tag][$last_item_index]; 
		        } 
		
		    } elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
		        //See if the key is already taken. 
		        if(!isset($current[$tag])) { //New Key 
		            $current[$tag] = $result; 
		            $repeated_tag_index[$tag.'_'.$level] = 1; 
		            if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data; 
		
		        } else { //If taken, put all things inside a list(array) 
		            if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array... 
		
		                // ...push the new element into that array. 
		                $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
		                 
		                if($priority == 'tag' and $get_attributes and $attributes_data) { 
		                    $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
		                } 
		                $repeated_tag_index[$tag.'_'.$level]++; 
		
		            } else { //If it is not an array... 
		                $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
		                $repeated_tag_index[$tag.'_'.$level] = 1; 
		                if($priority == 'tag' and $get_attributes) { 
		                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
		                         
		                        $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
		                        unset($current[$tag.'_attr']); 
		                    } 
		                     
		                    if($attributes_data) { 
		                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
		                    } 
		                } 
		                $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken 
		            } 
		        } 
		
		    } elseif($type == 'close') { //End of tag '</tag>' 
		        $current = &$parent[$level-1]; 
		    } 
		} 
		 
		return($xml_array); 
	}
}

/* End of file AuthorizedCim.php */
/* Location: ./application/libraries/authroizedcim.php */