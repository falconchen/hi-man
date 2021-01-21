<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\PostMeta;
use App\Model\User;

/**
 * 
 */
class Post extends Model
{
	protected $table = 'posts';
	// 如果在模型里没有声明主键，Eloquent会默认使用id作为主键，执行$obj->save()后，得到的是$obj->id。但是如果你设定的主键不是id，比如是post_id的话，如果不在模型里声明 protected $primaryKey = 'post_id'; 保存后你会得到$obj->id而并不是期望的$obj->post_id
	protected $primaryKey = 'post_id';
	public $timestamps = FALSE;
	protected $fillable = ['post_name'];


	public function getSyncStatus($type = 'osc')
	{

		$item = PostMeta::where(['post_id' => $this->post_id, 'meta_key' => $type . '_sync_result'])->first();
		if (!is_null($item)) {
			$item->meta_value = unserialize($item->meta_value);
		}
		return $item;
	}

	public function getSyncOptions($type = 'osc')
	{

		$metaKey = $type . '_sync_options';
		$oscSyncOptions = $this->metas()->where('meta_key', $metaKey)->first();
		if (!is_null($oscSyncOptions)) {
			return maybe_unserialize($oscSyncOptions->meta_value);
		}
		return null;
	}

	public function updatePostMeta($metaKey,$metaValue) {

		$postMeta = $this->metas()->firstOrNew(['meta_key'=>$metaKey]);		
		$postMeta->meta_value = maybe_serialize($metaValue);
		return $postMeta->save();
	}
	public function getPostMeta($metaKey) {
		
		$postMeta = $this->metas()->where(['meta_key'=>$metaKey])->first();		
		if (!is_null( $postMeta )) {
			return maybe_unserialize($postMeta->meta_value);
		}
		return null;
	}

	public function getOscLink()
	{
		return getOscPostLink($this->post_id, $this->post_author);
	}

	public function metas()
	{
		return $this->hasMany('App\Model\PostMeta', 'post_id');
	}

	    /**
     * Collections，多对多
     */
    public function collections()
    {
		return $this->belongsToMany('App\Model\Collection','post_collection','post_id','collection_id')
		->withPivot('order') //中间表加入额外字段
    	->withTimestamps();//中间表加入时间字段
    }
}
