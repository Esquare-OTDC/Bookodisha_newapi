<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    protected $table = 'page_contents';
    
    protected $fillable = [
        'type', 'image', 'title', 'content', 'price', 'url', 'section', 'slug', 'vendor_id', 'display_type', 'start_time', 'end_time', 'status', 'create_user', 'update_user'
    ];
}


