<?php
namespace App\Model;
 
use Illuminate\Database\Eloquent\Model;

/**
* 
*/
class Collection extends Model
{
	
	protected $table = 'collections';
    protected $primaryKey = 'collection_id';
    protected $fillable = ['title','slug','description'];

		    /**
     * Collections
     */
    public function posts()
    {
        return $this->belongsToMany('App\Models\Post','post_collection','collection_id','post_id');
    }
}