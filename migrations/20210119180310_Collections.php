<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class Collections extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('collections', function($table)
        {
            $table->increments('collection_id');
            $table->integer('author')->default(0);
            $table->string('title')->default('');            
            $table->string('slug');
            $table->string('type')->default('article');
            $table->integer('media_id')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('collections');
    }
}