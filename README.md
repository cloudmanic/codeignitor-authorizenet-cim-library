## Author(s) 
	Spicer Matthews <spicer@cloudmanic.com>
	Cloudmanic Labs, LLC
	http://www.cloudmanic.com
	
	Based On Work From: 
		- John Conde <johnny@johnconde.net>
		- http://www.communitymx.com/content/article.cfm?page=4&cid=FDB14
	
## About
	This is a library for the php framework Codeignitor http://codeigniter.com to make API calls to the Authorize.net CIM interface.
	This is free to use. It would be nice if you make updates if you submitted them.
	
	The best way to understand this library is to read through and run the authorizecim.php controller. Also read through the 100+ pages of 
	the Authorize.net CIM and AIM documents. This library is mostly complete. Every call is not tested against the CIM spec. The CIM spec is 
	100+ pages long. If something is missing and you would like us to add that feature just make a request on the gitHub issues section.
	
	The CIM works in three sections. You create a profile for the customer. Then you attached a payment profile, and then a shipping profile to 
	the customer profile. One customer profile can have many payment and shipping profiles attached to it. To make a credit card transaction 
	you need to set the customer profile, payment profile, shipping profile then create a transaction profile.
	

## Install

Copy the files to these locations.
	
    config/authorizenet.php -> application/config/
    libraries/AuthorizeCimLib.php -> application/libraries/
    controllers/authorizecim.php -> application/controllers/

## Usage
	
	$this->load->library('authorizecimlib')
	
	
## Public Functions
	$this->authorizecimlib->validate_profile($custid, $payid, $shipid)
	$this->authorizecimlib->set_validationmode($mode)
	$this->authorizecimlib->get_validationmode()
	$this->authorizecimlib->set_data($field, $value)
	$this->authorizecimlib->clear_data()
	$this->authorizecimlib->create_customer_profile()
	$this->authorizecimlib->update_customer_profile($id)
	$this->authorizecimlib->create_customer_payment_profile()
	$this->authorizecimlib->update_customer_payment_profile($custid, $payid)
	$this->authorizecimlib->create_customer_shipping_profile()
	$this->authorizecimlib->update_customer_shipping_profile($custid, $shipid)
	$this->authorizecimlib->create_customer_transaction_profile($type)
	$this->authorizecimlib->get_customer_ids()
	$this->authorizecimlib->get_customer($id)
	$this->authorizecimlib->delete_customer_profile($id)
	$this->authorizecimlib->delete_payment_profile($custid, $paymentid)
	$this->authorizecimlib->delete_shipping_profile($custid, $shipid)
	$this->authorizecimlib->get_payment_profile($custid, $payid)
	$this->authorizecimlib->get_shipping_profile($custid, $shipid)
	$this->authorizecimlib->validate_profile($custid, $payid, $shipid)
	$this->authorizecimlib->get_direct_response()
	$this->authorizecimlib->setLineItem($itemId, $name, $description, $quantity, $unitprice, $taxable)
	$this->authorizecimlib->get_error_msg()
	
	
	