<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class UserPermission extends Model
{
	
	protected $table = 'users_permission';
	protected $guarded = []; //设置为空时所有字段都可以批量赋值 https://learnku.com/docs/laravel/5.5/eloquent/1332#mass-assignment
}