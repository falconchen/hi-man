<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class Posts extends Migration
{
    // protected $timestamps = FALSE;
    /**
     * Do the migration
     */
    public function up()
    {
        
         
         /**
         * 
         * Laravel Schema: Null default, timestamp on update
         * https://stackoverflow.com/questions/42782161/laravel-schema-null-default-timestamp-on-update
         */
         
        Capsule::schema()->create('posts', function($table)
        {

            $table->increments('post_id');
            $table->integer('post_author');
//            $table->timestamp('post_date')->default(
//                Capsule::raw('CURRENT_TIMESTAMP')
//            );
            $table->timestamp('post_date')->default('0000-00-00 00:00:00');
            $table->timestamp('post_date_local')->default('0000-00-00 00:00:00');
            $table->longtext('post_content');
            $table->text('post_title');
            $table->string('post_name')->unique();
            $table->text('post_excerpt')->nullable();
            $table->string('post_status')->default('publish');
            $table->string('post_password')->default('');
            $table->string('comment_status')->default('open');
            $table->integer('comment_count')->default(0);
            $table->integer('post_parent')->default(0);

            $table->timestamp('post_modified')->default('0000-00-00 00:00:00');
            $table->string('post_type')->default('post');
            $table->string('post_mime_type')->nullable();;
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('posts');
    }
}