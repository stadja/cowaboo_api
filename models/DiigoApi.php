<?php
class DiigoRealApi extends Diigo{

	function __construct($url, $urlRss, $api) {
		parent::__construct($url, $urlRss, $api);
    }
    
    /**
     * description
     *
     * @param string method rest verb (lowecase)
     * @param string url method url
     * @param array data data sent to the server
     * @return array result of the api call
     */
    protected function diigoApiCall($method, $url, $data, $headers)
    {
    	$apikey = 'key=754de7bc88a0d803&filter=all';
    	if (!strpos($url, '?')) {
    		$url .= '?'.$apikey;
    	} else {
    		$url .= '&'.$apikey;
    	}

        $results = $this->api->call($method, $url, $data, $headers);
        return $results; /* result of the api call */
    }
}
?>