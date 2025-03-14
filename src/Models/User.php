<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username'];
    
    // Disable timestamps since our schema doesn't have updated_at
    public $timestamps = false;
    
    // A user can be a member of many groups
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot('joined_at');
    }
    
    // A user can have many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    // A user can create many groups
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }
}