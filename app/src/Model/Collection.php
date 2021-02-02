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
    protected $fillable = ['title', 'slug', 'description','media_id'];

    



    //protected $hidden = ['media'];
    
    /**
     * Collections
     */
    public function posts()
    {
        //用法
        // Collection::find(1)->posts()->attach(1024,['order'=>8848]); //加入 post_collection 表： post_id=1024 ，collection_id=1, order=8848
        // Collection::find(1)->posts()->get();
        return $this->belongsToMany(Post::class, 'post_collection', 'collection_id', 'post_id')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function media()
    {
        //用法：Collection::find(1)->media->origin_url;
        return $this->belongsTo(MediaMap::class, 'media_id');
    }

    public function owner(){
        return $this->belongsTo(User::class, 'author');
    }

    public function getCoverAttribute()
    {
        return !is_null($this->media)
            ? str_replace('http://', '//', $this->media->origin_url)
            : '';
    }

    
    // public function getAuthorNameAtrribute(){

    // }
}
