<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverTraining;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    /**
     * Lista treinamentos de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $trainings = $caregiver->trainings()
            ->orderBy('completed_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $this->success([
            'trainings' => $trainings,
            'completed_count' => $trainings->filter(fn ($t) => $t->is_completed)->count(),
            'pending_count' => $trainings->filter(fn ($t) => !$t->is_completed)->count(),
        ]);
    }

    /**
     * Registra novo treinamento.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'course_name' => 'required|string|max:255',
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $training = CaregiverTraining::create([
            'caregiver_id' => $caregiver->id,
            'course_name' => $request->get('course_name'),
            'completed_at' => $request->get('completed_at'),
        ]);

        return $this->success($training, 'Treinamento registrado com sucesso', 201);
    }

    /**
     * Marca treinamento como concluido.
     */
    public function complete(int $caregiverId, int $trainingId): JsonResponse
    {
        $training = CaregiverTraining::where('caregiver_id', $caregiverId)
            ->where('id', $trainingId)
            ->first();

        if (!$training) {
            return $this->error('Treinamento nao encontrado', 404);
        }

        $training->update([
            'completed_at' => now(),
        ]);

        return $this->success($training, 'Treinamento marcado como concluido');
    }

    /**
     * Remove registro de treinamento.
     */
    public function destroy(int $caregiverId, int $trainingId): JsonResponse
    {
        $training = CaregiverTraining::where('caregiver_id', $caregiverId)
            ->where('id', $trainingId)
            ->first();

        if (!$training) {
            return $this->error('Treinamento nao encontrado', 404);
        }

        $training->delete();

        return $this->success(null, 'Treinamento removido');
    }

    /**
     * Lista cursos disponiveis (para sugestao).
     */
    public function availableCourses(): JsonResponse
    {
        // Lista de cursos padrao oferecidos
        $courses = [
            [
                'name' => 'Cuidados Basicos com Idosos',
                'description' => 'Fundamentos do cuidado diario',
                'duration_hours' => 8,
            ],
            [
                'name' => 'Primeiros Socorros',
                'description' => 'Atendimento emergencial basico',
                'duration_hours' => 4,
            ],
            [
                'name' => 'Cuidados com PCD',
                'description' => 'Atencao especializada para pessoas com deficiencia',
                'duration_hours' => 12,
            ],
            [
                'name' => 'TEA - Transtorno do Espectro Autista',
                'description' => 'Abordagem e cuidados especificos',
                'duration_hours' => 16,
            ],
            [
                'name' => 'Cuidados Pos-Operatorios',
                'description' => 'Acompanhamento de recuperacao cirurgica',
                'duration_hours' => 8,
            ],
            [
                'name' => 'Comunicacao e Postura Profissional',
                'description' => 'Relacionamento com familias e pacientes',
                'duration_hours' => 4,
            ],
            [
                'name' => 'Administracao de Medicamentos',
                'description' => 'Controle e aplicacao segura',
                'duration_hours' => 6,
            ],
        ];

        return $this->success(['courses' => $courses]);
    }
}
