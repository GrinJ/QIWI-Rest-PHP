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

//Connecting main class
require_once( "qiwi.class.php" );

//Starting the QIWI class - ShopID, Rest API ID, Rest API Password
$shop = new QIWI( "123456", "12345678", "StrongPassword1" );

//Setting parameters to create a new invoice
$shop -> setInvoiceID( 123 );		//The unique identifier of the account that is used on your website - up to 200 characters
$shop -> setPhone( "+79123456789" );	//The Visa QIWI Wallet user’s ID, to whom the invoice is issued
$shop -> setAmount( 120 );				//The invoice amount - rounded up to 2 or 3 decimal places after the comma
$shop -> setCurency( "RUB" );			//Invoice currency identifier (Alpha-3 ISO 4217 code)
$shop -> setComment( "Creating a test invoice by using the QIWI REST PHP library by EC Team" );		//Comment to the invoice which is shown on the payment page
$shop -> setLifeTime( 120 );			//Еime up to which the invoice is available for payment. Enter the number of seconds to count down from the current time or date+time in UNIX format
$shop -> setPaySource( QCONST::QIWI );	//Set the way to pay the invoice. QCONST::QIWI - to pay using QIWI website or QCONST::MOBILE to pay with mobile phone bills
$shop -> setSuccessUrl( "http://test/success.php" );	//The URL to which the payer will be redirected in case of successful creation of Visa QIWI Wallet transaction.
$shop -> setFailUrl( "http://test/fail.php" );		//The URL to which the payer will be redirected when creation of Visa QIWI Wallet transaction is unsuccessful.

//Creating new invoice
$shop -> newInvoice();

//Checking the result callback
if( $shop -> getResult() == QCONST::SUCCESS )
{
	//If success - redirect to the QIWI website
	echo 'The invoice was created successfully!';
	header('Location: ' . $shop -> getUrl() );
}
else 
	echo $shop -> getError();


//Getting info about existing invoice
$shop -> getInvoice( 123 );

//Let's check if callback is successUrl
if( $shop -> getResult() == QCONST::SUCCESS )
{
	//If invoice status is paid - do something
	if( $shop -> getStatus() == QCONST::STATUS_PAID )
		echo "The invoice was paid";
	else 
		echo "Current status - " . $shop -> getStatus();
}
else
	echo $shop -> getError();
?>
