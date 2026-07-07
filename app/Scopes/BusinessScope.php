<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            if ($user->business_id && !$user->hasRole('Superadmin')) {
                $builder->where($model->getTable() . '.business_id', $user->business_id);
            }
        }
    }
}
