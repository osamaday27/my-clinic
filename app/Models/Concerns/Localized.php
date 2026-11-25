<?php
namespace App\Models\Concerns;

trait Localized
{
    /** Pick *_ar / *_en according to the active locale with graceful fallback. */
    protected function localized(string $base): ?string
    {
        $ar = $this->getAttribute($base.'_ar');
        $en = $this->getAttribute($base.'_en');

        return app()->getLocale() === 'en' ? ($en ?: $ar) : ($ar ?: $en);
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }
}

/*
SYSTEM DESIGN:
Bilingual data (Rule 21): every translatable entity stores *_ar and *_en columns and exposes one accessor, so views never branch on locale.

DATA STRUCTURE:
Accessor (computed property) over two columns; O(1).
*/
