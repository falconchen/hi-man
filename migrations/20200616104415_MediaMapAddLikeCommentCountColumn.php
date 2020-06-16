<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class MediaMapAddLikeCommentCountColumn extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->table('media_map', function ($table) {
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0); 
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->table('media_map', function ($table) {
            $table->dropColumn('like_count');
            $table->dropColumn('comment_count');
        });
    }
}