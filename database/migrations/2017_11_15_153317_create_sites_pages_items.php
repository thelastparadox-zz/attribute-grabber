<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSitesPagesItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {      
        Schema::create('sites_pages_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_page_id');
            $table->string('item_name');
            $table->enum('item_type', ['text', 'integer', 'list', 'price', 'image', 'html', 'boolean', 'decimal', 'url']);
            $table->string('identifier');
            $table->enum('identifier_type', ['css', 'regex', 'xpath']);
            $table->string('db_column_name')->nullable();
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
        Schema::dropIfExists('sites_pages_items');
    }
}
