<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Ai\Schemas\CompanyContentSchema;
use App\Enums\CompanyReviewStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CompaniesTable::approveAction(),
            CompaniesTable::rejectAction(),
            CompaniesTable::generateWordPressPostAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Validate the edited content against CompanyContentSchema BEFORE it is
     * persisted, and send the draft back to pending review whenever the
     * content actually changed — mirroring the spirit of
     * Company::REVIEWED_ATTRIBUTES (edited content must be re-approved
     * before publication). The form only carries the editable leaf fields,
     * so the saved payload is merged back over the original to keep keys
     * the form does not render (e.g. the schema version "v").
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Server-side lock: while a generation run owns the content row the
        // company must not be edited — 409 regardless of the disabled UI.
        if ($this->getRecord()->contentRecord?->status->isProcessing() ?? false) {
            abort(409, __('companies.content_locked'));
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['content'] = $this->record->content ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $original = is_array($this->record->content) ? $this->record->content : [];

        $prune = function ($value) use (&$prune) {
            if (! is_array($value)) {
                return $value;
            }

            $filtered = [];

            foreach ($value as $key => $item) {
                // Untouched inputs submit as null, an empty string or an
                // empty array — all mean "not edited" and must not reach
                // validation. Nested arrays prune first, so a subtree whose
                // children were all untouched disappears entirely.
                if (is_array($item)) {
                    $prunedItem = $prune($item);

                    if ($prunedItem !== []) {
                        $filtered[$key] = $prunedItem;
                    }

                    continue;
                }

                if ($item === null || $item === '') {
                    continue;
                }

                $filtered[$key] = $item;
            }

            return $filtered;
        };

        $merged = $original;
        $validationErrors = [];

        foreach ((array) ($data['content'] ?? []) as $locale => $payload) {
            $payload = $prune($payload);

            if ($payload === []) {
                continue;
            }

            $merged[$locale] = array_replace($original[$locale] ?? [], $payload);

            foreach (CompanyContentSchema::validate($merged[$locale]) as $field => $message) {
                $validationErrors["data.content.{$locale}.{$field}"] = "محتوای زبان «{$locale}» نامعتبر است — {$field}: {$message}";
            }
        }

        if ($validationErrors !== []) {
            throw ValidationException::withMessages($validationErrors);
        }

        if ($merged !== $original) {
            $data['review_status'] = CompanyReviewStatus::PendingReview;
            $data['reviewed_at'] = null;
        }

        $data['content'] = $merged;

        return $data;
    }
}
