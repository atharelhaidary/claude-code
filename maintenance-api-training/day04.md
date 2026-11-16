# Day 04: CLAUDE.md + Skill + مقارنات

## 1. اللي اتسلّم

| الملف                                  | الحالة                                                                                                      |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `CLAUDE.md`                            | 60 سطر (الحد 100): أوامر، layout، قواعد، ممنوعات، workflow، known gaps                                      |
| `.claudeignore`                        | secrets، vendor، node_modules، storage، sqlite، lock files                                                  |
| `.claude/settings.json`                | deny على قراءة `.env` وقاعدة SQLite (لأن Claude Code بيعتمد على `permissions.deny` أكتر من `.claudeignore`) |
| `.claude/skills/form-request/SKILL.md` | 154 سطر، الوصف بيبدأ بـ `Use when`، فيه مثال B4 من المشروع                                                  |

## 2. مقارنة الـ Skill: قبل/بعد على كود المشروع الحقيقي

المقارنة على `UpdateRequestStatusRequest` (endpoint `PATCH /api/v1/requests/{maintenanceRequest}/status`).
**قبل** = الكود الموجود فعلًا في المشروع. **بعد** = نفس الـ Request متكتب بقواعد الـ Skill.
(الـ "بعد" كتبته بإيدي وأنا ماشي على `SKILL.md`، مش ناتج Claude Code بيحمّله تلقائيًا، وما اتجرّبش على PHP.)

### قبل (الموجود في المشروع)

```php
class UpdateRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['in_progress', 'done', 'cancelled'])],
        ];
    }
}
```

### بعد (بقواعد الـ Skill)

```php
class UpdateRequestStatusRequest extends FormRequest
{
    /** الحالة الحالية => الحالات المسموح الانتقال لها (افتراض يتأكد مع صاحب المنتج). */
    private const TRANSITIONS = [
        'new'         => ['cancelled'],
        'assigned'    => ['in_progress', 'cancelled'],
        'in_progress' => ['done', 'cancelled'],
        'done'        => [],
        'cancelled'   => [],
    ];

    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('maintenanceRequest'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => strtolower(trim($this->input('status')))]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['in_progress', 'done', 'cancelled'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('status')) {
                return;
            }

            $current = $this->route('maintenanceRequest')->status;
            $target = $this->input('status');

            if (! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
                $validator->errors()->add('status', __('requests.errors.invalid_transition', [
                    'from' => $current,
                    'to'   => $target,
                ]));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('requests.update_status.status.required'),
            'status.in'       => __('requests.update_status.status.in'),
        ];
    }

    public function attributes(): array
    {
        return ['status' => __('requests.attributes.status')];
    }
}
```

وفي `lang/ar/requests.php`: `'invalid_transition' => 'لا يمكن نقل الطلب من :from إلى :to.'`
وفي `lang/en/requests.php`: `'invalid_transition' => 'A request cannot move from :from to :to.'`

### نتيجة الـ checklist

| بند الـ Skill                          | قبل                                | بعد                                                             |
| -------------------------------------- | ---------------------------------- | --------------------------------------------------------------- |
| الاسم `{Action}{Entity}Request` ومكانه | نعم                                | نعم                                                             |
| `authorize()` بـ Policy                | نعم                                | نعم                                                             |
| فحص الحالة في `after()`                | لا: `done` ممكن يرجع `in_progress` | نعم                                                             |
| رسائل ar + en عبر `__()`               | لا: مفيش رسائل مخصصة               | نعم (محتاج ملفات `lang/` تتعمل)                                 |
| `prepareForValidation()` للتنضيف       | لا                                 | نعم (`trim` + lowercase)                                        |
| نفس الـ guard في الـ Action            | لا                                 | جزئي: مفيش Action للحالة، والـ controller بيعمل `update` مباشرة |
| اختبار 200/403/422                     | لا                                 | لا: لسه مكتبتوش                                                 |
| **المجموع**                            | **2 من 7**                         | **5 من 7 + 1 جزئي**                                             |

المقارنة بتوضح بالظبط الحاجات اللي الـ Skill بيضيفها فوق الموجود، وبتوضح كمان إن اتنين بنود (الـ Action والاختبار) محتاجين شغل بشري مش بس Skill.

## 3. مقارنة CLAUDE.md: قبل/بعد من أخطاء حصلت فعلًا

قبل ما `CLAUDE.md` يتكتب من كود المشروع، الأداة (أنا) غلطت في الحاجات دي في نفس الشغل:

| الغلطة الفعلية                                                                      | السبب                                                   | القاعدة/المعلومة في `CLAUDE.md` اللي بتمنعها                         |
| ----------------------------------------------------------------------------------- | ------------------------------------------------------- | -------------------------------------------------------------------- |
| اسم الموديل `ServiceOrder` و`OrderStatus` بدل `MaintenanceRequest` ولا enum أصلًا   | خمّنت                                                   | قسم Domain + `Never guess class, model, route-param or column names` |
| ملف `composer.json` فيه `pestphp/pest-plugin` في `allow-plugins`، ممكن يوحي بـ Pest | المشروع PHPUnit فعلًا                                   | `tests/Feature/ PHPUnit (NOT Pest)`                                  |
| استخدمت `$this->authorize()` في الـ controller                                      | الـ base Controller في Laravel 11+ مفيهوش `authorize()` | `use Gate::authorize()`                                              |
| افترضت فولدر `lang/` موجود                                                          | مش موجود                                                | Known gaps: `php artisan lang:publish`                               |
| افترضت إن B4 سببه إن الـ controller مش عامل type-hint للـ Request                   | طلع عامله فعلًا                                         | Known gaps: `Reproduce before touching`                              |
| وصف الـ Skill الأول مكانش بيبدأ بـ `Use when`                                       | تجاهلت شرط التسليم                                      | الشرط اتطبق في `SKILL.md`                                            |
| مثال B3 على إنه مشكلة Form Request                                                  | هو مشكلة Resource                                       | Known gaps + قسم "Not a Form Request problem" في الـ Skill           |

الأرقام: 7 أخطاء قبل، وكلها اتحولت لقاعدة أو معلومة مكتوبة. مش قدرت أقيس عدد الـ tool calls اللي بتوفرها لأن ده بيتقاس في Claude Code.

## 4. تشخيص الـ bugs من الكود الحقيقي

| Bug   | السبب                                                                                     | المكان                |
| ----- | ----------------------------------------------------------------------------------------- | --------------------- |
| B1    | `priority` مش في `$fillable`                                                              | Model                 |
| B2/B6 | `index` بـ `get()` من غير pagination + N+1 على customer/technician                        | Controller + Resource |
| B3    | الـ Resource بيرجع ISO UTC فعلًا، ولازم تعيد الـ repro                                    | Resource              |
| B4    | الـ Request بيرفض done/cancelled والـ controller عامل type-hint، ناقص guard في الـ Action | Action                |
| B5    | `show` مفيهوش authorize والـ Policy مفيهاش `view`                                         | Controller + Policy   |
| B7    | check التعيين مش locked                                                                   | Action                |
| B8    | `store` بيحوّل لـ UTC والـ Resource بيطبعه UTC                                            | Resource              |

## 5. اختبار تحميل الـ Skill في Claude Code

الخطوات: `claude` من جذر المشروع، `/memory` لازم يظهر `CLAUDE.md`، اسأل `what skills are available in this project?` ولازم تذكر `form-request`، بعدين `/clear` واطلب Form Request جديد.
النتيجة: لسه ما اتشغلتش في Claude Code. المقارنة في القسم 2 مبنية على الكود، مش على تشغيل الأداة.
