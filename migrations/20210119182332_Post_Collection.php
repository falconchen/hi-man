<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class PostCollection extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('post_collection', function($table)
        {
                        
            
            $table->integer('post_id')->default(0);
            $table->integer('collection_id')->default(0);
            $table->integer('order')->default(0);                        
            $table->timestamps();

        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('post_collection');
    }
}