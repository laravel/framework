<?php

app('router')->setCompiledRoutes(
    [
        'compiled' => [
            0 => false,
            1 => [
                '/' => [
                    0 => [
                        0 => [
                            '_route' => 'generated::7CFionvE02fEbBNP',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
            ],
            2 => [
                0 => '{^(?|/users/([^/]++)(*:22))/?$}sDu',
            ],
            3 => [
                22 => [
                    0 => [
                        0 => [
                            '_route' => 'generated::YmZUvOCRFrqhC2sO',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => null,
                        1 => null,
                        2 => null,
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => 0,
                    ],
                ],
            ],
            4 => null,
        ],
        'attributes' => [
            'generated::7CFionvE02fEbBNP' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => '/',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                    ],
                    'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:44:"function () {
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000002f00000000000000000";}}',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'generated::7CFionvE02fEbBNP',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'generated::YmZUvOCRFrqhC2sO' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'users/{user}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                    ],
                    'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:52:"fn (\\Illuminate\\Foundation\\Auth\\User $user) => $user";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000002f20000000000000000";}}',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'generated::YmZUvOCRFrqhC2sO',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
        ],
    ]
);
