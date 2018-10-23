<?php

return [
    "search" => [
        "validation" => [
            # Custom ajax messages for customers.search.validation
        ]
    ],
    "registration" => [
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
