<?php
class Cim extends Controller {
	function Cim()
	{
		parent::Controller();	
		$this->load->library('AuthorizeCim');
	}
		
	//
	// This function will get a list of customer profile ids that are already 
	// Uploaded to the CIM.
	// This function is based off page 62 of the CIM_XML_Guide.pdf.
	// "Input Elements for getCustomerProfileIdsRequest"
	//
	function getcustomers()
	{
		if($custs = $this->authorizecim->get_customers()) 
			print_r($custs); 
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";	
	}
		
	//
	// This function will add a new customer profile.
	// This function is based off page 10 of the CIM_XML_guide.pdf. 
	// "Input Elements for createCustomerProfileRequest" 
	//
	// We assume you are createing address and payment profiles in other function calls.
	// The libaray could be modified to support creating them all at once.
	//
	function addprofile()
	{
		// You can sent any one of the 3 arguments. (yourcustomerid, description, email)
		if($newcustid = $this->authorizecim->submit_profile("", "", "woot3@foobar.com")) 
			echo "<b>Success: </b> CustomerProfileId - " . $newcustid . "<br />"; 
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}

	//
	// Just updates the stuff added in add profile. Need to pass in profile id.
	// This function is based off page 65 of the CIM_XML_guide.pdf. 
	// "Input Elements for updateCustomerProfileRequest" 
	//
	function updateprofile($id)
	{
		// You can sent any one of the 3 arguments. (yourcustomerid, description, email)
		if($this->authorizecim->update_profile($id, "675", "", "")) 
			echo "<b>Success: </b> Updated CustomerProfileId - " . $id . "<br />"; 
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}

	//
	// This function will add a new a payment profile.
	// This function is based off page 17 of the CIM_XML_guide.pdf. 
	// "Input Elements for createCustomerPaymentProfileRequest" 
	//
	function addpayment($customerid)
	{
		if($paymentid = $this->authorizecim->submit_paymentprofile($customerid, "4012888818888", "2011-10", "554"))
			echo "<b>Success: </b> PaymentId - " . $paymentid . "<br />";
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}
	
	//
	// This function will add a new a payment profile.
	// This function is based off page 17 of the CIM_XML_guide.pdf. 
	// "Input Elements for createCustomerPaymentProfileRequest" 
	//
	function updatepayment($customerid, $paymentid)
	{
		if($this->authorizecim->update_paymentprofile($customerid, $paymentid, "370000000000002", "2011-10", "5554"))
			echo "<b>Success: </b> Updated - " . $paymentid . "<br />";
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}
	
	//
	// This will submit a payment transaction. (Auth Only)
	// This function is based off page 24 of the CIM_XML_guid.pdf
	// "Input Elements for createCustomerProfileTransactionRequest"
	//
	// In order for this to be useful we need to add a (capture only) Page 42
	//
	function transactionauth($customerid, $paymentid)
	{
		if($this->authorizecim->submit_transaction_authonly($customerid, $paymentid, "25.66"))
			echo "<b>Success: </b> Auth Transaction Complete! <br />";
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}
	
	//
	// This will submit a payment transaction. (Auth & Capture)
	// This function is based off page 24 of the CIM_XML_guid.pdf
	// "Input Elements for createCustomerProfileTransactionRequest"
	//
	function transactionauthcap($customerid, $paymentid)
	{
		if($tranid = $this->authorizecim->submit_transaction_authcap($customerid, $paymentid, "315.88"))
			echo "<b>Success: </b> Transaction Id - $tranid <br />";
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}
	
	function transactionrefund($customerid, $paymentid, $tranid)
	{
		if($tranid = $this->authorizecim->submit_transaction_refund($customerid, $paymentid, "5.88", $tranid))
			echo "<b>Success: </b> Transaction Id - $tranid <br />";
		else 
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";
	}

	//
	// This will get the customer information from the authorize.net database.
	//
	function getcustomer($customerid)
	{
		if($cust = $this->authorizecim->get_customer($customerid))
			print_r($cust);
		else
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";	
	}
	
	//
	// This will delete the customer information from the authorize.net database.
	//
	function deletecustomer($customerid)
	{
		if($cust = $this->authorizecim->delete_customer($customerid))
			echo "<b>Success: </b> Customer Deleted. <br />";
		else
			foreach($this->authorizecim->errormsgs AS $error)
				echo "<b>Error:</b> " . $error['msg'] . "<br>";	
	}
}
?>