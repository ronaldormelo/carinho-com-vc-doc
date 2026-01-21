<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Repositories\AtendimentoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function __construct(private AtendimentoRepository $repository)
    {
    }

    public function sendProposal(Request $request, int $conversation): JsonResponse
    {
        return $this->sendEmail($request, $conversation, 'proposal');
    }

    public function sendContract(Request $request, int $conversation): JsonResponse
    {
        return $this->sendEmail($request, $conversation, 'contract');
    }

    private function sendEmail(Request $request, int $conversation, string $type): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['nullable', 'email'],
            'data' => ['nullable', 'array'],
        ]);

        $conversationData = $this->repository->findConversationById($conversation);

        if (!$conversationData) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $contact = $this->repository->findContactById($conversationData->contact_id);

        if (!$contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        $to = $validated['to'] ?? $contact->email;

        if (!$to) {
            return response()->json(['message' => 'Missing recipient email'], 422);
        }

        $payload = array_merge([
            'name' => $contact->name,
            'city' => $contact->city,
        ], $validated['data'] ?? []);

        SendEmailJob::dispatch($type, $to, $payload);

        return response()->json(['status' => 'queued']);
    }
}
