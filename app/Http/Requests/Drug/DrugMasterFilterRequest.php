<?php

namespace App\Http\Requests\Drug;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DrugMasterFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
        ];
    }
}
