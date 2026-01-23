<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NoteService
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Adiciona uma nota a uma conversa
     */
    public function addNote(int $conversationId, int $agentId, string $content, bool $isPrivate = true): int
    {
        $noteId = DB::table('conversation_notes')->insertGetId([
            'conversation_id' => $conversationId,
            'agent_id' => $agentId,
            'content' => $content,
            'is_private' => $isPrivate,
            'created_at' => now()->toDateTimeString(),
        ]);

        // Registra na auditoria
        $this->auditService->logNoteAdded($conversationId, $agentId, $content);

        return $noteId;
    }

    /**
     * Retorna as notas de uma conversa
     */
    public function getNotes(int $conversationId, bool $includePrivate = true): array
    {
        $query = DB::table('conversation_notes')
            ->join('agents', 'agents.id', '=', 'conversation_notes.agent_id')
            ->where('conversation_notes.conversation_id', $conversationId)
            ->select([
                'conversation_notes.id',
                'conversation_notes.content',
                'conversation_notes.is_private',
                'conversation_notes.created_at',
                'agents.id as agent_id',
                'agents.name as agent_name',
            ]);

        if (!$includePrivate) {
            $query->where('conversation_notes.is_private', 0);
        }

        return $query->orderByDesc('conversation_notes.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Retorna as notas mais recentes para o painel do supervisor
     */
    public function getRecentNotes(int $limit = 20): array
    {
        return DB::table('conversation_notes')
            ->join('agents', 'agents.id', '=', 'conversation_notes.agent_id')
            ->join('conversations', 'conversations.id', '=', 'conversation_notes.conversation_id')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->select([
                'conversation_notes.id',
                'conversation_notes.conversation_id',
                'contacts.name as contact_name',
                'conversation_notes.content',
                'conversation_notes.is_private',
                'conversation_notes.created_at',
                'agents.name as agent_name',
            ])
            ->orderByDesc('conversation_notes.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
