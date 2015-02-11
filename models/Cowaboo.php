<?php
class Cowaboo {

	var $app;
	var $diigo;
	var $zotero;

	function __construct($app, $diigo, $zotero, $mediaWiki) {
		$this->app       = $app;
		$this->diigo     = $diigo;
		$this->zotero    = $zotero;
		$this->mediaWiki = $mediaWiki;
    }

    /**
     * Return a string without accent
     *
     * @param string withAccent string with accent
     * @return string string without accent
     */
    private function  _noAccent($withAccent)
    {
        $withoutAccent = str_replace(
			array(
				'à', 'â', 'ä', 'á', 'ã', 'å',
				'î', 'ï', 'ì', 'í', 
				'ô', 'ö', 'ò', 'ó', 'õ', 'ø', 
				'ù', 'û', 'ü', 'ú', 
				'é', 'è', 'ê', 'ë', 
				'ç', 'ÿ', 'ñ'
			),
			array(
				'a', 'a', 'a', 'a', 'a', 'a', 
				'i', 'i', 'i', 'i', 
				'o', 'o', 'o', 'o', 'o', 'o', 
				'u', 'u', 'u', 'u', 
				'e', 'e', 'e', 'e', 
				'c', 'y', 'n'
			),$withAccent
		); 

		return $withoutAccent; /* string without accent */
    }

    /**
     * Get all related tags
     * 
     * @return array related tags by service
     */
    public function  getTagsRelatedInfoByTagService()
    {
        $services = $this->app->request->get('tag_services'); 
	 	if (!$services) {
	 		$services = 'diigo,wikipedia';
	 	}
	 	$services = explode(',', $services);

	 	$tag = $this->getParam('get', 'related tags', 'tag');
		$tag = $this->_noAccent(strtolower($tag));
 		$tagNoSpace = str_replace(' ','%20',$tag); 
 		
	 	$infos = array();
	 	if (in_array('diigo', $services)) {
	 		$relatedTags = $this->diigo->getRelatedTags($tagNoSpace);
	 		if (!sizeof($relatedTags)) {
	 			$relatedTags = $this->diigo->getRelatedTags($tag);
	 		}

	 		$infos['diigo'] = $relatedTags;
	 	}

	 	if (in_array('wikipedia', $services)) {
	 		$infos['wikipedia'] = $this->mediaWiki->getRelatedInfo($tag);
	 	}

        return $infos; /* related tags by service */
    }
    
    public function getAllServiceTags() {
		$info = new stdClass();
	
	 	$services = $this->app->request->get('services'); 
	 	if (!$services) {
	 		$services = 'diigo,zotero';
	 	}
	 	$services = explode(',', $services);

	 	$tags = array();
	 	if (in_array('diigo', $services)) {
	 		$diigoUsername = $this->getParam('get', 'diigo', 'diigo_username');
	 		$diigoTags = $this->diigo->getTags($diigoUsername);
	 		$tags['diigo'] = $diigoTags;
	 	}

	 	if (in_array('zotero', $services)) {
	 		$usersOrGroups = $this->getParam('get', 'zotero service', 'zotero_users_or_groups');
	 		$elementId = $this->getParam('get', 'zotero service', 'zotero_elementId');
	 		$key = $this->getParam('get', 'zotero service', 'zotero_api_key');
	 		$zoteroTags = $this->zotero->getTags($usersOrGroups, $elementId, $key);

			if (isset($zoteroTags->error)) {
				$error = $zoteroTags->error[0];
				$this->sendError('Zotero Error: '.$error->message, $error->code);
			}
	 		$tags['zotero'] = $zoteroTags;

		}

		return $tags;
    }

	public function mergeAllTags($unmergedTagsByService) {
		$tags = array();
    	foreach ($unmergedTagsByService as $serviceName => $unmergedTags) {
    		$tags = $this->mergeTags($unmergedTags, $tags);
    	}
    	return array_values($tags);
	}

	/**
	 * Take 2 sets of tags and merge them
	 *
	 * @param array tagsA first set of tags
	 * @param array tagsB second set of tags
	 * @return array final merged set of tags
	 */
	private function mergeTags($tagsA, $tagsB)
	{
	    $mergedTags = array();

	    foreach ($tagsA as $tag){
	    	$key = md5($tag['title']);
	    	$mergedTags[$key] = $tag;
	    }

	    foreach ($tagsB as $tag){
	    	$key = md5($tag['title']);
	    	if (isset($mergedTags[$key])) {
	    		$mergedTags[$key]['nbrOfElement'] = array_merge($mergedTags[$key]['nbrOfElement'], $tag['nbrOfElement']);
	    		$mergedTags[$key]['links'] = array_merge($mergedTags[$key]['links'], $tag['links']);
	    	} else {
	    		$mergedTags[$key] = $tag;
	    	}
	    }
	    return array_values($mergedTags); /* final merged set of tags */
	}

