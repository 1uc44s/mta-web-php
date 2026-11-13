<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use App\Services\Notifications\NotificationPreferences;
use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/settings/notifications — docs/DATABASE.md §3.11. İşaretsiz kutu = kapalı. */
final class NotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['prefs' => ['array'], 'prefs.*' => ['array'], 'prefs.*.*' => ['boolean']];
    }

    /** @return array<string, array<string, bool>> */
    public function matrix(): array
    {
        $input = (array) $this->validated('prefs', []);
        $out = [];
        foreach (NotificationPreferences::MATRIX as $category => $channels) {
            foreach ($channels as $channel) {
                $out[$category][$channel] = (bool) ($input[$category][$channel] ?? false);
            }
        }

        return $out;
    }
}
