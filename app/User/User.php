<?php

namespace ChingShop\User;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class UserResource
 *
 * @package ChingShop\User
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\ChingShop\User\Role[] $roles
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\ChingShop\User\User whereUpdatedAt($value)
 */
class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    const FOREIGN_KEY = 'user_id';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return string
     */
    public function email(): string
    {
        return isset($this->email) ? $this->email : '';
    }

    /**
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->roles
        && $this->roles->contains('name', Role::STAFF);
    }

    /**
     * @return string
     */
    public function hashedPassword(): string
    {
        return $this->password;
    }

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            Role::USER_ASSOCIATION_TABLE,
            self::FOREIGN_KEY,
            Role::FOREIGN_KEY
        );
    }
}