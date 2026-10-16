<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CancelBookingAction;
use App\Actions\Admin\CancelShowtimeAction;
use App\Actions\Admin\CreateStaffUserAction;
use App\Actions\Admin\IssueRefundAction;
use App\Actions\Admin\ProcessRefundAction;
use App\Actions\Admin\ReplayStripeEventAction;
use App\Actions\Admin\ResendBookingEmailAction;
use App\Actions\Admin\RetryRefundAction;
use App\Actions\Admin\UpdateSettingsAction;
use App\Actions\Admin\VoidTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\IssueRefundRequest;
use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\Showtime;
use App\Models\StripeEvent;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class AdminWorkflowController extends Controller
{
    public function cancelBooking(CancelBookingRequest $request, Booking $booking, CancelBookingAction $action): RedirectResponse
    {
        $action->execute(
            $booking,
            $request->validated('reason'),
            (bool) $request->validated('override', false),
            $request->user(),
        );

        return back()->with('status', 'Booking cancelled.');
    }

    public function issueRefund(IssueRefundRequest $request, Booking $booking, IssueRefundAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->execute($booking, (int) $data['amount_cents'], $data['reason'], $request->user());

        return back()->with('status', 'Refund request created.');
    }

    public function processRefund(Refund $refund, ProcessRefundAction $action): RedirectResponse
    {
        $action->execute($refund);

        return back()->with('status', 'Refund processed.');
    }

    public function retryRefund(Refund $refund, RetryRefundAction $action): RedirectResponse
    {
        $action->execute($refund);

        return back()->with('status', 'Refund retry completed.');
    }

    public function cancelShowtime(
        CancelShowtimeRequest $request,
        Showtime $showtime,
        CancelShowtimeAction $action,
    ): RedirectResponse {
        $action->execute($showtime, $request->validated('reason'), $request->user());

        return back()->with('status', 'Showtime cancelled.');
    }

    public function voidTicket(Ticket $ticket, VoidTicketAction $action): RedirectResponse
    {
        $action->execute($ticket);

        return back()->with('status', 'Ticket voided.');
    }

    public function resendBookingEmail(Booking $booking, ResendBookingEmailAction $action): RedirectResponse
    {
        $action->execute($booking);

        return back()->with('status', 'Booking confirmation email queued.');
    }

    public function replayStripeEvent(StripeEvent $event, ReplayStripeEventAction $action): RedirectResponse
    {
        $action->execute($event);

        return back()->with('status', 'Stripe event replay queued.');
    }

    public function createStaff(StoreStaffUserRequest $request, CreateStaffUserAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->execute($data['name'], $data['email'], $data['password']);

        return back()->with('status', 'Staff account created.');
    }

    public function updateSettings(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->execute($request->validated('values'));

        return back()->with('status', 'Settings updated.');
    }
}
