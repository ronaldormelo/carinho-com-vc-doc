<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverRegion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Lista regioes de atuacao de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $regions = $caregiver->regions()
            ->orderBy('city')
            ->orderBy('neighborhood')
            ->get();

        return $this->success([
            'regions' => $regions,
        ]);
    }

    /**
     * Adiciona regiao de atuacao.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:128',
            'neighborhood' => 'nullable|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Verifica se ja existe
        $existing = $caregiver->regions()
            ->where('city', $request->get('city'))
            ->where('neighborhood', $request->get('neighborhood'))
            ->first();

        if ($existing) {
            return $this->error('Esta regiao ja esta cadastrada', 422);
        }

        $region = CaregiverRegion::create([
            'caregiver_id' => $caregiver->id,
            'city' => $request->get('city'),
            'neighborhood' => $request->get('neighborhood'),
        ]);

        return $this->success($region, 'Regiao adicionada com sucesso', 201);
    }

    /**
     * Remove regiao de atuacao.
     */
    public function destroy(int $caregiverId, int $regionId): JsonResponse
    {
        $region = CaregiverRegion::where('caregiver_id', $caregiverId)
            ->where('id', $regionId)
            ->first();

        if (!$region) {
            return $this->error('Regiao nao encontrada', 404);
        }

        $region->delete();

        return $this->success(null, 'Regiao removida com sucesso');
    }

    /**
     * Sincroniza todas as regioes de uma vez.
     */
    public function sync(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'regions' => 'required|array',
            'regions.*.city' => 'required|string|max:128',
            'regions.*.neighborhood' => 'nullable|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Remove regioes atuais
        $caregiver->regions()->delete();

        // Adiciona novas
        foreach ($request->get('regions') as $item) {
            CaregiverRegion::create([
                'caregiver_id' => $caregiver->id,
                'city' => $item['city'],
                'neighborhood' => $item['neighborhood'] ?? null,
            ]);
        }

        return $this->success(
            $caregiver->regions()->orderBy('city')->orderBy('neighborhood')->get(),
            'Regioes sincronizadas com sucesso'
        );
    }

    /**
     * Lista cidades disponiveis (para filtros).
     */
    public function cities(): JsonResponse
    {
        $cities = CaregiverRegion::select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return $this->success(['cities' => $cities]);
    }

    /**
     * Lista bairros de uma cidade (para filtros).
     */
    public function neighborhoods(string $city): JsonResponse
    {
        $neighborhoods = CaregiverRegion::where('city', $city)
            ->whereNotNull('neighborhood')
            ->select('neighborhood')
            ->distinct()
            ->orderBy('neighborhood')
            ->pluck('neighborhood');

        return $this->success(['neighborhoods' => $neighborhoods]);
    }
}
