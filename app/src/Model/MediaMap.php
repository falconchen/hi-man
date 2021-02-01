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
	protected $hidden = ['meta_info','cdn','tags'];

	public function getCoverAttribute()
    {
        return !is_null($this->media)
            ? str_replace('http://', '//', $this->media->origin_url)
            : '';
    }
}