<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['status', 'group_id','active_code'];//允许批量赋值更新的字段 ref: (https://learnku.com/articles/6096/the-real-meaning-of-laravel-mass-assignment-batch-assignment)
}