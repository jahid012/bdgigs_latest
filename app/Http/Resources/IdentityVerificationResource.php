<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'status' => $this->status,
            'details' => $this->details ?: [],
            'documentPath' => $this->document_path,
            'submittedAt' => $this->submitted_at?->toISOString(),
            'reviewNote' => $this->review_note,
            'additionalDocumentNote' => $this->additional_document_note,
        ];
    }
}
