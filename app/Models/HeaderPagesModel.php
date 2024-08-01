<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class HeaderPagesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'header_pages';
    protected $primaryKey = 'uid';
    protected $keyType = 'string';

    protected $fillable = ['name', 'content', 'order', 'slug', 'header_page_uid'];

    public function parentPage()
    {
        return $this->belongsTo(HeaderPagesModel::class, 'header_page_uid')->with('parentPage')->whereNull('header_page_uid');
    }

    public function parentPageName()
    {
        return $this->belongsTo(HeaderPagesModel::class, 'header_page_uid')->whereNull('header_pages.header_page_uid');
    }

}
