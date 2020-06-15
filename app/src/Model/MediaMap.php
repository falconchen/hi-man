<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class MediaMap extends Model
{
	
	protected $table = 'media_map';			
	protected $primaryKey = 'media_id';
	protected $fillable = ['post_id', 'origin_url',];

	
}