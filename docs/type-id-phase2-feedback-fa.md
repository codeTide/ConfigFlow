# بازخورد فاز 2 (Service-centric Admin) — تکمیل‌شده

## کارهای انجام‌شده
1. مسیر legacy `admin.service.type_select` deprecate شد و کاربر به لندینگ سرویس هدایت می‌شود.
2. navigation مدیریت سرویس از type-centric به service-centric تغییر کرد:
   - ورود به جزئیات سرویس از لیست سرویس‌ها بدون وابستگی به type.
   - بازگشت از view سرویس به لیست سرویس‌ها.
   - حذف سرویس و بازگشت به لیست سرویس‌ها.
3. payload stateهای اصلی ادمین سرویس از `type_id` پاکسازی شد:
   - `admin.service.create`
   - `admin.service.edit`
   - `admin.service.view`
   - `admin.service.tariffs`
   - `admin.service.tariffs.bridge`
   - `admin.service.tariff.create`
   - `admin.service.tariff.edit`
   - `admin.service.tariff.delete`
   - `admin.service.inventory`
   - `admin.service.inventory.add`
   - `admin.service.inventory.detail`
4. برای سازگاری، هرجا لازم باشد `type_id` به‌صورت lazy از `service_id` resolve می‌شود و دیگر از state payload گرفته نمی‌شود.
5. state لندینگ مدیریت سرویس (`admin.service.landing`) هم از `default_type_id` پاک شد.

## نتیجه فاز 2
- از دید navigation و state machine ادمین سرویس، وابستگی runtime به `type_id` حذف شده و فلو عملیاتی بر محور `service_id` است.
- مسیرهای type-centric باقی‌مانده عمدتاً به مدیریت legacy گروه‌ها (`admin.type.*`) مربوط‌اند و موضوع فاز 3/4 (cleanup کامل + migration schema) هستند.
