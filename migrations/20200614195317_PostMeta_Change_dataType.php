<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class PostMetaChangeDataType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->table('post', function($table)
        {
            $table->mediumText('meta_value')->change();

        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->table('post_meta', function($table)
        {
            $table->text('meta_value')->change();

        });
    }
}