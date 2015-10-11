<?php
/* 
 * QIWI Rest PHP - Simple library
 * 
 * Copyright 2013 - 2015 EasyCoding Team (ECTeam).
 * Copyright 2005 - 2015 EasyCoding Team.
 * 
 * License: GNU GPL version 3.
 *
 * EasyCoding Team's official blog: http://www.easycoding.org/
 * 
 */

class QCONST
{
	const STATUS_WAITING = "waiting";
	const STATUS_PAID = "paid";
	const STATUS_REJECTED = "rejected";
	const STATUS_UNPAID = "unpaid";
	const STATUS_EXPIRED = "expired";
	const STATUS_PROCESSING = "processing";
	const STATUS_SUCCESS = "success";
	const STATUS_FAIL = "fail";
	
	const MOBILE = "mobile";
	const QIWI = "qw";
	
	const SUCCESS = 0;
	const INCORRECT_DATA = 5;
	const SERVER_BUSY = 13;
	const FORBIDDEN_OPERATION = 78;
	const AUTH_ERROR = 150;
	const PROTOCOL_ERROR = 152;
	const WRONG_INVOICE = 210;
	const DUPL_BILL = 215;
	const SMALL_AMOUNT = 241;
	const LARGE_AMOUNT = 242;
	const WRONG_ACCOUNT = 298;
	const ERROR = 300;
	const WRONG_PHONE = 303;
	const AUTH_BLOCKED = 316;
	const NO_RIGHTS = 319;
	const IP_BLOCKED = 339;
	const WRONG_REQUEST = 341;
	const LIMIT_EXCEED = 700;
	const ACCOUNT_BLOCKED = 774;
	const FORBIDDEN_CCY = 1001;
	const CONVERT_RATE_ERROR = 1003;
	const OPERATOR_ERROR = 1019;
	const CHANGE_ERROR = 1419;
}


 
class QIWI
{	
	//ID of your shop == qiwi user id
	private $ShopID;
	
	//Rest ID - get on https://ishop.qiwi.com/options/rest.action
	private $RestID;
	
	//Rest Password - get on https://ishop.qiwi.com/options/rest.action
	private $RestPassword;
	
	//ist of regular expressions for the preg_match() - taken from official documentation
	const INVOICEID_REGEX = "/^.{1,200}$/";
	const PHONE_REGEX = "/tel:\+\d{1,15}$/";
	const AMOUNT_REGEX = "/^\d+(.\d{0,3})?$/";
	const CCY_REGEX = "/^[a-zA-Z]{3}$/";
	const COMMENT_REGEX = "/^.{0,255}$/";
	const LIFETIME_REGEX = "/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/";
	const PAYSOURCE_REGEX = "/^((mobile)|(qw)){1}$/";
	const PRV_REGEX = "/^.{1,100}$/";
	const ACCOUNT_REGEX = "/^.{0,100}$/";
	const EXTRAS_REGEX = "/^.{0,500}$/";
	const STATUS_REGEX = "/^[a-z]{1,15}$/";
	const URL_REGEX = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\\\".,<>?«»“”‘’]))/";
	
	//QIWI Rest API URL
	const API_URL = "https://w.qiwi.com/api/v2/prv/%s";
	
	//QIWI redirect link
	const REDIRECT_URL = "https://qiwi.com/order/external/main.action";
	
	//Phone prefix
	const PHONE_PREFIX = "tel:";
	
	private $InvoiceID;
	private $Phone;
	private $Amount;
	private $Comment;
	private $Curency;
	private $LifeTime;
	private $PaySource;
	private $PrvName;
	private $Account;
	private $Extras;
	private $Iframe;
	private $SuccessUrl;
	private $Target;
	private $Status;
	
	//Arrays that contain all the invoice info
	private $Parameters = array();
	private $Result = array();
	
	//Constructor - saving the ShopID, rest ID and pass
	public function __construct( $id, $restid, $pass ) 
	{
		$this -> ShopID = $id;
		$this -> RestID = $restid;
		$this -> RestPassword = $pass;
	}
	
	//The invoice ID, which uses on your website as the unique identifier
	public function setInvoiceID( $id )
	{
		$this -> InvoiceID = preg_match( self::INVOICEID_REGEX, $id ) ? $id : NULL;
	}
	
	//The Visa QIWI Wallet user’s ID, to whom the invoice is issued 
	public function setPhone( $number )
	{
		$this -> Phone = preg_match( self::PHONE_REGEX, self::PHONE_PREFIX . $number) ? self::PHONE_PREFIX . $number : NULL;
	}
	
	//The invoice amount - positive number rounded up to 2 or 3 decimal places after the comma
	public function setAmount( $price )
	{
		$this -> Amount = preg_match( self::AMOUNT_REGEX, $price) ? $price : NULL;
	}
	
