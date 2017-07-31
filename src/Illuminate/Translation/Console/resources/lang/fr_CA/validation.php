<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'Le champ :attribute doit être accepté.',
    'active_url'           => 'Le champ :attribute n’est pas une URL valide.',
    'after'                => 'Le champ :attribute doit être une date après le :date.',
    'after_or_equal'       => 'Le champ :attribute doit être une date égale ou suivante au :date.',
    'alpha'                => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash'           => 'Le champ :attribute ne peut contenir que des lettres, numéros et traits d’union.',
    'alpha_num'            => 'Le champ :attribute ne peut contenir que des lettres et des numéros.',
    'array'                => 'Le champ :attribute doit être un tableau.',
    'before'               => 'Le champ :attribute doit être une date avant le :date.',
    'before_or_equal'      => 'Le champ :attribute doit être une date égale ou précédant le :date.',
    'between'              => [
        'numeric' => 'Le champ :attribute doit se situer entre :min et :max.',
        'file'    => 'Le champ :attribute doit peser entre :min and :max kilobits.',
        'string'  => 'Le champ :attribute ne peut contenir qu’entre :min et :max caractères.',
        'array'   => 'Le champ :attribute ne peut contenir qu’entre :min et :max items.',
    ],
    'boolean'              => 'Le champ :attribute doit être vrai ou faux..',
    'confirmed'            => 'La confirmation du champ :attribute n’est pas la identique.',
    'date'                 => 'Le champ :attribute n’est pas une date valide.',
    'date_format'          => 'Le champ :attribute ne correspond pas au format :format.',
    'different'            => 'Le champ :attribute et le champ :other doivent être différents.',
    'digits'               => 'Le champ :attribute doit être de :digits chiffres.',
    'digits_between'       => 'Le champ :attribute doit être entre :min et :max chiffres.',
    'dimensions'           => 'L’image du champ :attribute n’a pas les bonnes dimensions.',
    'distinct'             => 'Le champ :attribute à une valeur dupliquée.',
    'email'                => 'Le champ :attribute doit être une adresse courriel valide.',
    'exists'               => 'Le champ :attribute sélectionné est invalide.',
    'file'                 => 'Le champ :attribute doit être un fichier.',
    'filled'               => 'Le champ :attribute est requis.',
    'image'                => 'Le champ :attribute doit être une image.',
    'in'                   => 'Le champ :attribute sélectionné est invalide.',
    'in_array'             => 'La valeur du champ :attribute ne doit pas exister dans :other.',
    'integer'              => 'Le champ :attribute doit être un nombre entier.',
    'ip'                   => 'Le champ :attribute doit être une adresse IP valide.',
    'json'                 => 'Le champ :attribute doit être une chaîne JSON valide.',
    'max'                  => [
        'numeric' => 'Le champ :attribute ne doit pas être plus grand que :max.',
        'file'    => 'Le champ :attribute ne doit pas être plus grand que :max kilobits.',
        'string'  => 'Le champ :attribute ne doit pas être plus grand que :max caractères.',
        'array'   => 'Le champ :attribute ne peut pas contenir plus que :max items.',
    ],
    'mimes'                => 'Le champ :attribute doit être un fichier du type: :values.',
    'mimetypes'            => 'Le champ :attribute doit être un fichier du type: :values.',
    'min'                  => [
        'numeric' => 'Le champ :attribute doit être plus grand que :min.',
        'file'    => 'Le champ :attribute doit être d’au moins :min kilobits.',
        'string'  => 'Le champ :attribute doit être d’au moins :min caractères.',
        'array'   => 'Le champ :attribute doit avoir au moins :min items.',
    ],
    'not_in'               => 'La valeur sélectionné dans le champ :attribute est invalide.',
    'numeric'              => 'Le champ :attribute doit être un nombre.',
    'present'              => 'Le champ :attribute doit être présent.',
    'regex'                => 'Le format du champ :attribute est invalide.',
    'required'             => 'Le champ :attribute est requis.',
    'required_if'          => 'Le champ :attribute est requis lorsque :other est :value.',
    'required_unless'      => 'Le champ :attribute est requis sauf si :other est dans :values.',
    'required_with'        => 'Le champ :attribute est requis quand :values sont présents.',
    'required_with_all'    => 'Le champ :attribute est requis quand :values est présent.',
    'required_without'     => 'Le champ :attribute est requis quand :values n’est pas présent.',
    'required_without_all' => 'Le champ :attribute est requis quand aucun de :values sont présents.',
    'same'                 => 'Le champ :attribute et :other doivent être identiques.',
    'size'                 => [
        'numeric' => 'Le champ :attribute doit être de :size.',
        'file'    => 'Le champ :attribute doit être de :size kilobits.',
        'string'  => 'Le champ :attribute doit être de :size caractères.',
        'array'   => 'Le champ :attribute doit contenir :size items.',
    ],
    'string'               => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone'             => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique'               => 'Le champ :attribute à déjà été sélectionné.',
    'uploaded'             => 'Le téléversement du champ :attribute à échoué.',
    'url'                  => 'Le format du champ :attribute est invalide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