	/**
	 * Generate meta informations for the bookmarks
	 *
	 * @param array unmerged array of bookmarks by services
	 * @param array merged array of merged bookmarks
	 * @return array meta informations
	 */
	public function  generateBookmarkMeta($unmerged, $merged)
	{
	    $meta = array();
	    $meta['merged'] = array();
	    $meta['unmerged'] = array();

	    foreach ($unmerged as $service => $bookmarks) {
	       	$meta['unmerged'][$service] = array();
	       	$meta['unmerged'][$service]['count'] = sizeof($bookmarks);
	       	$meta['unmerged'][$service]['tags'] = array();

	       	foreach ($bookmarks as $bookmark) {
	       		$tags = array();
		       	switch ($service) {
		       		case 'diigo':
		       			$tagString = $bookmark->tags;
		       			$tagArray = explode(',', $tagString);
		       			$tags = array_filter($tagArray, function($value) {
		       				return $value != 'no_tag';
		       			});
		       			break;

		       		case 'zotero':
	       				$tagArray = $bookmark->data->tags;
	       				foreach ($tagArray as $tagData) {
	       					$tags[] = $tagData->tag;
	       				}
		       			break;
		       		
		       		default:
		       			break;
		       	}

		       	foreach ($tags as $tag) {
		       		if (!isset($meta['unmerged'][$service]['tags'][$tag])) {
		       			$meta['unmerged'][$service]['tags'][$tag] = new stdClass();
		       			$meta['unmerged'][$service]['tags'][$tag]->name = $tag;
		       			$meta['unmerged'][$service]['tags'][$tag]->count = 0;
		       		}
	       			$meta['unmerged'][$service]['tags'][$tag]->count++;
		       	}
	       	}
	    }

    	$meta['merged']['count'] = sizeof($merged);
       	$meta['merged']['tags'] = array();

       	foreach ($merged as $bookmark) {
       		foreach ($bookmark->tags as $tag) {
       			$tag = strtolower($tag);
	       		if (!isset($meta['merged']['tags'][$tag])) {
	       			$meta['merged']['tags'][$tag] = new stdClass();
	       			$meta['merged']['tags'][$tag]->name = $tag;
	       			$meta['merged']['tags'][$tag]->count = 0;
	       		}
       			$meta['merged']['tags'][$tag]->count++;
	       	}
       	}
       	
	    return $meta; /* meta informations */
	}
	
    public function getAllServiceBookmarks() {
    	$tags = $this->app->request->get('tags'); 

	 	$services = $this->app->request->get('services'); 
	 	if (!$services) {
	 		$services = 'diigo,zotero';
	 	}
	 	$services = explode(',', $services);

	 	$bookmarks = array();
	 	$results = array();
	 	if (in_array('diigo', $services)) {
	 		$diigoUsername = $this->getParam('get', 'diigo', 'diigo_username');
	 		$diigoAccessKey = $this->getParam('get', 'diigo', 'diigo_access_key');
			$diigoBookmarks = $this->diigo->getBookmarks($diigoUsername, $diigoAccessKey, $tags);
			if (isset($diigoBookmarks->error)) {
				$error = $diigoBookmarks->error[0];
				$this->sendError('Diigo Error: '.$error->message, $error->code);
			}
				
			if (is_string($diigoBookmarks)) {
				$this->sendError('Diigo Error: '.$diigoBookmarks, 400);
			}
			$results['diigo'] = $diigoBookmarks;
	 	}

	 	if (in_array('zotero', $services)) {
	 		$usersOrGroups = $this->getParam('get', 'zotero', 'zotero_users_or_groups');
	 		$elementId = $this->getParam('get', 'zotero', 'zotero_elementId');
	 		$key = $this->getParam('get', 'zotero service', 'zotero_api_key');
			$zoteroBookmarks = $this->zotero->getBookmarks($usersOrGroups, $elementId, $key, $tags);

			if (isset($zoteroBookmarks->error)) {
				$error = $zoteroBookmarks->error[0];
				$this->sendError('Zotero Error: '.$error->message, $error->code);
			}

			if (!$zoteroBookmarks) {
				$zoteroBookmarks = array();
			}
			$results['zotero'] = $zoteroBookmarks;
		}

		return $results;
    }

    public function mergeAllBookmarks($unmergedBookmarksByService) {
    	$bookmarks = array();
    	foreach ($unmergedBookmarksByService as $serviceName => $unmergedBookmarks) {
    		switch ($serviceName) {
    			case 'diigo':
    				$unmergedBookmarks = $this->diigo->standardizeBookmarks($unmergedBookmarks);
    				break;

    			case 'zotero':
    				$unmergedBookmarks = $this->zotero->standardizeBookmarks($unmergedBookmarks);
    				break;
    			
    			default:
    				# cod...
    				break;
    		}

    		$bookmarks = $this->mergeBookmarks($unmergedBookmarks, $bookmarks);
    	}
    	return array_values($bookmarks);
    }

