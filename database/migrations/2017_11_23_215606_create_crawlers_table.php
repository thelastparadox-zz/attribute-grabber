<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrawlersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crawlers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('host_ip');
            $table->string('friendly_name')->nullable();
            $table->enum('status', ['pending_auth', 'stopped', 'error', 'running', 'complete']);
            $table->boolean('crawl_enabled')->default(false);
            $table->boolean('authorised')->default(false);
            $table->string('auth_token');
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
        Schema::dropIfExists('crawlers');
    }
}
