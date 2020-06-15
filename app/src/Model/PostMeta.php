<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class PostMeta extends Model
{	
	protected $table = 'post_meta';		
	protected $primaryKey = 'meta_id';
	protected $fillable = ['post_id', 'meta_key','meta_value'];

	public function post()
	{
		return $this->belongsTo('App\Model\Post', 'post_id');
	}
}