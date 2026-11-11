<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/settings/preferences — docs/SRS.md FR-UCP-004. */
final class PreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'theme' => ['required', 'in:dark,light,system'],
            'reduced_effects' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reduced_effects' => $this->boolean('reduced_effects')]);
    }
}
