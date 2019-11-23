<?php
use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserMeta extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('user_meta', function($table)
        {
            $table->increments('umeta_id');
            $table->integer('user_id');
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
        Capsule::schema()->drop('user_meta');
    }
}