<?php
/**
 * add active_code column
 */
use Illuminate\Database\Capsule\Manager as Capsule;
use Phpmig\Migration\Migration;

class UserAddActiveCode extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {

        Capsule::schema()->table('users', function ($table) {
            $table->string('active_code');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->table('users', function ($table) {
            $table->dropColumn('active_code');
        });
    }
}