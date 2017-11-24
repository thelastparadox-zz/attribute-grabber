<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('upload');
});

Route::get('sites', 'SitesController@sites_home');

Route::get('crawlers', 'CrawlersController@crawlers_home');

Route::post('add_categories', 'CategoriesController@process_upload');

Route::get('categories', 'CategoriesController@view_categories');

Route::get('grab_categories', 'CategoriesController@grab_categories');

Route::get('test_crawler', 'CrawlersController@test_crawler');

Route::get('crawler_test_specific_page', 'CrawlersController@crawler_test_specific_page');

Route::get('process_crawler_queue', 'CrawlersController@process_crawler_queue');

Route::get('cron', 'CronController@cron_main');