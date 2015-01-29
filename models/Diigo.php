<?php
class Diigo {
	var $url = '';
	var $urlRss = '';
	var $api = '';

	function __construct($url, $urlRss, $api) {
		$this->url = $url;
		$this->urlRss = $urlRss;
		$this->api = $api;
    }

	/**
	 * Create a diigo bookmark
	 *
	 * @param stdClass info the information of the bookmark to be saved
	 * @return string the ID of the newly created bookmark
	 */
	public function  createBookmark($accessKey, $info)
	{
		$methodUrl = $this->url.'/bookmarks'; 

		$diigoBookmark = new stdClass();
		$diigoBookmark->title = $info->title;
		$diigoBookmark->url = $info->url;
		$diigoBookmark->desc = $info->description;
		$diigoBookmark->tags = $info->tags;
		$diigoBookmark->shared = 'yes';

		$results = $this->api->call('post', $methodUrl, $diigoBookmark, array('Authorization' => $accessKey));

	    return $results; /* the ID of the newly created bookmark */
	}

	/**
	 * return all the diigo bookmarks of the user
	 * @param  string $diigoUsername the diigo user
	 * @return array        		 diigo bookmarks
	 */	
    public function getBookmarks($diigoUsername, $diigoAccessKey ,$tags = false) {
		$methodUrl            = $this->url.'/bookmarks?user='.$diigoUsername; 

		if ($tags) {
			$tags=explode(',', $tags);
			$methodUrl .= "&tags=";
			$i = 0;
			foreach ($tags as $tag) {
				if ($i) {
					$methodUrl .= ',';
				}
				$methodUrl .= '"'.urlencode($tag).'"';
				$i++;

			}
		}
		$diigoBookmarks = $this->api->call('get', $methodUrl, false, array('Authorization' => $diigoAccessKey));
		$diigoBookmarks = json_decode($diigoBookmarks);

		return $diigoBookmarks;
	}

	/**
	 * take diigo bookmarks and return standard bookmarks
	 * @param  array $diigoBookmarks diigo bookmarks
	 * @return array                 standard bookmarks
	 */
	public function standardizeBookmarks($diigoBookmarks) {
		$bookmarks = array();
		foreach ($diigoBookmarks as $diigoBookmark) {
			$config             = new stdClass();
			$config->services    = array('diigo');
			$config->users   = array();

			$user = new stdClass();
			$user->service = 'diigo';
			$user->name = $diigoBookmark->user;
			$user->id = $diigoBookmark->user;
			$user->type = 'user';
			$config->users[] = $user;

			$config->title      	= $diigoBookmark->title;
			$config->url        	= $diigoBookmark->url;
			$config->description	= $diigoBookmark->desc;

			$tags = explode(',', $diigoBookmark->tags);
			$tags = array_filter($tags, function($var) {
				return $var != "no_tag";
			});
			$config->tags = $tags;

			$created_at = DateTime::createFromFormat('Y/m/d H:i:s O', $diigoBookmark->created_at);
			$config->created_at = $created_at->format('c');
			$updated_at = DateTime::createFromFormat('Y/m/d H:i:s O', $diigoBookmark->updated_at);
			$config->updated_at = $updated_at->format('c');

			$notes = array();
			foreach ($diigoBookmark->annotations as $note) {
				$note->userid = $diigoBookmark->user;
				$note->service = 'diigo';
				$created_at = DateTime::createFromFormat('Y/m/d H:i:s O', $note->created_at);
				$note->created_at = $created_at->format('c');
				$notes[] = $note;
			}
			$config->notes      = $notes;

			$bookmarks[]        = $config;
		}
		return $bookmarks;
	}

	/**
	 * return all the diigo bookmarks of the user
	 * @param  string $diigoUsername the diigo user
	 * @return array        		 diigo tags
	 */	
	public function getTags($diigoUsername)
	{
		$methodUrl = $this->urlRss."/user_tag/$diigoUsername"; 

		$tags = $this->api->call('get', $methodUrl);

		try {
			$parser = new SimpleXMLElement($tags);
		} catch(Exception $e) {
			return array();
		}

		$tags = array();
		if ($parser && isset($parser->channel) && isset($parser->channel->item)) {
			foreach($parser->channel->item as $key => $tag) {
				$nbrOfElement = (string)$tag->description;
				$number = array();
				preg_match_all('/\d+/', $nbrOfElement, $number);
				
				if (isset($number[0]) && isset($number[0][0])) {
	        		$nbrOfElement = $number[0][0];
				}
				if ((int) $nbrOfElement && ((string)$tag->title != 'no_tag')) {
					$tags[] = array('title' => (string)$tag->title
						, 'nbrOfElement' => array(array('service' => 'diigo', 'nbr' => (int)$nbrOfElement))
						,'links' => array(array('service' => 'diigo', 'link' => (string)$tag->link))
					);
				}
			}
		}
	    return $tags; /* the diigo user tags */
	}
}
?>