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
	protected $maxoptraApiVersion = '1';

/**
 * API Key for Authentication
 */
	protected $apiKey = null;

/**
 * URL for Maxoptra REST API
 * Maxoptra does not yet have a sandbox, all request are made live
 */
	protected $restUrl = 'http://live.maxoptra.com:80/rest/distribution-api/';

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
	public function __construct($key = null) {

		if(is_null($key)){
			throw new MaxoptraException(__d('maxoptra', 'Do not instantiate Maxoptra without an Maxoptra API Key'));
		}

		if(is_array($key)){
			throw new MaxoptraException(__d('maxoptra', 'Maxoptra API Key should be a string, not an array'));
		}
		
		$this->{'apiKey'} = $key;

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

		if (!$this->apiKey) {

			throw new MaxoptraException(__d('maxoptra' , 'No Maxoptra API Key provided'));
		}

		$request_data = array('apiRequest' => array(
						'apiKey' => $this->apiKey,
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
 * Builds the end point using the REST URI and API Version
 *
 * @return string
 * @author James Mikkelson
 **/
	public function getOrderEndpoint() {

		return $this->restUrl.$this->maxoptraApiVersion.'/orders/';

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
