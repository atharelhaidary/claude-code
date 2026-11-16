# Reported bugs

Bugs reported by the people who use the system. Each entry is what they saw,
in their words, and how to see it yourself. Finding the cause is your job.

الـ bugs زي ما المستخدمين بلّغوا عنها: اللي شافوه، بكلامهم، وإزاي تشوفه بنفسك.
السبب عليك.

---

## B1 — The priority goes missing · الأولوية بتضيع

> «لما بعمل طلب جديد وأختار الأولوية high، الطلب بيتسجل من غير أولوية.»
> "When I create a request and pick high priority, it is saved with no priority."

**To see it:** log in as the dispatcher, create a request with `"priority": "high"`,
then open it.

## B2 — The list loads everything · القائمة بتجيب كل حاجة

> «صفحة الطلبات بقت بطيئة وبتحمّل كل الطلبات مرة واحدة.»
> "The requests page has become slow and loads every request at once."

**To see it:** seed a few hundred requests and call `GET /api/v1/requests`.

## B3 — The closing date comes back as text · تاريخ الإغلاق بيرجع نص

> «تطبيق الموبايل بيقع لما يعرض تاريخ قفل الطلب.»
> "The mobile app crashes when it shows when a request was closed."

**To see it:** as the assigned technician, set a request's status to `done`
and look at `completed_at` in the response.

## B4 — A technician assigned to a closed request · فني متعيّن على طلب مقفول

> «الـ dispatcher عيّن فني على طلب كان اتقفل من امبارح، والفني راح على الفاضي.»
> "The dispatcher assigned a technician to a request that was closed yesterday,
> and the technician went for nothing."

**To see it:** take a request whose status is `done` or `cancelled` and assign
a technician to it.

## B5 — A technician can see other people's requests · فني شايف طلبات غيره

> «فني قال إنه لما غيّر الرقم في اللينك فتح طلب مش متعيّن له.»
> "A technician said that by changing the number in the link they opened a
> request that is not assigned to them."

**To see it:** log in as one technician and open a request assigned to another.

## B6 — The requests list is slow · قائمة الطلبات بطيئة

> «صفحة الطلبات بتاخد ثواني لما الطلبات تكتر.»
> "The requests page takes seconds once there are a lot of requests."

**To see it:** seed a lot of assigned requests and time `GET /api/v1/requests`.
The tests will not show you this one.

## B7 — One technician on two requests at the same moment · فني على طلبين في نفس اللحظة

> «اتنين dispatchers عيّنوا نفس الفني على طلبين في نفس الثانية، والاتنين اتقبلوا.»
> "Two dispatchers assigned the same technician to two requests in the same
> second, and both went through."

**To see it:** two requests at the same `scheduled_at`, one technician, and two
assignment calls sent at the same time.

## B8 — Appointments three hours off · المواعيد مزحولة 3 ساعات

> «الفني جاله الموعد 7 الصبح والعميل كان حاجز 10.»
> "The technician was told 7 in the morning and the customer had booked 10."

**To see it:** create a request with `"scheduled_at": "2026-10-01 10:00"` and
read `scheduled_at` back.
