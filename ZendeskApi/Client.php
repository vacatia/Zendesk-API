<?php
/**
 * Class definition for ZendeskApi\Client
 */
namespace ZendeskApi;
/**
  * A minimal Zendesk API PHP implementation
  * @package Zendesk
  * @author  Julien Renouard <renouard.julien@gmail.com> (deeply inspired by Darren Scerri <darrenscerri@gmail.com> Mandrill's implemetation)
  * @version 1.0
  */
class Client
{
	/**
	 * API Constructor. If set to test automatically, will return an Exception if the ping API call fails
	 * @param string $apiKey API Key.
	 * @param string $user Username on Zendesk.
	 * @param string $domain Your subdomain on zendesk or domain mask, without https:// nor trailling dot.
	 * @param string $suffix .json by default.
	 * @param bool $test=true Whether to test API connectivity on creation.
	 */
	public function __construct($apiKey, $user, $domain, $suffix = '.json', $test = false)
	{
		$this->api_key = $apiKey;
		$this->user    = $user;
		if (strpos($domain, '.') === false) {
			$this->base = 'https://' . $domain . '.zendesk.com/api/v2';
		} else {
			$this->base = 'https://' . $domain . '/api/v2';
		}
		$this->suffix  = $suffix;
		if ($test === true && !$this->test())
		{
			throw new \Exception('Cannot connect or authentice with the Zendesk API');
		}
	}
	
	/**
	 * Perform an API call.
	 * @param string $url='/tickets' Endpoint URL. Will automatically add the suffix you set if necessary (both '/tickets.json' and '/tickets' are valid)
	 * @param array $json=array() An associative array of parameters
	 * @param string $action Action to perform POST/GET/PUT
	 * @return mixed Automatically decodes JSON responses. If the response is not JSON, the response is returned as is
	 */
	public function call($url, $json, $action)
	{
		if (substr_count($url, $this->suffix) == 0)
		{
			$url .= '.json';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_URL, $this->base.$url);
		curl_setopt($ch, CURLOPT_USERPWD, $this->user."/token:".$this->api_key);
		switch($action){
			case "POST":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
				break;
			case "GET":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
				break;
			case "PUT":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
			default: // we should probably merge POST/PUT/DELETE in the default case
				break;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'Accept: application/json'));
		curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		$output = curl_exec($ch);

		if ($output === false) {
		    throw new \Exception(curl_error($ch), curl_errno($ch));
		}
		curl_close($ch);
		$decoded = json_decode($output, true);

		return is_null($decoded) ? $output : $decoded;
	}
	
	/**
	 * Tests the API using /users/ping
	 * @return bool Whether connection and authentication were successful
	 */
	public function test()
	{
		return $this->call('/tickets', '', 'GET');
	}
}
