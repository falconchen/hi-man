<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class MediaMeta extends Model
{
	
	protected $table = 'media_meta';			
	protected $primaryKey = 'meta_id';
	protected $fillable = ['post_id', 'meta_key','meta_value'];
}