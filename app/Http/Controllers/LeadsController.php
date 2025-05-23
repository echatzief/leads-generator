<?php

namespace App\Http\Controllers;

use App\Libraries\Mailchimp;
use App\Models\Lead;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeadsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => 'required|email|unique:leads,email',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'allowSendEmails' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $lead = Lead::query()->create([
                'email' => $payload['email'],
                'first_name' => $payload['firstName'],
                'last_name' => $payload['lastName'],
                'allow_send_emails' => $payload['allowSendEmails'],
            ]);

            $mailchimp = new Mailchimp();
            $mailchimp->subscribe(
                $lead->email,
                [
                    'FNAME' => $lead->first_name,
                    'LNAME' => $lead->last_name
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Lead added successfully');
        } catch (Exception) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['message' => 'An error occurred while creating the lead or subscribing the member to Mailchimp.']);
        }
    }

    public function update(string $id, Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => 'required|email',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'allowSendEmails' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();
            $mailchimp = new Mailchimp();

            $lead = Lead::query()->find($id);
            if (!$lead) {
                return redirect()->back()
                    ->withErrors(['message' => 'The member does not exists.']);
            }

            $mailchimp->unsubscribe($lead->email);

            $lead->update([
                'email' => $payload['email'],
                'first_name' => $payload['firstName'],
                'last_name' => $payload['lastName'],
                'allow_send_emails' => $payload['allowSendEmails']
            ]);

            $mailchimp->subscribe(
                $payload['email'],
                [
                    'FNAME' => $payload['firstName'],
                    'LNAME' => $payload['lastName'],
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Lead updated successfully');
        } catch (Exception) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['message' => 'An error occurred while updating the lead or updating the member at Mailchimp.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $lead = Lead::query()->find($id);
            if (!$lead) {
                return redirect()->back()
                    ->withErrors(['message' => 'The member does not exists.']);
            }

            $lead->delete();

            $mailchimp = new Mailchimp();
            $mailchimp->unsubscribe($lead->email);

            DB::commit();

            return redirect()->back()->with('success', 'Lead deleted successfully');
        } catch (Exception) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['message' => 'An error occurred while deleting the lead or deleting the member from Mailchimp.']);
        }
    }

    public function index(Request $request): Response
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 10);

        $total = Lead::query()->count();
        $leads = Lead::query()->offset($page - 1)->limit($limit)->get()->map(function ($lead) {
            return [
                'id' => $lead->id,
                'email' => $lead->email,
                'firstName' => $lead->first_name,
                'lastName' => $lead->last_name,
                'allowSendEmails' => $lead->allow_send_emails,
                'createdAt' => $lead->created_at,
                'updatedAt' => $lead->updated_at,
            ];
        });

        return Inertia::render('Index', [
            'leads' => $leads,
            'total' => $total,
            'totalPages' => ceil($total / $limit),
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
