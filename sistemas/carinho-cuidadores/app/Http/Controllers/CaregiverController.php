<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\DomainCaregiverStatus;
use App\Services\CaregiverService;
use App\Services\TriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaregiverController extends Controller
{
    public function __construct(
        private CaregiverService $caregiverService,
        private TriageService $triageService
    ) {}

    /**
     * Lista cuidadores com filtros e paginacao.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            (int) $request->get('per_page', config('cuidadores.pagination.default_per_page')),
            config('cuidadores.pagination.max_per_page')
        );

        $query = Caregiver::with(['status', 'skills.careType', 'regions']);

        // Filtros
        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('code', $request->get('status')));
        }

        if ($request->filled('city')) {
            $query->byCity($request->get('city'));
        }

        if ($request->filled('care_type')) {
            $query->bySkill($request->get('care_type'));
        }

        if ($request->filled('min_experience')) {
            $query->withMinExperience((int) $request->get('min_experience'));
        }

        if ($request->filled('day_of_week')) {
            $query->availableOn((int) $request->get('day_of_week'));
        }

        // Ordenacao
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    /**
     * Exibe detalhes de um cuidador.
     */
    public function show(int $id): JsonResponse
    {
        $caregiver = Caregiver::with([
            'status',
            'documents.docType',
            'documents.status',
            'skills.careType',
            'skills.level',
            'availability',
            'regions',
            'contracts.status',
            'ratings',
            'trainings',
        ])->find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        return $this->success([
            'caregiver' => $caregiver,
            'metrics' => [
                'average_rating' => $caregiver->average_rating,
                'total_ratings' => $caregiver->total_ratings,
                'total_incidents' => $caregiver->incidents()->count(),
                'has_all_documents' => $caregiver->has_all_required_documents,
            ],
        ]);
    }

    /**
     * Cadastra novo cuidador (formulario de cadastro digital).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'cpf' => 'nullable|string|max:14',
            'birth_date' => 'nullable|date|before:today',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:128',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_complement' => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:128',
            'address_zipcode' => 'nullable|string|max:10',
            'address_state' => 'nullable|string|max:2',
            'experience_years' => 'nullable|integer|min:0',
            'profile_summary' => 'nullable|string|max:2000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:32',
            'recruitment_source' => 'nullable|string|max:64',
            'referred_by_caregiver_id' => 'nullable|integer|exists:caregivers,id',
            'skills' => 'nullable|array',
            'skills.*.care_type_code' => 'required_with:skills|string',
            'skills.*.level_code' => 'required_with:skills|string',
            'availability' => 'nullable|array',
            'availability.*.day_of_week' => 'required_with:availability|integer|between:0,6',
            'availability.*.start_time' => 'required_with:availability|date_format:H:i',
            'availability.*.end_time' => 'required_with:availability|date_format:H:i',
            'regions' => 'nullable|array',
            'regions.*.city' => 'required_with:regions|string|max:128',
            'regions.*.neighborhood' => 'nullable|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Valida CPF se informado
        if ($request->filled('cpf')) {
            $cpf = Caregiver::normalizeCpf($request->get('cpf'));
            
            if (!Caregiver::validateCpf($cpf)) {
                return $this->error('CPF inválido', 422, ['cpf' => ['O CPF informado é inválido']]);
            }

            // Verifica duplicidade
            if (Caregiver::where('cpf', $cpf)->exists()) {
                return $this->error('CPF já cadastrado', 422, ['cpf' => ['Este CPF já está cadastrado no sistema']]);
            }
        }

        // Valida idade mínima se data de nascimento informada
        if ($request->filled('birth_date')) {
            $birthDate = new \DateTime($request->get('birth_date'));
            $age = $birthDate->diff(new \DateTime())->y;
            $minAge = config('cuidadores.operacional.min_age_years', 18);
            
            if ($age < $minAge) {
                return $this->error('Idade insuficiente', 422, [
                    'birth_date' => ["O cuidador deve ter no mínimo {$minAge} anos"]
                ]);
            }
        }

        $caregiver = $this->caregiverService->create($validator->validated());

        return $this->success($caregiver, 'Cuidador cadastrado com sucesso', 201);
    }

    /**
     * Atualiza dados do cuidador.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $caregiver = Caregiver::find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:32',
            'email' => 'nullable|email|max:255',
            'city' => 'sometimes|string|max:128',
            'experience_years' => 'nullable|integer|min:0',
            'profile_summary' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $caregiver = $this->caregiverService->update($caregiver, $validator->validated());

        return $this->success($caregiver, 'Cuidador atualizado com sucesso');
    }

    /**
     * Ativa cuidador.
     */
    public function activate(int $id): JsonResponse
    {
        $caregiver = Caregiver::find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $result = $this->caregiverService->changeStatus($caregiver, 'active');

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($caregiver->fresh(['status']), 'Cuidador ativado com sucesso');
    }

    /**
     * Desativa cuidador.
     */
    public function deactivate(int $id): JsonResponse
    {
        $caregiver = Caregiver::find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $result = $this->caregiverService->changeStatus($caregiver, 'inactive');

        return $this->success($caregiver->fresh(['status']), 'Cuidador desativado com sucesso');
    }

    /**
     * Bloqueia cuidador.
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $caregiver = Caregiver::find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $reason = $request->get('reason', 'Bloqueio administrativo');
        $result = $this->caregiverService->changeStatus($caregiver, 'blocked', $reason);

        return $this->success($caregiver->fresh(['status']), 'Cuidador bloqueado');
    }

    /**
     * Verifica elegibilidade para ativacao (triagem).
     */
    public function checkEligibility(int $id): JsonResponse
    {
        $caregiver = Caregiver::with(['documents.docType', 'documents.status'])->find($id);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $eligibility = $this->triageService->checkEligibility($caregiver);

        return $this->success($eligibility);
    }
}
