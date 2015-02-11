<?php
class MediaWiki {
	var $url = '';
	var $wikipediaUrl = '';
	var $api = '';

	function __construct($url, $wikipediaUrl, $api) {
		$this->url = $url;
		$this->wikipediaUrl = $wikipediaUrl;
		$this->api = $api;
    }

	/**
	 * Return the related info of a tag
	 *
	 * @param string tag the tag from which we want the media info
	 * @return array the related info
	 */
	public function  getRelatedInfo($tag)
	{
		$methodUrl = $this->url.'?format=json&action=query&list=search&srprop=titlesnippet|snippet&continue=&srsearch='.urlencode($tag); 

		$search = $this->api->call('get', $methodUrl);

		$search = json_decode($search);
		$search->suggestion = '';
		if (isset($search->query->searchinfo->suggestion)) {
			$search->suggestion = $search->query->searchinfo->suggestion;
		}
		$search->articles = $search->query->search;

		$relatedInfo = array();
		foreach ($search->articles as $info) {
			$info->url = $this->wikipediaUrl.'/'.str_replace('+', '%20', urlencode($info->title));
			$relatedInfo[] = $info;
		}

		$data = array('articles' => $relatedInfo, 'suggestion' => $search->suggestion);
	    return $data; /* the related info */
	}
	
}
?>