<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Branch;
use App\Models\Unit;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'password',
        'address',
        'phone',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    protected $appends = [
        'is_init_company_needed',
        'is_init_branch_needed',
        'is_init_unit_needed',
    ];

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }
     
    public function getIsCompanyExistsAttribute(){
        return $this->company ? true : false;
    }

    public function getIsBranchExistsAttribute(){
        return count($this->company->branches) > 0 ? true : false;
    }

    public function getIsUnitExistsAttribute(){
        return count($this->company->units) > 0 ? true : false;
    }

    public function getIsInitCompanyNeededAttribute(){
        if ($this->role == 'Owner' && !$this->isCompanyExists) return true;
        return false;
    }

    public function getIsInitBranchNeededAttribute(){
        if ($this->role == 'Owner' && (!$this->isCompanyExists || !$this->isBranchExists)) return true;
        return false;
    }

    public function getIsInitUnitNeededAttribute(){
        if ($this->role == 'Owner' && (!$this->isCompanyExists || !$this->isBranchExists || !$this->isUnitExists)) return true;
        return false;
    }
}
