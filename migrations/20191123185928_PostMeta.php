<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class PostMeta extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('post_meta', function($table)
        {
            $table->increments('meta_id');  
            $table->integer('post_id');          
            $table->string('meta_key');
            $table->text('meta_value');
            $table->timestamps();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('post_meta');
    }
}