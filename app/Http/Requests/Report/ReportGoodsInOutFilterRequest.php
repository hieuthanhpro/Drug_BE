<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ReportGoodsInOutFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
            'report_by' => ['required', Rule::in(["selling", 'import'])],
            'type_export' => Rule::in(["all", 'current_page', 'current_search']),
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',

        ];
    }
}
