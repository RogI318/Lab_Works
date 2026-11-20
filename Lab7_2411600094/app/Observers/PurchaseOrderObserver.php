<?php

namespace App\Observers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PurchaseOrderObserver
{
    public function created(PurchaseOrder $order): void
    {
        $order->addLog('created', null, $order->status, null, $order->created_by);

        $recipients = User::admins()->get()
            ->merge(User::where('role', 'manager')->get())
            ->unique('id')
            ->pluck('email')
            ->all();

        $this->queueMail($recipients, new PurchaseOrderCreated($order));
    }

    public function updated(PurchaseOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $from = $order->getOriginal('status');
        $to = $order->status;
        $comment = $to === 'rejected' ? $order->rejection_reason : null;

        $order->addLog($to, $from, $to, $comment, Auth::id());

        if ($to === 'approved' && $order->createdBy?->email) {
            $this->queueMail([$order->createdBy->email], new PurchaseOrderApproved($order));
        }

        if ($to === 'sent' && $order->supplier?->email) {
            $this->queueMail([$order->supplier->email], new PurchaseOrderSent($order));
        }
    }

    private function queueMail(array $recipients, object $mailable): void
    {
        $recipients = array_values(array_filter($recipients));

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->queue($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
