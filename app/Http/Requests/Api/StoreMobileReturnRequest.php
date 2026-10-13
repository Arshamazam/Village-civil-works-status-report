<?php

namespace App\Http\Requests\Api;

use App\Models\Reason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreMobileReturnRequest extends FormRequest
{
    /**
     * Tehsil access is checked in the controller once the site is loaded.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Same field rules as the web DSM-1 form; GPS is optional because offline devices may not have a fix.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'client_uuid' => ['required', 'uuid'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'report_date' => ['required', 'date_format:Y-m-d'],
            'captured_at' => ['nullable', 'date'],
            'status' => ['required', 'in:active,dormant'],
            'physical_progress' => ['nullable', 'integer', 'between:0,100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:10240'],
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
            $rules['idle_since'] = ['required', 'date_format:Y-m-d'];
        }

        return $rules;
    }

    /**
     * Business rules shared with the web form: an active site needs labour or machinery,
     * and some idle reasons require a remark.
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

                if ($this->input('status') === 'dormant' && blank($this->input('remarks'))) {
                    $reason = Reason::find($this->input('reason_id'));
                    if ($reason?->requires_remark) {
                        $validator->errors()->add('remarks', 'Remarks are mandatory for the selected idle reason.');
                    }
                }
            },
        ];
    }
}
