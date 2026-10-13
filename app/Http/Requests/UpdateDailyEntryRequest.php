<?php

namespace App\Http\Requests;

use App\Models\Reason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyEntryRequest extends FormRequest
{
    /**
     * Route middleware restricts corrections to Super Admin and HQ Manager.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['Super Admin', 'HQ Manager']);
    }

    /**
     * Same field rules as the DSM-1 form, plus "completed" and an optional note explaining the correction.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'status' => ['required', 'in:active,dormant,completed'],
            'physical_progress' => ['nullable', 'integer', 'between:0,100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'correction_note' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->input('status') === 'active') {
            $rules['labour_count'] = ['required', 'integer', 'min:0'];
            $rules['machinery_count'] = ['required', 'integer', 'min:0'];
            $rules['machinery_types'] = ['nullable', 'array'];
            $rules['machinery_types.*'] = ['string', 'max:100'];
            $rules['work_done'] = ['required', 'string', 'min:5', 'max:2000'];
        }

        if ($this->input('status') === 'dormant') {
            $rules['reason_id'] = ['required', 'integer', 'exists:reasons,id'];
            $rules['idle_since'] = ['required', 'date', 'before_or_equal:'.$this->route('date')];
        }

        return $rules;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') === 'active'
                    && (int) $this->input('labour_count') === 0
                    && (int) $this->input('machinery_count') === 0) {
                    $validator->errors()->add('labour_count', 'An Active site must have at least 1 labourer or 1 machinery unit deployed.');
                }

                if ($this->input('status') === 'dormant' && blank($this->input('remarks'))
                    && Reason::find($this->input('reason_id'))?->requires_remark) {
                    $validator->errors()->add('remarks', 'Remarks are mandatory for the selected idle reason.');
                }
            },
        ];
    }
}
