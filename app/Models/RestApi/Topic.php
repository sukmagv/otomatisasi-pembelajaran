<?php

namespace App\Models\RestApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'restapi_topics';

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'status',
        'created_by',
        'updated_by',
    ];
}
