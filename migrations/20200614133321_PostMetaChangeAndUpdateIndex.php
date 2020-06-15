<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;


class PostMetaChangeAndUpdateIndex extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->table('post_meta', function($table)
        {            
            $table->string('meta_key',50)->change();
            $table->index(['post_id', 'meta_key']);	
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        //Capsule::schema()->drop('media_meta_add_primary_index');
        Capsule::schema()->table('post_meta', function($table)
        {
            $table->string('meta_key')->change();
            $table->dropIndex('post_meta_post_id_meta_key_index');	
        });
        
    }
}