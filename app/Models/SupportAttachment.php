<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportAttachment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function supportMessage()
    {
        return $this->belongsTo('App\Models\SupportMessage','support_message_id');
    }
}
