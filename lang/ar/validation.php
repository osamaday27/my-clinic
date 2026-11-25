<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'حقل :attribute يجب أن يكون نصًا.',
    'email' => 'يجب إدخال بريد إلكتروني صحيح.',
    'integer' => 'حقل :attribute يجب أن يكون رقمًا صحيحًا.',
    'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'in' => 'القيمة المحددة في :attribute غير صالحة.',
    'enum' => 'القيمة المحددة في :attribute غير صالحة.',
    'array' => 'حقل :attribute يجب أن يكون قائمة.',
    'date_format' => 'حقل :attribute لا يطابق الصيغة :format.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخًا لاحقًا أو مساويًا لـ :date.',
    'gt' => ['numeric' => 'حقل :attribute يجب أن يكون أكبر من :value.'],
    'max' => ['file' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.', 'string' => 'حقل :attribute لا يجب أن يتجاوز :max حرفًا.', 'array' => 'حقل :attribute لا يجب أن يتجاوز :max عناصر.', 'numeric' => 'حقل :attribute لا يجب أن يتجاوز :max.'],
    'min' => ['string' => 'حقل :attribute يجب ألا يقل عن :min أحرف.', 'array' => 'حقل :attribute يجب أن يحتوي على :min عنصر على الأقل.', 'numeric' => 'حقل :attribute يجب ألا يقل عن :min.'],
    'between' => ['numeric' => 'قيمة :attribute يجب أن تكون بين :min و :max.'],
    'regex' => 'صيغة :attribute غير صحيحة.',
    'file' => 'حقل :attribute يجب أن يكون ملفًا.',
    'mimes' => 'نوع ملف :attribute غير مسموح (:values).',
    'attributes' => [],
];

/*
SYSTEM DESIGN:
Arabic base validation messages for the rules used by this project (Rule 26).

DATA STRUCTURE:
Associative array rule => message template.
*/
