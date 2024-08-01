<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class FooterPagesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'footer_pages';
    protected $primaryKey = 'uid';
    protected $keyType = 'string';

    protected $fillable = ['name', 'content', 'order', 'slug', 'footer_page_uid'];

    public function parentPage()
    {
        return $this->belongsTo(FooterPagesModel::class, 'footer_page_uid')->with('parentPage')->whereNull('footer_page_uid');
    }

    public function parentPageName()
    {
        return $this->belongsTo(FooterPagesModel::class, 'footer_page_uid')->whereNull('footer_pages.footer_page_uid');
    }

}
