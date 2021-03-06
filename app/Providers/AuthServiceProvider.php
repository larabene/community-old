<?php

namespace App\Providers;

use App\Permission;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Model::class => \App\Policies\ModelPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     *
     * @return void
     */
    public function boot(GateContract $gate)
    {
        /*
         * Admin and Owner _always_ have access to the full platform
         */
        $gate->before(function ($user) {
            if ($user->hasRole('owner') || $user->hasRole('admin')) {
                return true;
            }
        });

        $this->registerPolicies($gate);

        // All custom defined Gate methods
        if (\Schema::hasTable('permissions')) {
            $this->entrustPermissions($gate);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getPermissions()
    {
        return Permission::with('roles')->get();
    }

    /**
     * @param GateContract $gate
     */
    public function entrustPermissions(GateContract $gate)
    {
        foreach ($this->getPermissions() as $permission) {
            $gate->define($permission->name, function ($user) use ($permission) {
                return $user->can($permission->name);
            });
        }
    }
}
