<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Scopes\FilterScope;
use App\Models\Scopes\FiltersScope;
use App\Models\Scopes\SelectScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Override;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nombre', 'email', 'password','fecha_inicio','fecha_fin','estado','area_id','discord_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasRoles;

    protected $table = 'usuarios';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    public function scopeFilters($query)
    {
        return (new FiltersScope)->apply($query, $this);
    }

    public function scopeSelectColumns($query)
    {
        return (new SelectScope)->apply($query, $this);
    }

    public function scopeGetOrPaginate($query,int $default=5)
    {

        $perPage = request('perPage',$default);
        return $query->paginate($perPage);


    }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean'
        ];
    }

    public array $allowedFilters=['name'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }


}
