<?php

namespace App\Services;

use App\Enums\AvaEnrollmentStatus;
use App\Mail\AvaEnrollmentConfirmedMail;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\OrderSplit;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AvaEnrollmentService
{
    /**
     * Cria matrículas para todos os produtos digitais de um split confirmado.
     * Chamado pelo listener HandleAvaEnrollmentOnSplitConfirmed.
     */
    public function createFromOrderSplit(OrderSplit $split): void
    {
        $userId = $split->order?->user_id;

        if (! $userId) {
            return;
        }

        $items = $split->order
            ->items()
            ->where('expositor_id', $split->expositor_id)
            ->with('product.avaCourse')
            ->get();

        foreach ($items as $item) {
            $product = $item->product;

            if (! $product?->is_digital || ! $product->avaCourse) {
                continue;
            }

            $course = $product->avaCourse;

            if (! $course->isPublished()) {
                continue;
            }

            $alreadyEnrolled = AvaEnrollment::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->exists();

            if ($alreadyEnrolled) {
                continue;
            }

            $enrollment = $this->enroll(
                user:   $split->order->user,
                course: $course,
                split:  $split,
            );

            try {
                Mail::to($split->order->user)->send(new AvaEnrollmentConfirmedMail($enrollment));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Cria uma matrícula diretamente (admin, cortesia, etc.).
     */
    public function enroll(User $user, AvaCourse $course, ?OrderSplit $split = null): AvaEnrollment
    {
        $expiresAt = $course->access_duration_days
            ? now()->addDays($course->access_duration_days)
            : null;

        return AvaEnrollment::create([
            'user_id'        => $user->id,
            'course_id'      => $course->id,
            'order_split_id' => $split?->id,
            'status'         => AvaEnrollmentStatus::Active,
            'enrolled_at'    => now(),
            'expires_at'     => $expiresAt,
        ]);
    }
}