	//Invoice currency identifier (Alpha-3 ISO 4217 code)
	public function setCurency( $price )
	{
		$this -> Curency = preg_match( self::CCY_REGEX, $price ) ? $price : NULL;
	}
	
	//Comment to the invoice which shown to user on QIWI pay page - any text up to 255 symbols
	public function setComment( $text )
	{
		$this -> Comment = preg_match( self::COMMENT_REGEX, $text ) ? $text : NULL;
	}
	
	//Date and time up to which the invoice is available for payment. If the invoice is not paid by this date it will become void and will be assigned a final status
	public function setLifeTime( $lifetime )
	{
		//Check is it absolute and relative time
		$lifetime += strlen( $lifetime ) < 9 ? time() : 0;
		
		//Saving the time format without timezone_abbreviations_list
		$time = substr(date('c', $lifetime), 0, strpos( date('c', $lifetime), "+" ) );
		
		//Check for compliance
		$this -> LifeTime = preg_match( self::LIFETIME_REGEX, $time ) ? $time : NULL;		
	}
	
	//If the value is "mobile" the user’s MNO balance will be used as a funding source. If the value is "qw", any other funding source is used available in Visa QIWI Wallet interface
	public function setPaySource( $pay_source )
	{
		$this -> PaySource = preg_match( self::PAYSOURCE_REGEX, $pay_source ) ? $pay_source : NULL;
	}
	
	//Merchant’s name - any text
	public function	setPrvName( $prv_name )
	{
		$this -> PRVName = preg_match( self::PRV_REGEX, $prv_name ) ? $prv_name : NULL;
	}
	
	//Client's account on the merchant's side, i.e. contract number - non-empty string of no more than 100 symbols
	public function	setAccount( $account )
	{
		$this -> Account = preg_match( self::ACCOUNT_REGEX, $account );
	}
	
	//Extra-parameters of the request - up to 500 characters
	public function	setExtras( $extras )
	{
		$this -> Extras = preg_match( self::EXTRAS_REGEX, $extras ) ? $extras : NULL;
	}
	
	//It means that invoice page would be opened in "iframe"
	public function setIframe( $iframe )
	{
		$this -> Iframe = is_bool( $iframe ) ? $iframe : false;
	}
	
	//The URL to which the payer will be redirected in case of successful creation of Visa QIWI Wallet transaction
	public function setSuccessURL( $url )
	{
		$this -> SuccessUrl = preg_match( self::URL_REGEX, $url ) ? urlencode( $url ) : urlencode( $_SERVER["HTTP_HOST"] );
	}
	
	//The URL to which the payer will be redirected when creation of Visa QIWI Wallet transaction is unsuccessful
	public function setFailUrl( $url )
	{
		$this -> FailUrl = preg_match( self::URL_REGEX, $url ) ? urlencode( $url ) : urlencode( $_SERVER["HTTP_HOST"] );
	}
	
	//This parameter means that hyperlink specified in successUrl / failUrl parameter opens in "iframe" page 
	public function setTarget( $target )
	{
		$this -> Target = $target;
	}	
	
	//New invoice status 
	public function setStatus( $status )
	{
		$this -> Status = preg_match( self::STATUS_REGEX, $status ) ? $status : NULL;
	}
	
	//Getting the status code of the request
	public function getResult()
	{
		return $this -> Result["result_code"];
	}
	
	//Getting the invoice id
	public function getInvoiceID()
	{
		return $this -> Result["bill_id"];
	}
	
	//Getting the amount of the invoice
	public function getAmount()
	{
		return $this -> Result["amount"];
	}
	
	//Getting the currency of the invoice
	public function getCurrency()
	{
		return $this -> Result["ccy"];
	}
	
	//Getting the status code of the invoice
	public function getStatus()
	{
		return $this -> Result["status"];
	}
	
	//Getting the error code of the invoice
	public function getCode()
	{
		return $this -> Result["error"];
	}
	
	//Getting the user's phone number
	public function getPhone()
	{
		return $this -> Result["user"];
	}
	
	//Getting comment of the invoice
	public function getComment()
	{
		return $this -> Result["comment"];
	}
	
	//Getting the refund id of rejected invoice
	public function getRefundID()
	{
		return $this -> Result["refund_id"];
	}
	
	//Getting the errors description
	public function getError()
	{
		return $this -> Result["description"];
	}
	
