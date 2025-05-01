<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Enums\Department;
use App\Enums\EvaluationType;
use App\Enums\NominationStatus;
use App\Enums\UserRole;
use App\Enums\LecturerTitle;
use App\Enums\ActionType;

class EnumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Add validation rule macros
        Validator::extend('department', function ($attribute, $value, $parameters, $validator) {
            return Department::isValid($value);
        }, 'The :attribute must be a valid department.');
        
        Validator::extend('evaluation_type', function ($attribute, $value, $parameters, $validator) {
            return EvaluationType::isValid($value);
        }, 'The :attribute must be a valid evaluation type.');
        
        Validator::extend('nomination_status', function ($attribute, $value, $parameters, $validator) {
            return NominationStatus::isValid($value);
        }, 'The :attribute must be a valid nomination status.');
        
        Validator::extend('user_role', function ($attribute, $value, $parameters, $validator) {
            return UserRole::isValid($value);
        }, 'The :attribute must be a valid user role.');
        
        Validator::extend('lecturer_title', function ($attribute, $value, $parameters, $validator) {
            return LecturerTitle::isValid($value);
        }, 'The :attribute must be a valid lecturer title.');
        
        Validator::extend('action_type', function ($attribute, $value, $parameters, $validator) {
            return ActionType::isValid($value);
        }, 'The :attribute must be a valid action type.');
    }
}