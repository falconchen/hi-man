<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;


class MediaMap extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('media_map', function($table)
        {
            $table->increments('media_id');
            $table->string('content_type',50)->default('');
            $table->integer('media_author')->default(0);
            $table->integer('post_id')->default(0);
            $table->text('origin_url')->nullable();
            $table->string('local_path')->default('');
            $table->string('title')->default('');
            $table->text('description')->nullable();
            $table->text('meta_info')->nullable();
            $table->text('cdn')->nullable();
            $table->string('tags')->nullable();
                        
            $table->timestamps();
            
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('media_map');
    }
}