	//Creating a new invoice
	public function newInvoice()
	{
		//Generate API url
		$API_URL = sprintf( self::API_URL, $this -> ShopID . "/bills/" . $this -> InvoiceID );
		
		//Let's clear parameters array
		unset( $this -> Parameters );
		
		//Taking all parametrs in one array
		$this -> Parameters["amount"] = $this -> Amount;
		$this -> Parameters["ccy"] = $this -> Curency;
		$this -> Parameters["comment"] = $this -> Comment;
		$this -> Parameters["user"] = $this -> Phone;
		$this -> Parameters["lifetime"] = $this -> LifeTime;
		
		if( !empty( $this -> PaySource ) )
			$this -> Parameters["pay_source"] = $this -> PaySource;
		
		if( !empty( $this -> PrvName ) )
			$this -> Parameters["prv_name"] = $this -> PrvName;
		
		if( !empty( $this -> Account ) )
			$this -> Parameters["account"] = $this -> Account;
		
		if( !empty( $this -> Extras ) )
			$this -> Parameters["extras"] = $this -> Extras;
		
		//Sending request with our parametrs
		return $this -> SendRequest( $API_URL, "PUT" );		
	}
	
	//Generate a redirection link to QIWI Shop
	public function getUrl()
	{
		//Add required parametrs
		$url = self::REDIRECT_URL . "?shop=" . $this -> ShopID . "&transaction=" . $this -> InvoiceID;
		
		//Add optional parametr it is not empty
		if( !empty( $this -> Iframe ) )
			$url .= "&iframe=" . $this -> Iframe;
		
		if( !empty( $this -> SuccessUrl ) )
			$url .= "&successUrl=" . $this -> SuccessUrl;
		
		if( !empty( $this -> FailUrl ) )
			$url .= "&failUrl=" . $this -> FailUrl;
		
		if( !empty( $this -> Target ) )
			$url .= "&target=" . $this -> Target;
		
		//Returns the resulting value
		return $url;
	}
	
	//Returning all the info about invoice
	public function getInvoice( $id )
	{
		//Saving the data
		$this -> InvoiceID = $id;
		
		//Creating the api url
		$API_URL = sprintf( self::API_URL, $this -> ShopID . "/bills/" . $this -> InvoiceID );
		
		//Let's clear parameters array
		unset( $this -> Parameters );
		
		//Sending request without parametrs
		return $this -> SendRequest( $API_URL, "GET" );
	}	
	
	//Canceling the invoice
	public function cancelInvoice( $id )
	{
		//Saving the data
		$this -> InvoiceID = $id;
		
		//Creating the api url
		$API_URL = sprintf( self::API_URL, $this -> ShopID . "/bills/" . $this -> InvoiceID );
		
		//Let's clear parameters array
		unset( $this -> Parameters );
		
		//Add status to the parameters list
		$this -> Parameters["status"] = "rejected";
		
		//Sending request with parametrs
		return $this -> SendRequest( $API_URL, "PATCH" );
	}
	
	//Doing the refunds
	public function getRefund( $refund_id, $amount )
	{
		//Creating the api url
		$API_URL = sprintf( self::API_URL, $this -> ShopID . "/bills/" . $this -> InvoiceID . "/refund/" . $refund_id );
		
		//Let's clear parameters array
		unset( $this -> Parameters );
		
		//Add amount value to the parameters array
		$this -> Parameters["amount"] = $amount;
		
		//Sending request with parametrs
		return $this -> SendRequest( $API_URL, "PUT" );
	}
	
	//Checking the refund status
	public function getRefundStatus( $refund_id )
	{
		//Creating the api url
		$API_URL = sprintf( self::API_URL, $this -> ShopID . "/bills/" . $this -> InvoiceID . "/refund/" . $refund_id );
		
		//Let's clear parameters array
		unset( $this -> Parameters );
		
		//Sending request without parametrs
		return $this -> SendRequest( $API_URL, "GET" );
	}
	
	//Sending the all requests
	private function SendRequest( $url, $method )
	{
		//Initializing curl
		$ch = curl_init();
		
		//Adding some important headers
		$headers = array(
			"Accept: text/json",
			"Content-Type: application/x-www-form-urlencoded; charset=utf-8"
		);
		
		//Adding some values to curl		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this -> RestID . ":" . $this -> RestPassword);
		
		//Add parameters to curl if it is not GET request
		if( $method != "GET" )	
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $this -> Parameters ) );
		else 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array() ) );
		
		//Getting and parsing the response to array
		$response = json_decode( curl_exec( $ch ), true );
		
		//If the result is successful - saving the bill data to result array
		if( $response["response"]["result_code"] == QCONST::SUCCESS )
		{			
			//Saving the result elements values to variable
			$this -> Result = $response["response"]["bill"];
		}
		else {
			//Saving the error description
			$this -> Result["description"] = $response["response"]["description"];
		}
		
		//Add result_code to the result array
		$this -> Result["result_code"] = $response["response"]["result_code"];
		
		//Return all the response values as big array
		return $response;
	}
}