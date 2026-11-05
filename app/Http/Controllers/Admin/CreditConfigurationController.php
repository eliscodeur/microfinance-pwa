<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditObject;
use App\Models\CreditProduct;
use App\Models\CreditType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CreditConfigurationController extends Controller
{
    public function index()
    {
        return Inertia::render('Credits/Settings', [
            'types' => CreditType::withCount('products')->orderBy('nom')->get(),
            'products' => CreditProduct::with(['type', 'creditObjects'])->orderBy('nom')->get(),
            'objects' => CreditObject::withCount('creditProducts')->orderBy('nom')->get(),
        ]);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:credit_types,code'],
            'is_active' => ['required', 'boolean'],
        ]);

        CreditType::create($data + ['ulid' => strtolower((string) Str::ulid())]);

        return redirect()->route('admin.credit-settings.index');
    }

    public function updateType(Request $request, CreditType $creditType)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('credit_types', 'code')->ignore($creditType->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $creditType->update($data);

        return redirect()->route('admin.credit-settings.index');
    }

    public function storeProduct(Request $request)
    {
        $data = $this->validateProduct($request);
        $objectIds = $data['object_ids'] ?? [];
        unset($data['object_ids']);

        $product = CreditProduct::create($data + ['ulid' => strtolower((string) Str::ulid())]);
        $product->objects()->sync($objectIds);

        return redirect()->route('admin.credit-settings.index');
    }

    public function updateProduct(Request $request, CreditProduct $creditProduct)
    {
        $data = $this->validateProduct($request, $creditProduct);
        $objectIds = $data['object_ids'] ?? [];
        unset($data['object_ids']);

        $creditProduct->update($data);
        $creditProduct->objects()->sync($objectIds);

        return redirect()->route('admin.credit-settings.index');
    }

    public function storeObject(Request $request)
    {
        $data = $this->validateObject($request);
        CreditObject::create($data + ['ulid' => strtolower((string) Str::ulid())]);

        return redirect()->route('admin.credit-settings.index');
    }

    public function updateObject(Request $request, CreditObject $creditObject)
    {
        $creditObject->update($this->validateObject($request, $creditObject));

        return redirect()->route('admin.credit-settings.index');
    }

    private function validateProduct(Request $request, ?CreditProduct $product = null): array
    {
        $data = $request->validate([
            'credit_type_id' => ['required', 'exists:credit_types,id'],
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('credit_products', 'code')->ignore($product?->id)],
            'type_carnet_requis' => ['required', Rule::in(['compte', 'tontine'])],
            'taux_interet_defaut' => ['required', 'numeric', 'min:0', 'max:100'],
            'frais_dossier_defaut' => ['required', 'numeric', 'min:0'],
            'duree_max_mois' => ['required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'object_ids' => ['present', 'array'],
            'object_ids.*' => ['integer', 'distinct', 'exists:credit_objects,id'],
        ]);

        $currentTypeId = $product ? (int) $product->credit_type_id : null;
        if (
            ! CreditType::whereKey($data['credit_type_id'])->where('is_active', true)->exists()
            && (int) $data['credit_type_id'] !== $currentTypeId
        ) {
            throw ValidationException::withMessages([
                'credit_type_id' => 'Un type inactif ne peut pas être associé à un nouveau produit.',
            ]);
        }

        $currentObjectIds = $product
            ? $product->objects()->pluck('credit_objects.id')->map(fn ($id) => (int) $id)->all()
            : [];
        $newInactiveObject = CreditObject::whereIn('id', $data['object_ids'])
            ->where('is_active', false)
            ->whereNotIn('id', $currentObjectIds)
            ->exists();

        if ($newInactiveObject) {
            throw ValidationException::withMessages([
                'object_ids' => 'Un motif inactif ne peut pas être associé à un nouveau produit.',
            ]);
        }

        return $data;
    }

    private function validateObject(Request $request, ?CreditObject $object = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255', Rule::unique('credit_objects', 'nom')->ignore($object?->id)],
            'secteur_activite' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
