<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CustomerFilterRequest extends BaseRequest
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
