<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' =>
  array (
    0 => false,
    1 =>
    array (
      '/' =>
      array (
        0 =>
        array (
          0 =>
          array (
            '_route' => 'generated::7CFionvE02fEbBNP',
          ),
          1 => NULL,
          2 =>
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 =>
    array (
      0 => '{^(?|/users/([^/]++)(*:22))/?$}sDu',
    ),
    3 =>
    array (
      22 =>
      array (
        0 =>
        array (
          0 =>
          array (
            '_route' => 'generated::YmZUvOCRFrqhC2sO',
          ),
          1 =>
          array (
            0 => 'user',
          ),
          2 =>
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 =>
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' =>
  array (
    'generated::7CFionvE02fEbBNP' =>
    array (
      'methods' =>
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' =>
      array (
        'middleware' =>
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:44:"function () {
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000002f00000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' =>
        array (
        ),
        'as' => 'generated::7CFionvE02fEbBNP',
      ),
      'fallback' => false,
      'defaults' =>
      array (
      ),
      'wheres' =>
      array (
      ),
      'bindingFields' =>
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YmZUvOCRFrqhC2sO' =>
    array (
      'methods' =>
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'users/{user}',
      'action' =>
      array (
        'middleware' =>
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:52:"fn (\\Illuminate\\Foundation\\Auth\\User $user) => $user";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000002f20000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' =>
        array (
        ),
        'as' => 'generated::YmZUvOCRFrqhC2sO',
      ),
      'fallback' => false,
      'defaults' =>
      array (
      ),
      'wheres' =>
      array (
      ),
      'bindingFields' =>
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
