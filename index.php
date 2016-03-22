<?php
require 'vendor/autoload.php';
require 'models/Cowaboo.php';
require 'models/Diigo.php';
require 'models/DiigoApi.php';
require 'models/Zotero.php';
require 'models/MediaWiki.php';
require 'models/Ipfs.php';
require 'services/ApiCaller.php';

/*
use Abraham\TwitterOAuth\TwitterOAuth;
const CONSUMER_KEY = 'j9SBN1SCTzWRT2SF770IMsmoS';
const CONSUMER_SECRET = 'jYqIP6U97x0aJunPaICB7lWhLsO66Ra1ZTCq8LFdGsuqZeomhd';
$access_token = '17897401-BSEjlx8rmRJEiZauMbWYs9mZZctwu58OJ62IAKT9r';
$access_token_secret = 'Pf7KnFlznVnJtwpZ7SdfDaXwLD3pEWewaUwXvPX84bPtL';
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);
$content = $connection->get("search/tweets", array("q" => "fallout 4"));
var_dump($content);
die();
/**/
$dfUrl     = "http://stadja.net:81/rest";
$diigoRssUrl  = $dfUrl."/diigoRss";

$db = new mysqli('localhost', 'cowaboo', 'cowaboo1234', 'cowaboo');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} 

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


$ipfsUrl = 'http://ipfs.stadja.net/upload/';
$ipfs = new Ipfs($ipfsUrl);

$cowaboo = new Cowaboo($app, $diigoRealApi, $zotero, $mediaWiki, $ipfs, $db);
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
 * GET: getRelatedBookmarks
 * To get related bookmarks
 */
$app->get('/tags/bookmarks', function () use ($app) {
	$related = array();
	$related = $app->cowaboo->getRelatedBookmarksByService();

	$app->response->setBody(json_encode($related));
})->name('getRelatedBookmarks');

/**
 * GET: getRelatedGroups
 * To get related tags
 */
$app->get('/tags/users', function () use ($app) {
	$related = array();
	$related = $app->cowaboo->getRelatedUsersByService();

	$app->response->setBody(json_encode($related));
})->name('getRelatedGroups');


$app->post('/ipfs', function() use ($app) {
	$hash = $app->cowaboo->postToIpfs();
	$app->response->setBody(json_encode($hash));
})->name('postToIpfs');

$app->get('/ipfs', function() use ($app) {
	$hash = $app->cowaboo->postToIpfs();
	$app->response->setBody(json_encode($hash));
})->name('postToIpfs');

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