<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackendFileDownloadTokensModel extends Model
{
    use HasFactory;

    protected $table = 'backend_file_download_tokens';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $timestamps = false;
}
