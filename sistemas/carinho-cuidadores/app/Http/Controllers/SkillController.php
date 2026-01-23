<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverSkill;
use App\Models\DomainCareType;
use App\Models\DomainSkillLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
{
    /**
     * Lista habilidades de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $skills = $caregiver->skills()
            ->with(['careType', 'level'])
            ->get();

        return $this->success([
            'skills' => $skills,
            'available_care_types' => DomainCareType::all(),
            'available_levels' => DomainSkillLevel::all(),
        ]);
    }

    /**
     * Adiciona habilidade ao cuidador.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'care_type_code' => 'required|string|exists:domain_care_type,code',
            'level_code' => 'required|string|exists:domain_skill_level,code',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $careType = DomainCareType::byCode($request->get('care_type_code'));
        $level = DomainSkillLevel::byCode($request->get('level_code'));

        // Verifica se ja existe
        $existing = $caregiver->skills()
            ->where('care_type_id', $careType->id)
            ->first();

        if ($existing) {
            // Atualiza nivel
            $existing->update(['level_id' => $level->id]);
            return $this->success(
                $existing->fresh(['careType', 'level']),
                'Nivel de habilidade atualizado'
            );
        }

        $skill = CaregiverSkill::create([
            'caregiver_id' => $caregiver->id,
            'care_type_id' => $careType->id,
            'level_id' => $level->id,
        ]);

        return $this->success(
            $skill->load(['careType', 'level']),
            'Habilidade adicionada com sucesso',
            201
        );
    }

    /**
     * Remove habilidade do cuidador.
     */
    public function destroy(int $caregiverId, int $skillId): JsonResponse
    {
        $skill = CaregiverSkill::where('caregiver_id', $caregiverId)
            ->where('id', $skillId)
            ->first();

        if (!$skill) {
            return $this->error('Habilidade nao encontrada', 404);
        }

        $skill->delete();

        return $this->success(null, 'Habilidade removida com sucesso');
    }

    /**
     * Sincroniza todas as habilidades de uma vez.
     */
    public function sync(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'skills' => 'required|array',
            'skills.*.care_type_code' => 'required|string|exists:domain_care_type,code',
            'skills.*.level_code' => 'required|string|exists:domain_skill_level,code',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Remove todas as habilidades atuais
        $caregiver->skills()->delete();

        // Adiciona novas
        foreach ($request->get('skills') as $skillData) {
            $careType = DomainCareType::byCode($skillData['care_type_code']);
            $level = DomainSkillLevel::byCode($skillData['level_code']);

            CaregiverSkill::create([
                'caregiver_id' => $caregiver->id,
                'care_type_id' => $careType->id,
                'level_id' => $level->id,
            ]);
        }

        return $this->success(
            $caregiver->skills()->with(['careType', 'level'])->get(),
            'Habilidades sincronizadas com sucesso'
        );
    }
}
