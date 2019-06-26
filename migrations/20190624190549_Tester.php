<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class Tester extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('tester', function($table)
        {
            $table->timestamps();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('tester');
    }
}