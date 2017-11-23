<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSitesPages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->string('page_name');
            $table->enum('page_type', ['product', 'category', 'search']);
            $table->string('url_pattern');
            $table->string('example_url');      
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites_pages');
    }
}
