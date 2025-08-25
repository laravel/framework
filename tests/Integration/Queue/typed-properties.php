<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;

class TypedPropertyTestClass
{
    use SerializesModels;

    public ModelSerializationTestUser $user;

    public ModelSerializationTestUser $uninitializedUser;

    protected int $id;

    private array $names;

    public function __construct(ModelSerializationTestUser $user, int $id, array $names)
    {
        $this->user = $user;
        $this->id = $id;
        $this->names = $names;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }
}

class TypedPropertyCollectionTestClass
{
    use SerializesModels;

    public Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }
}