	/**
	 * Merge to bookmark arrays
	 * @param  array $setA bookmark arrays
	 * @param  array $setB bookmark arrays
	 * @return array       merged bookmark array
	 */
	public function mergeBookmarks($setA, $setB, $test = false) {
		$merged = array_merge($setA, $setB);
		usort($merged, function($a, $b){
			return $a->updated_at <= $b->updated_at;
		});

		for ($i=(sizeof($merged) - 1); $i >= 0; $i--) { 
			$bookmark = $merged[$i];
			$url = $this->getNormalizedUrl($bookmark->url);
			for ($j=$i - 1; $j >= 0; $j--) { 

				$testedBookmark = $merged[$j];
				$testedUrl = $this->getNormalizedUrl($testedBookmark->url);

				if($url == $testedUrl) {
					$testedBookmark->services = array_unique(array_merge($testedBookmark->services, $bookmark->services));
					$testedBookmark->notes = array_merge($testedBookmark->notes, $bookmark->notes);
					$testedBookmark->tags = array_unique(array_map("trim", array_merge($testedBookmark->tags, $bookmark->tags)));

					$users = array_merge($testedBookmark->users, $bookmark->users);
					$users = array_filter($users, function($user)
					{
					    static $idList = array();
					    $userId = $user->service.$user->id;
					    
					    if(in_array($userId,$idList)) {
					        return false;
					    }
					    $idList []= $userId;
					    return true;
					});
					$testedBookmark->users   = $users;

					$description = $bookmark->description;
					if ($bookmark->description != $testedBookmark->description) {
						if($testedBookmark->description) {
							if ($bookmark->description) {
								$description = $bookmark->description.' / ';
							}
							$description .= $testedBookmark->description;
						}
					}
					$testedBookmark->description = $description;

					$merged[$j] = $testedBookmark;

					unset($merged[$i]);
					break;
				}
			}
		}
		return $merged;
	}

	public function createABookmarkForEachService() {
		$info = new stdClass();
	
		$info->tags = $this->app->request->get('tags');
		if ($info->tags) {
			$tags = explode(',', $info->tags);
			$info->tags = "";
			foreach ($tags as $key => $tag) {
				$tag = trim($tag);
				if ($key > 1) {
					$info->tags .= ',';
				}
				$info->tags .= $tag;
			}
		}

		$info->description = $this->app->request->get('description');
		$info->title = $this->getParam('get', 'bookmark creation', 'title');
		$info->url = $this->getParam('get', 'bookmark creation', 'url');

	 	$services = $this->app->request->get('services'); 
	 	if (!$services) {
	 		$services = 'diigo,zotero';
	 	}
	 	$services = explode(',', $services);

	 	$bookmarks = array();
	 	$results = array();

	 	if (in_array('diigo', $services)) {
	 		$diigoAccessKey = $this->getParam('get', 'diigo', 'diigo_access_key');
	 		$diigoBookmark = $this->diigo->createBookmark($diigoAccessKey, $info);
	 	}

	 	if (in_array('zotero', $services)) {
	 		$usersOrGroups = $this->getParam('get', 'zotero service', 'zotero_users_or_groups');
	 		$elementId = $this->getParam('get', 'zotero service', 'zotero_elementId');
	 		$apiKey = $this->getParam('get', 'zotero service', 'zotero_api_key');
	 		$zoteroBookmark = $this->zotero->createBookmark($usersOrGroups, $elementId, $apiKey, $info);
	 		if (isset($zoteroBookmark->error)) {
				$error = $zoteroBookmark->error[0];
				$this->sendError('Zotero Error: '.$error->message, $error->code);
			}
		}

		return true;
	}

	/**
	 * Take an url and normalized it
	 * @param  string $url the url
	 * @return string      the normalized url
	 */
	private function getNormalizedUrl($url) {
		$url = str_replace("www.", "", $url);
		$url = str_replace("https", "", $url);
		$url = str_replace("http", "", $url);
		$url = str_replace("/", "", $url);
		$url = str_replace(":", "", $url);
		return $url;
	}

	/**
	 * test if a param exist in the request and return it if it does
	 * @param  string $method      get|post|put|patch|delete|options
	 * @param  string $serviceName the service whith which the param is associated
	 * @param  string $paramName   the name of the param
	 * @return mixed               the param value
	 */
	private function getParam($method, $serviceName, $paramName) {
		global $app;
		$param  = $this->app->request->$method($paramName); 
		if (!$param) {
			return $this->sendError("$serviceName asked, but no '$paramName' provided");
		}
		return $param;
	}

	/**
	 * throw an API error
	 * @param  string $msg the body (better if json encoded) of the error
	 * @return void      
	 */
	private function sendError($msg, $code = 400) {
		global $app;
		$this->app->response->setStatus($code);
		$exception = array('code' => $code, 'message' => $msg);
		$this->app->response->setBody(json_encode($exception));
		$this->app->halt($code, json_encode($msg));
	}

}
?>