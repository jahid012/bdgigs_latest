<?php

namespace App\Services;

use App\Events\IdentityAdditionalDocumentRequested;
use App\Events\IdentityVerificationApproved;
use App\Events\IdentityVerificationRejected;
use App\Events\IdentityVerificationUnderReview;
use App\Models\Admin;
use App\Models\IdentityVerificationSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IdentityVerificationReviewService
{
    public function markUnderReview(IdentityVerificationSubmission $submission, User|Admin|null $admin): IdentityVerificationSubmission
    {
        return $this->transition($submission, $admin, 'under_review', 'Identity verification moved under review.');
    }

    public function approve(IdentityVerificationSubmission $submission, User|Admin|null $admin, ?string $note = null): IdentityVerificationSubmission
    {
        return $this->transition($submission, $admin, 'approved', $note ?: 'Identity verification approved.');
    }

    public function reject(IdentityVerificationSubmission $submission, User|Admin|null $admin, string $reason): IdentityVerificationSubmission
    {
        return $this->transition($submission, $admin, 'rejected', $reason);
    }

    public function requestAdditionalDocument(IdentityVerificationSubmission $submission, User|Admin|null $admin, string $note): IdentityVerificationSubmission
    {
        return $this->transition($submission, $admin, 'additional_document_required', $note);
    }

    private function transition(IdentityVerificationSubmission $submission, User|Admin|null $admin, string $status, string $note): IdentityVerificationSubmission
    {
        return DB::transaction(function () use ($submission, $admin, $status, $note) {
            $submission->forceFill([
                'status' => $status,
                'reviewed_by' => $admin instanceof User ? $admin->id : null,
                'reviewed_by_admin_id' => $admin instanceof Admin ? $admin->id : null,
                'review_note' => $note,
                'reviewed_at' => now(),
                'additional_document_requested_at' => $status === 'additional_document_required' ? now() : $submission->additional_document_requested_at,
                'additional_document_note' => $status === 'additional_document_required' ? $note : $submission->additional_document_note,
            ])->save();

            $user = $submission->user;
            $user?->forceFill([
                'verification_status' => match ($status) {
                    'approved' => 'verified',
                    'rejected' => 'rejected',
                    'additional_document_required' => 'additional_document_required',
                    default => 'review',
                },
            ])->save();

            DB::afterCommit(function () use ($submission, $admin, $status, $note) {
                $fresh = $submission->fresh(['user', 'reviewer', 'adminReviewer']);

                match ($status) {
                    'approved' => event(new IdentityVerificationApproved($fresh, $admin)),
                    'rejected' => event(new IdentityVerificationRejected($fresh, $admin, $note)),
                    'additional_document_required' => event(new IdentityAdditionalDocumentRequested($fresh, $admin, $note)),
                    default => event(new IdentityVerificationUnderReview($fresh, $admin)),
                };
            });

            return $submission->fresh(['user', 'reviewer', 'adminReviewer']);
        });
    }
}
