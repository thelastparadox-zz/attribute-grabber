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

Route::middleware('api')->get('/crawler-queue/get', 'CrawlerApiController@queue_get');