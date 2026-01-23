<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    /**
     * Lista disponibilidade de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $availability = $caregiver->availability()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Agrupa por dia da semana
        $grouped = $availability->groupBy('day_of_week')
            ->map(fn ($items) => $items->map(fn ($item) => [
                'id' => $item->id,
                'start_time' => $item->start_time,
                'end_time' => $item->end_time,
                'display' => $item->formatted_time_range,
            ]));

        return $this->success([
            'availability' => $availability,
            'by_day' => $grouped,
            'days' => CaregiverAvailability::DAYS,
        ]);
    }

    /**
     * Adiciona horario de disponibilidade.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $availability = CaregiverAvailability::create([
            'caregiver_id' => $caregiver->id,
            'day_of_week' => $request->get('day_of_week'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
        ]);

        return $this->success($availability, 'Disponibilidade adicionada', 201);
    }

    /**
     * Atualiza horario de disponibilidade.
     */
    public function update(Request $request, int $caregiverId, int $availabilityId): JsonResponse
    {
        $availability = CaregiverAvailability::where('caregiver_id', $caregiverId)
            ->where('id', $availabilityId)
            ->first();

        if (!$availability) {
            return $this->error('Disponibilidade nao encontrada', 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'sometimes|integer|between:0,6',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $availability->update($validator->validated());

        return $this->success($availability, 'Disponibilidade atualizada');
    }

    /**
     * Remove horario de disponibilidade.
     */
    public function destroy(int $caregiverId, int $availabilityId): JsonResponse
    {
        $availability = CaregiverAvailability::where('caregiver_id', $caregiverId)
            ->where('id', $availabilityId)
            ->first();

        if (!$availability) {
            return $this->error('Disponibilidade nao encontrada', 404);
        }

        $availability->delete();

        return $this->success(null, 'Disponibilidade removida');
    }

    /**
     * Sincroniza toda a disponibilidade de uma vez.
     */
    public function sync(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'availability' => 'required|array',
            'availability.*.day_of_week' => 'required|integer|between:0,6',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Remove disponibilidades atuais
        $caregiver->availability()->delete();

        // Adiciona novas
        foreach ($request->get('availability') as $item) {
            CaregiverAvailability::create([
                'caregiver_id' => $caregiver->id,
                'day_of_week' => $item['day_of_week'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
            ]);
        }

        return $this->success(
            $caregiver->availability()->orderBy('day_of_week')->orderBy('start_time')->get(),
            'Disponibilidade sincronizada'
        );
    }
}
