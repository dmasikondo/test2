<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded =[];
    // protected $fillable = [
    //     'email',
    //     'password',
    //     'national_id',
    //     'second_name',
    //     'first_name',
    //     'student_number',
    //     'phone_number',
    //     'slug',
    // ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function results()
    {
        return $this->hasMany(Result::class, 'users_id');
    }


    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

   public function staff()
    {
        return $this->hasMany(Staff::class,'user_id');
    }    

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();    
    }
 

    /**
     * Assign user a role
     */

    public function assignRole($role)
    {
       // $check_if_role_exists = Role::where('name',$role)->get();
            

        return $this->roles()->save(Role::firstOrCreate(['name' =>$role]));
    } 

    /**
      * Check if the user has role of 
    */ 
    public function hasRole($role)
    {
        return  (bool) $this->roles()->where('name',$role)->count();
    }  

    // sentence-capitalise 
     public function getSecondNameAttribute($desc)
     {
         return ucwords($desc);
     }   

     public function getFirstNameAttribute($desc)
     {
         return ucwords($desc);
     } 
     public function getSexAttribute($desc)
     {
         return ucwords($desc);
     } 

     /**
      * Check if the user has results that have been cleared offline 
      * (accounts department sends an excel sheet of cleared user to ITU)
      */

     public function isClearedOffline()
     {
        $cleared_national_id = ClearedStudent::where('national_id_name','LIKE',$this->national_id.'%')->get();
        if($cleared_national_id->count()>0) {
            return true;
        }
        else{
            return false;
        }        
     }

     public function isStudent()
     {
        return (bool) $this->results()->where('users_id', $this->id)->count();
     }

     /**
      * check if user belongs to a given department
      */

     public function belongsTodepartmentOf($dept)
     {
        $department = Department::where('name',$dept)->first();
        //dd($department->id);
        return (bool)($this->staff()->where('user_id', $this->id)->where('department_id',$department->id)->count());
        //dd($value);
        //return (bool) $this->staff()->where('user_id', $this->id)->where('department_id',$department->id)->count();

     /*  $exists= $this->whereHas('staff', fn ($query)=>
            $query->where('department_id',$department->id )
                    ->where('user_id', $this->id)
            )->get();  
       if($exists->count()>0){
        return true;
       }
       else{
        return false;
       }*/
        
         
     }

    public function scopeFilter($query, array $filters)
    {
    /*    $query->when($filters['department'] ?? false, function($query, $department){
            $query->whereHas('results', function($query, $department){
                $query->where('discipline', $department);

            });
        });*/

            $query->when($filters['department'] ?? false, fn($query, $department) =>
            $query->whereHas('results', fn ($query) =>
                $query->where('discipline', $department)
                )
            );
            $query->when($filters['name'] ?? false, fn($query, $name) =>
            $query->has('results')
                ->where('second_name', 'like', '%' . $name . '%')
                ->orWhere('first_name', 'like', '%' . $name . '%')
                
            );
            $query->when($filters['nat_id'] ?? false, fn($query, $nat_id) =>
            $query->has('results')
                ->where('national_id', 'like', '%' . $nat_id . '%')
               
            );            
                       
    }

   
}
