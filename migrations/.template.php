<?= "<?php";?>
<?php $classTable=preg_replace_callback('#([A-Z])#',function($match){ return '_'.strtolower($match[1]);},lcfirst($className));?>

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class <?= $className ?> extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('<?= $classTable ?>', function($table)
        {
            $table->timestamps();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('<?= $classTable ?>');
    }
}