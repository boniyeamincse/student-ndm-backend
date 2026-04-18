<?php

namespace App\Http\Requests\Concerns;

trait NormalizesEmptyValues
{
    protected function normalizeEmptyToNull(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}