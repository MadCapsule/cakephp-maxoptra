<?php
/**
 * Maxoptra.php
 * Author:  James Mikkelson
 * Created: February 21st 2014
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * Love you, Cake <3
 *
 * @copyright     James Mikkelson on 2014-02-21.
 * @link          www.madcapsule.com
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::Import('Helper', 'Xml');
App::uses('CakeRequest', 'Network');
App::uses('HttpSocket', 'Network/Http');

/**
 * Class setup
 */
class MaxoptraException extends CakeException {}
class MaxoptraRedirectException extends CakeException {}
class Maxoptra {

/**
 * Maxoptra API Version
 */
	protected $maxoptraApiVersion = '2';

/**
 * API Key for Authentication
 */
	protected $apiKey = null;

/**
 * URL for Maxoptra REST API
 * Maxoptra does not yet have a sandbox, all request are made live
 */
	protected $restUrl = 'http://live.maxoptra.com:80/rest/2/';

/**
 * General definitions
 */
	public $HttpSocket = null;
	public $CakeRequest = null;

/**
 * Constructs Maxoptra
 *
 * @param string Maxoptra API 
 * @return void
 * @author James Mikkelson
 **/
	public function __construct($account = null, $username = null, $password = null) {

		if(is_null($account) || is_null($username) || is_null($password)){
			throw new MaxoptraException(__d('maxoptra', 'Do not instantiate Maxoptra without providing Maxoptra Authentication Credentials'));
		}

		$credentials = array(
							'account'  => $account,
							'username' => $username,
							'password' => $password
						);
							
		$this->{'apiKey'} = $this->createSession($credentials);

	}

/**
 * createSession
 * Creates a session with the Maxoptra REST API
 *
 * @param array $credentials data required to authenticate
 * @return string api key used to forthcoming calls
 * @author James Mikkelson
 **/
	public function createSession($credentials) {

        try {

            if (!$this->HttpSocket) {
                $this->HttpSocket = new HttpSocket();
            }

            $url = $this->getAuthenticationEndpoint().'createSession?accountID='.$credentials['account'].'&user='.$credentials['username'].'&password='.$credentials['password'];
            $options = $this->getRequestOptions();

            // Make the HTTP request
            $response = $this->HttpSocket->post($url, null, $options);

            if($response->code!=200){
                        throw new MaxoptraException($response->code.' '.$response->reasonPhrase);
            }

            // Transform the results
            $result = $this->transformResponse($response->body);

            // Handle the resposne
            if (!empty($result['authResponse']['sessionID'])) {
                return $result['authResponse']['sessionID'];

            }elseif(!empty($result['error']))  {

				$error_output = $result['error']['errorMessage'];
                throw new MaxoptraException(str_replace(array('"', '->'), array('', '-'), $error_output));

            }else{

                throw new MaxoptraException(__d('maxoptra' , 'Unable to authenticate with Maxoptra. Check API Username and Password and Connection.'));

            }

        } catch (SocketException $e) {

            throw new MaxoptraException(__d('maxoptra', 'Something went wrong communicating with Maxoptra.'));

        }
	}	
	
/**
 * Delivery
 * Creates a Delivery Order with Maxoptra
 *
 * @param array $delivery data required to create delivery
 * @return array containing order status, if successfull
 * @author James Mikkelson
 **/
	public function delivery($delivery) {

        try {
            // Turn our array into XML
            $xml = $this->buildXml($delivery);

            if (!$this->HttpSocket) {
                $this->HttpSocket = new HttpSocket();
            }

            $url = $this->getOrderEndpoint().'save';
            $options = $this->getRequestOptions();

            // Make the HTTP request
            $response = $this->HttpSocket->post($url, $xml, $options);
            if($response->code!=200){
                        throw new MaxoptraException($response->code.' '.$response->reasonPhrase);
            }

            // Transform the results
            $result = $this->transformResponse($response->body);

            // Handle the resposne
            if ($result['orders']['order']['status']=='Created') {
                return $result['orders']['order'];

			}elseif(!empty($result['error'])){
			
				$error_output = $result['error']['errorMessage'];
                throw new MaxoptraException(str_replace(array('"', '->'), array('', '-'), $error_output));
			
            }elseif ($result['orders']['order']['status']=='Error' && !empty($result['orders']['order']['errors']))  {

				$error_output = false;
				
				if(empty($result['orders']['order']['errors']['error'][0])){
					$error_output = $result['orders']['order']['errors']['error']['errorMessage'];
					
				}else{
					foreach($result['orders']['order']['errors']['error'] as $error){
						$error_output .= $error['errorMessage']. ' ';
						
					}
					
				}

                throw new MaxoptraException(str_replace(array('"', '->'), array('', '-'), $error_output));

            }else{

                throw new MaxoptraException(__d('maxoptra' , 'Unable to create order with Maxoptra. Check API Key and Connection.'));

            }

        } catch (SocketException $e) {

            throw new MaxoptraException(__d('maxoptra', 'Something went wrong communicating with Maxoptra.'));

        }
	}

/**
 * Turns a data array into XML for a Maxoptra order
 *
 * @param array $array Maxoptra Order request parameters
 * @return XML
 * @author James Mikkelson
 **/
	public function buildXml($array) {

		if (empty($array) || !is_array($array)) {

			throw new MaxoptraException(__d('maxoptra' , 'You must provide an array of request parameters'));
		}

		$request_data = array('apiRequest' => array(
						'sessionID' => $this->apiKey,
						'orders' => array('order' => $array)
						)
				);

		try{	    

			$xml = Xml::build($request_data, array('format' => 'tags'));

		}catch (XmlException $e) {

		    throw new MaxoptraException(__d('maxoptra' , 'Unable to build XML: '.$e->getMessage()));
		}

		return $xml->asXML();

	}

/**
 * Sets request options, such as request headers
 *
 * @return array
 * @author James Mikkelson
 **/
	public function getRequestOptions() {

		$options = array(
		  'header' => array(
		    'Accept' => 'application/xml',
		    'Content-Type' => 'application/xml; charset=UTF-8'
		  )
		);

		return $options;
   
	}

/**
 * Builds the authentication end point using the REST URI and API Version
 *
 * @return string
 * @author James Mikkelson
 **/
	public function getAuthenticationEndpoint() {

		return $this->restUrl.'authentication/';

	}	
	
/**
 * Builds the end point using the REST URI and API Version
 *
 * @return string
 * @author James Mikkelson
 **/
	public function getOrderEndpoint() {

		return $this->restUrl.'distribution-api/orders/';

	}

/**
 * Transform the response XML from Maxoptra to an array
 *
 * @param XML object
 * @return array
 * @author James Mikkelson
 **/
	public function transformResponse($response) {

		try{	    
			$response_array = Xml::toArray(Xml::build($response));

		}catch (XmlException $e) {

		    throw new MaxoptraException(__d('maxoptra' , 'Unable read response XML: '.$e->getMessage()));
		}

		return $response_array['apiResponse'];

	}

}
