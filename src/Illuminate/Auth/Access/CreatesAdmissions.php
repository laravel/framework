<?php

namespace Illuminate\Auth\Access;

trait CreatesAdmissions
{
    /**
     * Create a denied addmission.
     *
     * @param  string  $reason
     * @return \Illuminate\Auth\Access\Admission
     */
    protected function deny($reason = 'This action is unauthorized.')
    {
        return Admission::deny($reason);
    }

    /**
     * Create an allowed admission.
     *
     * @param  string|null  $reason
     * @return \Illuminate\Auth\Access\Admission
     */
    protected function allow($reason = null)
    {
        return Admission::allow($reason);
    }
}
