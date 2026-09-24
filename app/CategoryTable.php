<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryTable extends Model
{
    protected $table = 'category_table';
    
    protected $fillable = [
        'category_name', 'slug', 'parent_id', 'image', 'created_by'
    ];
    
    public function subcategory(){
        return $this->hasMany('App\CategoryTable', 'parent_id');
    } 
}