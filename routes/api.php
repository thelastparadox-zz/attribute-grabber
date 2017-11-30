<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api')->get('/refresh_category/{category_id}', 'CategoriesController@refresh_category');

Route::middleware('api')->get('/get_queue_stats', 'CrawlersController@get_queue_stats');

Route::middleware('api')->post('/load_template', 'JSTemplateController@load_template');

Route::middleware('api')->post('/crawler/history/check', 'CrawlerApiController@history_check');
Route::middleware('api')->post('/crawler/history/add', 'CrawlerApiController@history_add');

Route::middleware('api')->post('/crawler/sites/get', 'CrawlerApiController@sites_info_get');
Route::middleware('api')->post('/crawler/sites/pages/get', 'CrawlerApiController@sites_pages_get');
Route::middleware('api')->post('/crawler/sites/pages/avoid', 'CrawlerApiController@sites_pages_avoid_get');
Route::middleware('api')->post('/crawler/sites/pages/items', 'CrawlerApiController@sites_pages_items_get');

Route::middleware('api')->get('/crawler/authorisation/request', 'CrawlerApiController@authorisation_request');
Route::middleware('api')->get('/crawler/status/update', 'CrawlerApiController@status_update');

Route::middleware('api')->post('/category/save', 'CrawlerApiController@category_save');