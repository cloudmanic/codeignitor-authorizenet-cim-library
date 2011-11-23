<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class AuthorizeCim extends CI_Controller
{	
	public $data = array();
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('authorizecimlib');
		
		// Optional Settings. ( initialize() is to override the config file )
		//$this->authorizecimlib->initialize('XXXXXX', 'XXXXXX', TRUE);
		//$this->authorizecimlib->set_validationmode('liveMode');
	}
	
	//
	// This is a master testing function. By calling this it will test all the 
	// different supported operations with the Authorized.net CIM Library.
	//
	function fulltest()
	{
		// Complete process
		$this->createcustomerprofile();
		$this->getcustomer($this->data['profileid']);
		
		$this->updatecustomerprofile($this->data['profileid']);
		$this->updatepaymentprofile($this->data['profileid'], $this->data['paymentprofileid']);
		$this->updateshippingprofile($this->data['profileid'], $this->data['shippingprofileid']);
		$this->getcustomer($this->data['profileid']);
		$this->validateprofile($this->data['profileid'], $this->data['paymentprofileid'], $this->data['shippingprofileid']);
		
		$this->createtransaction($this->data['profileid'], $this->data['paymentprofileid'], $this->data['shippingprofileid']);
		$this->getpaymentprofile($this->data['profileid'], $this->data['paymentprofileid']);
		$this->getshippingprofile($this->data['profileid'], $this->data['shippingprofileid']);
		$this->deletecustomer($this->data['profileid']);
		
		// Create new customer to delete :)
		echo "<hr />";
		$this->createcustomerprofile();
		$this->deletepaymentprofile($this->data['profileid'], $this->data['paymentprofileid']);
		$this->deleteshippingprofile($this->data['profileid'], $this->data['shippingprofileid']);
		$this->deletecustomer($this->data['profileid']);
				
		// Get all customers.
		echo "<hr />";
		$this->getallcustomerids();
	}
	
	//
	// Validate a saved payment profile. 
	//
	function validateprofile($custid, $payid, $shipid)
	{
		echo '<h1>Validate Profile - validate_profile()</h1>';
	
		if(! $this->authorizecimlib->validate_profile($custid, $payid, $shipid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}	
		
		// Check to see if the card is good.
		$this->_validateresponse();
	}
	
	// 
	// Update a customer profile.
	//
	function updatecustomerprofile($id)
	{
    // Create the and updated basic profile
    $this->authorizecimlib->set_data('email', 'user' . time() . '@updateddomain.com');
    $this->authorizecimlib->set_data('description', 'Updated!! Monthly Membership No. ' . md5(uniqid(rand(), true)));
    $this->authorizecimlib->set_data('merchantCustomerId', substr(md5(uniqid(rand(), true)), 16, 16));
    
		echo '<h1>Update Profile - update_customer_profile()</h1>';
		
		if(! $this->authorizecimlib->update_customer_profile($id))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		echo "Customer Id: $id - Updated successfully.";
	}

	// 
	// Update a payment profile.
	//
	function updatepaymentprofile($custid, $payid)
	{
    // Create the Payment Profile
		$this->authorizecimlib->set_data('customerProfileId', $this->data['profileid']);
		$this->authorizecimlib->set_data('billToFirstName', 'Jane');
		$this->authorizecimlib->set_data('billToLastName', 'Well');
		$this->authorizecimlib->set_data('billToAddress', '321 River Street');
		$this->authorizecimlib->set_data('billToCity', 'Dation');
		$this->authorizecimlib->set_data('billToState', 'NJ');
		$this->authorizecimlib->set_data('billToZip', '54321');
		$this->authorizecimlib->set_data('billToCountry', 'US');
		$this->authorizecimlib->set_data('billToPhoneNumber', '800-555-1234');
		$this->authorizecimlib->set_data('billToFaxNumber', '800-555-2345');
		$this->authorizecimlib->set_data('cardNumber', '4111111111111111');
		$this->authorizecimlib->set_data('expirationDate', (date("Y") + 1) . '-12');
    
		echo '<h1>Update Payment Profile - update_customer_payment_profile()</h1>';
		
		if(! $this->authorizecimlib->update_customer_payment_profile($custid, $payid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		// Check to see if the card is good.
		$this->_validateresponse();
		
		echo "Customer Id: $custid, Payment Id: $payid - Payment Profile Updated successfully.";
	}

	// 
	// Update a shippment profile.
	//
	function updateshippingprofile($custid, $shipid)
	{
    // Create the Shipping Profile
		$this->authorizecimlib->set_data('customerProfileId', $this->data['profileid']);
		$this->authorizecimlib->set_data('shipToFirstName', 'Bob');
		$this->authorizecimlib->set_data('shipToLastName', 'Rosso');
		$this->authorizecimlib->set_data('shipToAddress', '2002 Another Road');
		$this->authorizecimlib->set_data('shipToCity', 'Smallville');
		$this->authorizecimlib->set_data('shipToState', 'NY');
		$this->authorizecimlib->set_data('shipToZip', '55595');
		$this->authorizecimlib->set_data('shipToCountry', 'US');
		$this->authorizecimlib->set_data('shipToPhoneNumber', '800-555-3456');
		$this->authorizecimlib->set_data('shipToFaxNumber', '800-555-4567');
    
		echo '<h1>Update Shipping Profile - update_customer_shipping_profile()</h1>';
		
		if(! $this->authorizecimlib->update_customer_shipping_profile($custid, $shipid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		echo "Customer Id: $custid, Shipping Id: $shipid - Shipping Profile Updated successfully.";
	}
	
	//
	// Get a customer payment profile.
	//
	function getpaymentprofile($cust, $payid)
	{
		echo '<h1>Get Payment Profile - get_payment_profile()</h1>';
		
		if(! $this->data['paymentprofile'] = $this->authorizecimlib->get_payment_profile($cust, $payid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}

		echo '<pre>' . print_r($this->data['paymentprofile'], TRUE) . '</pre>';
	}

	//
	// Get a customer shipping profile.
	//
	function getshippingprofile($cust, $shipid)
	{
		echo '<h1>Get Shipping Profile - get_shipping_profile()</h1>';
		
		if(! $this->data['shippingprofile'] = $this->authorizecimlib->get_shipping_profile($cust, $shipid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}

		echo '<pre>' . print_r($this->data['shippingprofile'], TRUE) . '</pre>';
	}
	
	//
	// Delete a customer shipment profile.
	//
	function deleteshippingprofile($id, $shipid)
	{
		echo '<h1>Delete Shipment Profile - delete_shipping_profile()</h1>';
		
		if(! $this->authorizecimlib->delete_shipping_profile($id, $shipid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}

		echo "<p>Customer Id: $id, Shipping Id: $shipid - Was successfully deleted.</p>";
	}

	//
	// Delete a customer payment profile.
	//
	function deletepaymentprofile($id, $payid)
	{
		echo '<h1>Delete Payment Profile - delete_payment_profile()</h1>';
		
		if(! $this->authorizecimlib->delete_payment_profile($id, $payid))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}

		echo "<p>Customer Id: $id, Payment Id: $payid - Was successfully deleted.</p>";
	}
	
	//
	// Delete a customer profile.
	//
	function deletecustomer($id)
	{
		echo '<h1>Delete Customer Profile - delete_customer_profile()</h1>';
		
		if(! $this->authorizecimlib->delete_customer_profile($id))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}

		echo "<p>Customer Id: $id - Was successfully deleted.</p>";
	}

	
	//
	// Create a transaction.
	//
	function createtransaction($profile_id, $payment_profile_id, $shipping_profile_id)
	{
		$this->authorizecimlib->set_data('amount', '5.00');
		$this->authorizecimlib->set_data('customerProfileId', $profile_id);
		$this->authorizecimlib->set_data('customerPaymentProfileId', $payment_profile_id);
		$this->authorizecimlib->set_data('customerShippingAddressId', $shipping_profile_id);
		$this->authorizecimlib->set_data('cardCode', '123');
		$this->authorizecimlib->setLineItem('12', 'test item', 'it lets you test stuff', '1', '1.00');
	
		echo '<h1>Creating A Transaction Profile - create_customer_transaction_profile()</h1>';
	
		// Types: 'profileTransAuthCapture', 'profileTransCaptureOnly', 'profileTransAuthOnly'
		if(! $this->data['approvalcode'] = $this->authorizecimlib->create_customer_transaction_profile('profileTransAuthCapture'))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		// Find out if it was approved or not.
		$this->_validateresponse();
		
		$this->authorizecimlib->clear_data();
	}
	
	//
	// Create complete customer profile.
	//
	function createcustomerprofile()
	{ 
    // Create the basic profile
    $this->authorizecimlib->set_data('email', 'user' . time() . '@domain.com');
    $this->authorizecimlib->set_data('description', 'Monthly Membership No. ' . md5(uniqid(rand(), true)));
    $this->authorizecimlib->set_data('merchantCustomerId', substr(md5(uniqid(rand(), true)), 16, 16));
    
		echo '<h1>Creating Customer Profile - create_customer_profile()</h1>';
		
		if(! $this->data['profileid'] = $this->authorizecimlib->create_customer_profile())
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
				
		echo '<p> Customer Id: ' . $this->data['profileid'] . '</p>';

 
    // Create the Payment Profile
		$this->authorizecimlib->set_data('customerProfileId', $this->data['profileid']);
		$this->authorizecimlib->set_data('billToFirstName', 'John');
		$this->authorizecimlib->set_data('billToLastName', 'Conde');
		$this->authorizecimlib->set_data('billToAddress', '123 Main Street');
		$this->authorizecimlib->set_data('billToCity', 'Townsville');
		$this->authorizecimlib->set_data('billToState', 'NJ');
		$this->authorizecimlib->set_data('billToZip', '12345');
		$this->authorizecimlib->set_data('billToCountry', 'US');
		$this->authorizecimlib->set_data('billToPhoneNumber', '800-555-1234');
		$this->authorizecimlib->set_data('billToFaxNumber', '800-555-2345');
		//$this->authorizecimlib->set_data('cardNumber', '6111111111111111'); // will produce a decline
		$this->authorizecimlib->set_data('cardNumber', '4111111111111111');
		$this->authorizecimlib->set_data('expirationDate', (date("Y") + 1) . '-12');
		
		echo '<h1>Creating Payment Profile - create_customer_payment_profile()</h1>';
		
		if(! $this->data['paymentprofileid'] = $this->authorizecimlib->create_customer_payment_profile())
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		// Find out if it was approved or not.
		$this->_validateresponse();
		
		echo '<p> Payment Profile Id: ' . $this->data['paymentprofileid'] . '</p>';
			 
		 
    // Create the shipping profile
		$this->authorizecimlib->set_data('customerProfileId', $this->data['profileid']);
		$this->authorizecimlib->set_data('shipToFirstName', 'John');
		$this->authorizecimlib->set_data('shipToLastName', 'Conde');
		$this->authorizecimlib->set_data('shipToAddress', '1001 Other Road');
		$this->authorizecimlib->set_data('shipToCity', 'Townsville');
		$this->authorizecimlib->set_data('shipToState', 'NJ');
		$this->authorizecimlib->set_data('shipToZip', '12345');
		$this->authorizecimlib->set_data('shipToCountry', 'US');
		$this->authorizecimlib->set_data('shipToPhoneNumber', '800-555-3456');
		$this->authorizecimlib->set_data('shipToFaxNumber', '800-555-4567');

		echo '<h1>Creating Shipping Profile - create_customer_shipping_profile()</h1>';
		
		if(! $this->data['shippingprofileid'] = $this->authorizecimlib->create_customer_shipping_profile())
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
		
		echo '<p> Shipping Profile Id: ' . $this->data['shippingprofileid'] . '</p>';
		
		$this->authorizecimlib->clear_data();
	}
	
	//
	// Get one customer by id.
	//
	function getcustomer($id)
	{
		echo '<h1>Get One Customer - get_customer()</h1>';
		
		if(! $this->data['customer'] = $this->authorizecimlib->get_customer($id))
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';
			die();
		}
				
		echo '<pre>' . print_r($this->data['customer'], TRUE) . '</pre>';
		
		$this->authorizecimlib->clear_data();
	}
	
	//
	// Get all Customer id's
	//
	function getallcustomerids()
	{
		echo '<h1>Get All Customers - get_customers()</h1>';
		
		if(! $this->data['customers'] = $this->authorizecimlib->get_customer_ids())
		{
			echo '<p> Error: ' . $this->authorizecimlib->get_error_msg() . '</p>';		
			die();
		}
		
		echo '<pre>' . print_r($this->data['customers'], TRUE) . '</pre>';
		
		$this->authorizecimlib->clear_data();
	}
	
	// ---------- Private helper functions --------------- //
	
	//
	// Validate response.
	//
	private function _validateresponse()
	{
		if($this->authorizecimlib->get_validationmode() != 'none')
		{
			$this->data['approvalcode'] = $this->authorizecimlib->get_direct_response();
			
			switch($this->data['approvalcode'][0])
			{
				// Approved
				case '1':
					echo '<p> Approval Code: ' . $this->data['approvalcode'][6] . '</p>';
					echo '<p> Transaction Id: ' . $this->data['approvalcode'][4] . '</p>';
					echo '<p> Response Text: ' . $this->data['approvalcode'][3] . '</p>';			
				break;
				
				// Declined
				case '2':
					echo '<p> Declied Reason: ' . $this->data['approvalcode'][3] . '</p>';				
				break;
	
				// Error
				case '3':
					echo '<p> Error Reason: ' . $this->data['approvalcode'][3] . '</p>';				
				break;
				
				// Held for review
				case '4':
					echo '<p> Review Reason: ' . $this->data['approvalcode'][3] . '</p>';				
				break;		
			}
		}
	}
}

/* End of file AuthorizedCim.php */
/* Location: ./application/controllers/authroizedcim.php */