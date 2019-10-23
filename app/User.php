<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'email_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

	public function hasRole($role) {
		return $this->role == $role;
	}

	// Upgrade from "pending" to "user" on self-verification
	public function verified() {
		if ($this->hasRole('pending')) {
			$this->role = 'user';
			$this->email_token = null;

			$this->save();
		}
	}

}
