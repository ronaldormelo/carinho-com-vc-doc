<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\DomainOwnerType;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * Lista contas bancárias de um proprietário.
     */
    public function index(Request $request)
    {
        $request->validate([
            'owner_type' => 'required|string|in:client,caregiver,company',
            'owner_id' => 'required|integer',
        ]);

        $ownerType = DomainOwnerType::getByCode($request->owner_type);

        $accounts = BankAccount::forOwner($ownerType->id, $request->owner_id)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'bank_code' => $account->bank_code,
                    'account_type' => $account->account_type,
                    'holder_name' => $account->holder_name,
                    'masked_account' => $account->masked_account,
                    'pix_key_type' => $account->pix_key_type,
                    'is_default' => $account->is_default,
                    'is_verified' => $account->isVerified(),
                ];
            });

        return $this->successResponse($accounts);
    }

    /**
     * Cadastra conta bancária.
     */
    public function store(Request $request)
    {
        $request->validate([
            'owner_type' => 'required|string|in:client,caregiver,company',
            'owner_id' => 'required|integer',
            'bank_name' => 'required|string|max:128',
            'bank_code' => 'nullable|string|max:10',
            'account_type' => 'required|string|in:checking,savings',
            'holder_name' => 'required|string|max:255',
            'holder_document' => 'required|string|max:20',
            'agency' => 'required|string|max:10',
            'account_number' => 'required|string|max:20',
            'account_digit' => 'nullable|string|max:2',
            'pix_key' => 'nullable|string|max:255',
            'pix_key_type' => 'nullable|string|in:cpf,cnpj,phone,email,random',
            'is_default' => 'nullable|boolean',
        ]);

        $ownerType = DomainOwnerType::getByCode($request->owner_type);

        // Monta hash criptografado dos dados bancários
        $accountHash = json_encode([
            'agency' => $request->agency,
            'account' => $request->account_number,
            'digit' => $request->account_digit,
        ]);

        $account = BankAccount::create([
            'owner_type_id' => $ownerType->id,
            'owner_id' => $request->owner_id,
            'bank_name' => $request->bank_name,
            'bank_code' => $request->bank_code,
            'account_hash' => $accountHash,
            'account_type' => $request->account_type,
            'holder_name' => $request->holder_name,
            'holder_document' => $request->holder_document,
            'pix_key' => $request->pix_key,
            'pix_key_type' => $request->pix_key_type,
        ]);

        // Define como padrão se solicitado ou se for a primeira
        if ($request->is_default || !BankAccount::forOwner($ownerType->id, $request->owner_id)->where('id', '!=', $account->id)->exists()) {
            $account->setAsDefault();
        }

        return $this->createdResponse([
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'masked_account' => $account->masked_account,
            'is_default' => $account->is_default,
        ], 'Conta bancária cadastrada');
    }

    /**
     * Exibe conta bancária.
     */
    public function show(BankAccount $bankAccount)
    {
        return $this->successResponse([
            'id' => $bankAccount->id,
            'owner_type' => $bankAccount->ownerType->code,
            'owner_id' => $bankAccount->owner_id,
            'bank_name' => $bankAccount->bank_name,
            'bank_code' => $bankAccount->bank_code,
            'account_type' => $bankAccount->account_type,
            'holder_name' => $bankAccount->holder_name,
            'masked_account' => $bankAccount->masked_account,
            'pix_key_type' => $bankAccount->pix_key_type,
            'has_pix' => $bankAccount->hasPixKey(),
            'is_default' => $bankAccount->is_default,
            'is_verified' => $bankAccount->isVerified(),
            'verified_at' => $bankAccount->verified_at?->toIso8601String(),
        ]);
    }

    /**
     * Define conta como padrão.
     */
    public function setDefault(BankAccount $bankAccount)
    {
        $bankAccount->setAsDefault();

        return $this->successResponse([
            'id' => $bankAccount->id,
            'is_default' => true,
        ], 'Conta definida como padrão');
    }

    /**
     * Marca conta como verificada.
     */
    public function verify(BankAccount $bankAccount)
    {
        $bankAccount->markAsVerified();

        return $this->successResponse([
            'id' => $bankAccount->id,
            'is_verified' => true,
            'verified_at' => $bankAccount->verified_at->toIso8601String(),
        ], 'Conta verificada');
    }

    /**
     * Remove conta bancária.
     */
    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->is_default) {
            return $this->errorResponse('Não é possível remover conta padrão. Defina outra como padrão primeiro.', 422);
        }

        $bankAccount->delete();

        return $this->successResponse(null, 'Conta removida');
    }
}
