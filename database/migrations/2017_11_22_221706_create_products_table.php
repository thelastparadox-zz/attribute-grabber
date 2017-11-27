<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');  
            $table->decimal('price'); 
            $table->string('title');
            $table->string('category_name'); 
            $table->string('breadcrumbs'); 
            $table->string('in_store')->nullable();
            $table->integer('sku_store')->nullable();
            $table->integer('sku_web')->nullable();
            $table->string('image_primary')->nullable();
            $table->integer('model_number')->nullable(); 
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            $table->decimal('review_rating')->nullable();
            $table->integer('number_of_reviews')->nullable();
            $table->string('url')->nullable();
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
        Schema::dropIfExists('products');
    }
}
