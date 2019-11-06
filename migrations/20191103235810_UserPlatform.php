<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserPlatform extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('user_platform', function($table)
        {
            $table->integer('user_id');
            $table->string('platform');
            $table->string('account_data');            
            $table->integer('status');
            $table->timestamps();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('user_platform');
    }
}