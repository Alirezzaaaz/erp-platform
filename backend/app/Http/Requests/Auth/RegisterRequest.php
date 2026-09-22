<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', 'unique:tenants,subdomain'],
            'business_type' => ['required', 'in:retail,factory,wholesale,service'],
            'size_category' => ['required', 'in:small,medium,enterprise'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],

            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],

            'platform' => ['required', 'in:windows,android,web'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'نام کسب‌وکار الزامی است.',
            'subdomain.required' => 'زیردامنه الزامی است.',
            'subdomain.regex' => 'زیردامنه فقط می‌تواند شامل حروف کوچک، اعداد و خط تیره باشد.',
            'subdomain.unique' => 'این زیردامنه قبلاً استفاده شده است.',
            'business_type.required' => 'نوع کسب‌وکار الزامی است.',
            'size_category.required' => 'اندازه کسب‌وکار الزامی است.',
            'name.required' => 'نام مدیر الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
