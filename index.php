<?php


class Foo
{
    public static function foo()
    {
        echo 'foo';
        return new self;
    }

    public function bar()
    {
        echo 'bar';

        return $this;
    }
    public function baz()
    {
        echo 'baz';

        return $this;
    }

    public function __destruct()
    {
        echo 'starting sleep';
        sleep(1);
        echo 'ending sleep';
    }
}


Foo::foo()->bar()->baz();
echo 'done';
