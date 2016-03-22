<?php
class Ipfs {
	var $url = '';

	function __construct($url) {
		$this->url = $url;
    }

    function post($description) {

		//Save string into temp file
		$file = tempnam(sys_get_temp_dir(), 'POST');
		file_put_contents($file, $description);

		//Post file
		$post = array(
		    "file_box"=> new CURLFile($file),
		);

		$headers = array();
		$headers[] = "Content-Type:multipart/form-data"; // cURL headers for file uploading
		$headers[] = 'Accept: application/json';
	    $options = array(
	        CURLOPT_URL => $this->url,
	        CURLOPT_POST => 1,
	        CURLOPT_HTTPHEADER => $headers,
	        CURLOPT_POSTFIELDS => $post
	    ); // cURL options

    	$ch = curl_init();
	    curl_setopt_array($ch, $options);
	    ob_start();
	    curl_exec($ch);

	    $hash = ob_get_clean();
	    $fileUploaded = json_decode($hash);
	    return $fileUploaded;
    }

}