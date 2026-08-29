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
     * Revoga o acesso concedido por um repasse que foi desfeito.
     *
     * Chamado pelo listener `HandleAvaEnrollmentOnSplitReverted`, ou seja,
     * **depois do commit** da reversão: revogar acesso sobre uma transição que
     * ainda pode sofrer rollback trancaria o aluno fora de um curso que continua
     * pago.
     *
     * ## O que é revogado, e o que não é
     *
     * Revogado: o direito **atual** de acesso. `AvaEnrollmentStatus::Refunded`
     * responde `isAccessible() === false`, e com isso o player, os materiais e a
     * API de aprendizado passam a recusar — cada um já consultava
     * `isAccessible()` antes desta fase.
     *
     * Preservado, e de propósito: `progress`, `completion_percent`,
     * `completed_at`, `certificate_path` e `enrolled_at`. Nada disso deixou de
     * ter acontecido porque o dinheiro voltou. Apagar o progresso de quem
     * estudou seria reescrever história para simplificar um estado, e é
     * exatamente o que a trilha FIN-SEC existe para não fazer.
     *
     * ## Idempotência
     *
     * Só matrícula `Active` transiciona. Refund reentregue encontra `Refunded` e
     * não faz nada; e uma matrícula já `Cancelled` ou `Expired` por outro motivo
     * não é sobrescrita — o motivo pelo qual o acesso terminou é informação.
     */
    public function revokeFromOrderSplit(OrderSplit $split): void
    {
        AvaEnrollment::query()
            ->where('order_split_id', $split->id)
            ->where('status', AvaEnrollmentStatus::Active->value)
            ->get()
            ->each
            ->update(['status' => AvaEnrollmentStatus::Refunded]);
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
