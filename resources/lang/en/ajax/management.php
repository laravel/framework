<?php

return [
    "roles" => [
        "validation" => [
            # Custom ajax messages for customers.search.validation
        ]
    ],
    "users" => [
    	"validation" => [
    		# Custom ajax messages for customers.registration.validation
    	],
        "status" => [
            "success" => [
                "title" => ":fullname successfully registered!",
                "body" => "Customer :fullname with Mobile :mobile and Email :email had successfully registered."
            ]
        ]
    ]
];
