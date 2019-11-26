<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class UserMeta extends Model
{
	protected $primaryKey = 'umeta_id';
	protected $table = 'user_meta';
	protected $fillable = ['user_id', 'meta_key','meta_value'];
}