<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewDashboard(User $user): bool
    {
        return true;
    }

    public function viewCompanies(User $user): bool
    {
        return true;
    }

    public function viewContacts(User $user): bool
    {
        return true;
    }

    public function viewPipeline(User $user): bool
    {
        return true;
    }

    public function viewOpportunities(User $user): bool
    {
        return true;
    }

    public function viewActivities(User $user): bool
    {
        return true;
    }

    public function viewCalls(User $user): bool
    {
        return true;
    }

    public function viewEmails(User $user): bool
    {
        return true;
    }

    public function viewWhatsapp(User $user): bool
    {
        return true;
    }

    public function viewAutomations(User $user): bool
    {
        return $user->role?->canManage() ?? false;
    }

    public function viewTemplates(User $user): bool
    {
        return $user->role?->canManage() ?? false;
    }

    public function viewChannels(User $user): bool
    {
        return $user->role?->canManage() ?? false;
    }

    public function viewReports(User $user): bool
    {
        return $user->role?->canManage() ?? false;
    }

    public function viewSettings(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
