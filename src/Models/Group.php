<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $fillable = ['name', 'created_by'];
    
    // Disable timestamps since our schema doesn't have updated_at
    public $timestamps = false;
    
    // A group has many members (users)
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('joined_at');
    }
    
    // A group has many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    // A group belongs to a creator (user)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}