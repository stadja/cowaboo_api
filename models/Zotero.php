<?php
class Zotero {
	var $url = '';
	var $api = '';

	function __construct($url, $key, $api) {
		$this->url = $url;
		$this->key = $key;
		$this->api = $api;
    }

	/**
	 * Create a diigo bookmark
	 *
	 * @param  string $usersOrGroups groups||user
	 * @param  string $elementId     the zotero group or user id
	 * @param stdClass info the information of the bookmark to be saved
	 * @return void
	 */
	public function createBookmark($usersOrGroups, $elementId, $apiKey, $info) {
		$methodUrl = $this->url.'/'.$usersOrGroups.'/'.$elementId.'/items?key='.$apiKey; 

		$tags = explode(',', $info->tags);

		$stringTags = "";
		$i = 0;
		foreach ($tags as $tag) {
			if ($i) {
				$stringTags .= ',';
			}
			$stringTags .= "{\"tag\": \"$tag\"}";
			$i++;
		}

		$webpage = "[{"
		."  \"itemType\": \"webpage\","
		."  \"title\": \"$info->title\","
		."  \"creators\": ["
		."    {"
		."      \"creatorType\": \"cowaboo\","
		."      \"firstName\": \"\","
		."      \"lastName\": \"\""
		."    }"
		."  ],"
		."  \"abstractNote\": \"$info->description\","
		."  \"websiteTitle\": \"\","
		."  \"websiteType\": \"\","
		."  \"date\": \"\","
		."  \"shortTitle\": \"\","
		."  \"url\": \"$info->url\","
		."  \"accessDate\": \"\","
		."  \"language\": \"\","
		."  \"rights\": \"\","
		."  \"extra\": \"\","
		."  \"tags\": [$stringTags],"
		."  \"collections\": [],"
		."  \"relations\": []"
		."}]";

		$results = $this->api->call('post', $methodUrl, $webpage);
	    return $results; /* the ID of the newly created bookmark */
	}

	/**
	 * return all the zotero bookmarks of the user or the group
	 * @param  string $usersOrGroups groups||user
	 * @param  string $elementId     the zotero group or user id
	 * @param  string $tags          the comma seperated list of tags to filter the query
	 * @return array        		 zoteroo bookmarks
	 */	
	public function getBookmarks($usersOrGroups, $elementId, $zoteroKey, $tags = false) {
		$methodUrl             = $this->url.'/'.$usersOrGroups.'/'.$elementId.'/items?key='.$zoteroKey; 
		if ($tags) {
			$tags = explode(',', $tags);
			foreach ($tags as $tag) {
				$methodUrl .= "&tag=".urlencode($tag);
			}
		}
		$zoteroBookmarks = $this->api->call('get', $methodUrl);
		$zoteroBookmarks = json_decode($zoteroBookmarks);
		return $zoteroBookmarks;
	}

	/**
	 * take zotero bookmarks and return standard bookmarks
	 * @param  array $zoteroBookmarks zotero bookmarks
	 * @return array                  standard bookmarks
	 */
	public function standardizeBookmarks($zoteroBookmarks) {
		$tempBookmarks = array();
		$attachments = array();

		foreach ($zoteroBookmarks as $zoteroBookmark) {
			$config = new stdClass();
			$config->services = array('zotero');
			$config->users   = array();

			$user = new stdClass();
			$user->service = 'zotero';
			$user->name = $zoteroBookmark->library->name;
			$user->id = $zoteroBookmark->library->id;
			$user->type = $zoteroBookmark->library->type;

			$config->users[] = $user;

			if (!isset($zoteroBookmark->data->parentItem) || ($zoteroBookmark->data->itemType == 'webpage')) {
				$config->url 			= $zoteroBookmark->data->url;
				$config->title 			= $zoteroBookmark->data->title;
				$config->description	= $zoteroBookmark->data->abstractNote;

				$tags = array();
				foreach ($zoteroBookmark->data->tags as $tagObject) {
					$tags[] = $tagObject->tag;
				}
				$config->tags = $tags;

				$config->created_at = $zoteroBookmark->data->dateAdded;
				$config->updated_at = $zoteroBookmark->data->dateModified;
				$config->updated_at = $zoteroBookmark->data->dateModified;

				$note = $zoteroBookmark->data->abstractNote;
				$config->notes = array();
				if ($note) {
					$noteObject = new stdClass();

					$noteObject->service    = 'zotero';
					$noteObject->content    = $note;
					$noteObject->userid         = $user->id;
					$noteObject->user       = $user->name;
					$noteObject->created_at = $config->updated_at;
					$config->notes = array($noteObject);
				}

				$tempBookmarks[$zoteroBookmark->key] = $config;
			} else {
				if (!isset($zoteroBookmark->data->parentItem)) {

				}
				$parent = $zoteroBookmark->data->parentItem;
				if (!isset($attachments[$parent])) {
					$attachments[$parent] = array();
				}
				$attachments[$parent][] = $zoteroBookmark;
			}
		}

		foreach ($attachments as $parentItemKey => $attachments) {
			if (isset($tempBookmarks[$parentItemKey])) {
				foreach ($attachments as $attachment) {
					if ($attachment->data->itemType == "attachment") {
						if (isset($attachment->links->enclosure)) {
							$note = $attachment->links->enclosure->href.'?'.$this->key;
						}
					} else {
						$note = $attachment->data->note;
					}
					if (!$note) {
						continue;
					}
					$noteObject = new stdClass();

					$noteObject->service    = 'zotero';
					$noteObject->content    = $note;
					$noteObject->userid         = $attachment->library->id;
					$noteObject->user      = $attachment->library->name;
					$noteObject->created_at = $attachment->data->dateModified;

					$parentItem = $tempBookmarks[$parentItemKey];
					$parentItem->notes[] = $noteObject;
					$tempBookmarks[$parentItemKey] = $parentItem;
				}
			}
		}

		$bookmarks = array();
		foreach ($tempBookmarks as $config) {
			$bookmarks[] = $config;
		}
		return $bookmarks;
	}

	/**
	 * return all the zotero bookmarks of the user or the group
	 * @param  string $usersOrGroups groups||user
	 * @param  string $elementId     the zotero group or user id
	 * @return array        		 zoteroo tags
	 */	
	function getTags($usersOrGroups, $elementId, $zoteroKey) {
		$methodUrl             = $this->url.'/'.$usersOrGroups.'/'.$elementId.'/tags?key='.$zoteroKey; 
		$zoteroTags = $this->api->call('get', $methodUrl);
		$zoteroTags = json_decode($zoteroTags);
		if (isset($zoteroTags->error)) {
			return $zoteroTags;
		}
		$tags = array();
		if ($zoteroTags) {
			foreach ($zoteroTags as $key => $tag) {
				$nbrOfElement = $tag->meta->numItems;
				if ($nbrOfElement) {
					$tags[] = array('title' => (string)$tag->tag
						, 'nbrOfElement' => array(array('service' => 'zotero', 'nbr' => $tag->meta->numItems))
						,'links' => array(array('service' => 'zotero', 'link' => (string)$tag->links->self->href))
					);
				}
			}
		}
		return $tags;
	}

}
?>