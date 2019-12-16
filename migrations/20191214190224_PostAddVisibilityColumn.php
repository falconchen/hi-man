<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;


class PostAddVisibilityColumn extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {

        Capsule::schema()->table('posts', function ($table) {
            $table->string('post_visibility')->default('public'); //public | private | password
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {

        Capsule::schema()->table('posts', function ($table) {
            $table->dropColumn('post_visibility');
        });
    }
}