<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->increments('id');
            $table->string('site_name');
            $table->string('start_url');
            $table->string('search_url');
            $table->string('search_suggestions_url');
            $table->integer('request_delay');
            $table->integer('cache_expiry')->nullable();
            $table->string('schedule')->nullable();
            $table->enum('status', ['stopped', 'error', 'running', 'complete']);
            $table->string('error_log')->nullable();
            $table->dateTime('last_run')->nullable();
            $table->dateTime('last_completed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites');
    }
}
