<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionSubmissionEmailsModel extends Model
{
    use HasFactory;
    protected $table = 'suggestion_submission_emails';
    protected $primaryKey = 'uid';
    protected $fillable = ['uid', 'email'];

    protected $keyType = 'string';
}
