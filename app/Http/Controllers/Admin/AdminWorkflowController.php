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
use App\Actions\Admin\SetUserActiveAction;
use App\Actions\Admin\UpdateSettingsAction;
use App\Actions\Admin\VoidTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\IssueRefundRequest;
use App\Http\Requests\SetUserActiveRequest;
use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Requests\VoidTicketRequest;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\Showtime;
use App\Models\StripeEvent;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Stripe\Exception\ApiErrorException;

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
        try {
            $processed = $action->execute($refund);
        } catch (ApiErrorException|\RuntimeException $exception) {
            return back()->withErrors(['refund' => 'Refund gateway failed: '.($refund->fresh()->failure_message ?: $exception->getMessage())]);
        }

        if ($processed->status->value === 'failed') {
            return back()->withErrors(['refund' => 'Refund gateway failed: '.($processed->failure_message ?: 'Unknown gateway error.')]);
        }

        return back()->with('status', 'Refund processed successfully.');
    }

    public function retryRefund(Refund $refund, RetryRefundAction $action): RedirectResponse
    {
        try {
            $retried = $action->execute($refund);
        } catch (ApiErrorException|\RuntimeException $exception) {
            return back()->withErrors(['refund' => 'Refund retry failed: '.($refund->fresh()->failure_message ?: $exception->getMessage())]);
        }

        if ($retried->status->value === 'failed') {
            return back()->withErrors(['refund' => 'Refund retry failed: '.($retried->failure_message ?: 'Unknown gateway error.')]);
        }

        return back()->with('status', 'Refund retry completed successfully.');
    }

    public function cancelShowtime(
        CancelShowtimeRequest $request,
        Showtime $showtime,
        CancelShowtimeAction $action,
    ): RedirectResponse {
        $action->execute($showtime, $request->validated('reason'), $request->user());

        return back()->with('status', 'Showtime cancelled.');
    }

    public function voidTicket(VoidTicketRequest $request, Ticket $ticket, VoidTicketAction $action): RedirectResponse
    {
        $action->execute($ticket, $request->validated('reason'));

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

    public function setUserActive(SetUserActiveRequest $request, User $user, SetUserActiveAction $action): RedirectResponse
    {
        $action->execute($user, $request->boolean('active'), $request->user());

        return back()->with('status', $request->boolean('active') ? 'User account reactivated.' : 'User account deactivated.');
    }

    public function updateSettings(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->execute($request->validated('values'));

        return back()->with('status', 'Settings updated.');
    }
}
