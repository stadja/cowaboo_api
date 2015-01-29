<?php
require 'vendor/autoload.php';
require 'models/Cowaboo.php';
require 'models/Diigo.php';
require 'models/Zotero.php';
require 'services/ApiCaller.php';

$dfUrl     = "http://stadja.net:81/rest";
$diigoRssUrl  = $dfUrl."/diigoRss";

$apiCaller = new ApiCaller();

$app = new \Slim\Slim();

$diigoUrl  = $dfUrl."/diigo";
$diigoRssUrl  = $dfUrl."/diigoRss";
$diigo = new Diigo($diigoUrl, $diigoRssUrl, $apiCaller);

$zoteroUrl = $dfUrl."/zotero";
$zoteroKey = 'key=KgxKEkhTflxqBqTYlWs0TPBP';
$zotero = new Zotero($zoteroUrl, $zoteroKey, $apiCaller);

$app->response->headers->set('Content-Type', 'application/json');

$cowaboo = new Cowaboo($app, $diigo, $zotero);
$app->cowaboo = $cowaboo;

/**
 * Get: getBookmarks
 * To get all the bookmarks
 */
$app->get('/bookmarks', function () use ($app) {
 	
	$unmergedBookmarks = $app->cowaboo->getAllServiceBookmarks();
	$mergedBookmarks   = $app->cowaboo->mergeAllBookmarks($unmergedBookmarks);

	$results             = array();
	$results['merged']   = $mergedBookmarks;
	$results['unmerged'] = $unmergedBookmarks;

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
 * Post: createBookmark
 * To create a bookmark
 */
$app->get('/bookmark', function () use ($app) {

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
 * When you ask a not existing api method
 */
$app->map(':whatever+', function() use ($app) {
	$routes = array();
	$routes[] = array('id' => 'getBookmarks', 'method' => 'GET', 'pattern' => '/bookmarks', 'query' => array('services', 'diigo_username', 'zotero_users_or_groups', 'zotero_elementId', 'tags'));
	$routes[] = array('id' => 'createBookmark', 'method' => 'POST', 'pattern' => '/bookmarks', 'query' => array('services', 'zotero_users_or_groups', 'zotero_elementId', 'title', 'description', 'url', 'tags'));
	$routes[] = array('id' => 'getTags', 'method' => 'GET', 'pattern' => '/tags', 'query' => array('services', 'diigo_username', 'zotero_users_or_groups', 'zotero_elementId'));
	$app->response->setStatus(404);
	$app->response->setBody(json_encode($routes));
	$app->halt(404, json_encode($routes));
})->name('man')->via('GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS');

//Generate a URL for the named route
// $url = $app->urlFor('getBookmarks', array('username' => 'stadja'));
// var_dump($url);

$app->run();