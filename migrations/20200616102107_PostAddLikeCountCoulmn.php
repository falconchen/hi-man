<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;


class PostAddLikeCountCoulmn extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->table('posts', function ($table) {
            $table->integer('like_count')->default(0); 
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->table('posts', function ($table) {
            $table->dropColumn('like_count');
        });
    }
}