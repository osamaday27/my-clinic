<?php
namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

class LangParityTest extends TestCase
{
    public function test_arabic_and_english_files_have_identical_keys(): void
    {
        $ar = array_keys(Arr::dot(require lang_path('ar/clinic.php')));
        $en = array_keys(Arr::dot(require lang_path('en/clinic.php')));
        sort($ar);
        sort($en);

        $this->assertSame($ar, $en);
    }
}

/*
SYSTEM DESIGN:
Guarantees no missing translation key (Rule 26).

DATA STRUCTURE:
Flattened key set comparison.
*/
