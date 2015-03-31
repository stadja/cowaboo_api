<?php
require 'vendor/autoload.php';
require 'models/Cowaboo.php';
require 'models/Diigo.php';
require 'models/DiigoApi.php';
require 'models/Zotero.php';
require 'models/MediaWiki.php';
require 'services/ApiCaller.php';

$dfUrl     = "http://stadja.net:81/rest";
$diigoRssUrl  = $dfUrl."/diigoRss";

$apiCaller = new ApiCaller();

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

/*$diigoUrl  = $dfUrl."/diigo";
$diigoRssUrl  = $dfUrl."/diigoRss";
$diigo = new Diigo($diigoUrl, $diigoRssUrl, $apiCaller);*/

$diigoUrl  = 'https://secure.diigo.com/api/v2';
$diigoRssUrl  = $dfUrl."/diigoRss";
$diigoRealApi = new DiigoRealApi($diigoUrl, $diigoRssUrl, $apiCaller);

$zoteroUrl = $dfUrl."/zotero";
$zoteroWebsiteSearchUrl  = 'https://www.zotero.org/searchresults';
$zoteroKey = 'key=KgxKEkhTflxqBqTYlWs0TPBP';

$zotero = new Zotero($zoteroUrl, $zoteroWebsiteSearchUrl, $zoteroKey, $apiCaller);

$mediaWikiUrl = "http://en.wikipedia.org/w/api.php";
$wikipediaUrl = "https://en.wikipedia.org/wiki";
$mediaWiki = new MediaWiki($mediaWikiUrl, $wikipediaUrl, $apiCaller);

$cowaboo = new Cowaboo($app, $diigoRealApi, $zotero, $mediaWiki);
$app->cowaboo = $cowaboo;

/**
 * Get: getBookmarks
 * To get all the bookmarks
 */
$app->get('/bookmarks', function () use ($app) {
 	
	$unmergedBookmarks = $app->cowaboo->getAllServiceBookmarks();
	$mergedBookmarks   = $app->cowaboo->mergeAllBookmarks($unmergedBookmarks);

	$results             = array();
	$results['meta']	 = array();
	$results['merged']   = $mergedBookmarks;
	$results['unmerged'] = $unmergedBookmarks;
	$results['meta'] 	 = $app->cowaboo->generateBookmarkMeta($results['unmerged'], $results['merged']);

	return $app->response->setBody(json_encode($results));
})->name('getBookmarks');

/**
 * Post: createBookmark
 * To create a bookmark
 */
$app->post('/bookmarks', function () use ($app) {

	$result = $app->cowaboo->createABookmarkForEachService();

	$results = array('code' => 200, 'message' => 'ok');
	$app->response->setBody(json_encode($results));

})->name('createBookmark');

/**
 * Get: findGroupId
 * To find a group id
 */
$app->get('/group/id', function () use ($app) {

	$results = $app->cowaboo->findGroupIdByService();
	$app->response->setBody(json_encode($results));

})->name('findGroupId');

/**
 * Post: createBookmark
 * To create a bookmark
 */
$app->get('/postbookmarks', function () use ($app) {

	$result = $app->cowaboo->createABookmarkForEachService();

	$results = array('code' => 200, 'message' => 'ok');
	$app->response->setBody(json_encode($results));

})->name('createBookmark');

/**
 * GET: getTags
 * To get all tags
 */
$app->get('/tags', function () use ($app) {
	$unmergedTags = $app->cowaboo->getAllServiceTags();
	$mergedTags   = $app->cowaboo->mergeAllTags($unmergedTags);

	$app->response->setBody(json_encode($mergedTags));
})->name('getTags');

/**
 * GET: getRelatedTagInformation
 * To get related tags info
 */
$app->get('/tags/infos', function () use ($app) {
	$related = array();
	$related = $app->cowaboo->getTagsRelatedInfoByTagService();

	$app->response->setBody(json_encode($related));
})->name('getRelatedTagInformation');

/**
 * GET: getRelatedGroups
 * To get related tags
 */
$app->get('/tags/groups', function () use ($app) {
	$related = array();
	$related = $app->cowaboo->getRelatedGroupsByService();

	$app->response->setBody(json_encode($related));
})->name('getRelatedGroups');

/**
 * GET: getRelatedGroups
 * To get related tags
 */
$app->get('/tags/users', function () use ($app) {
	$related = array();
	$related = $app->cowaboo->getRelatedUsersByService();

	$app->response->setBody(json_encode($related));
})->name('getRelatedGroups');

/**
 * When you ask a not existing api method
 */
$app->map(':whatever+', function() use ($app) {
	die();
	$routes = array();
	$routes[] = array('id' => 'getBookmarks', 'method' => 'GET', 'pattern' => '/bookmarks', 'query' => array('services', 'diigo_username', 'zotero_users_or_groups', 'zotero_elementId', 'tags'));
	$routes[] = array('id' => 'createBookmark', 'method' => 'POST', 'pattern' => '/bookmarks', 'query' => array('services', 'zotero_users_or_groups', 'zotero_elementId', 'title', 'description', 'url', 'tags'));
	$routes[] = array('id' => 'getTags', 'method' => 'GET', 'pattern' => '/tags', 'query' => array('services', 'diigo_username', 'zotero_users_or_groups', 'zotero_elementId'));
	$routes[] = array('id' => 'getRelatedTagInformation', 'method' => 'GET', 'pattern' => '/tags/related', 'query' => array('group_services', 'tag'));
	$app->response->setStatus(404);
	$app->response->setBody(json_encode($routes));
	$app->halt(404, json_encode($routes));
})->name('man')->via('GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS');

//Generate a URL for the named route
// $url = $app->urlFor('getBookmarks', array('username' => 'stadja'));
// var_dump($url);

$app->run();