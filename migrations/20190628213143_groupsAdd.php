<?php

use App\Model\Group as Groups;
use Phpmig\Migration\Migration;

class GroupsAdd extends Migration
{
    private $data;

    public function init()
    {
        $this->data = array(
            'group_name' => 'UnAuthorize',
            'description' => 'UnAuthorize user',
        );
        return parent::init();
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $array = array(
            $this->data,
        );

        Groups::insert($array);

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        //Groups::delete();
        Groups::where('group_name', $this->datagroup_name)->delete();
    }
}