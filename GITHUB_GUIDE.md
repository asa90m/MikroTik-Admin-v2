# 🚀 دليل رفع المشروع على GitHub وبناء APK تلقائياً

## الخطوة 1 — إنشاء Repository جديد على GitHub

1. افتح https://github.com/new
2. اسم الـ repo: `mikrotik-admin`
3. اختر **Private** أو Public حسب رغبتك
4. **لا تضع** README أو .gitignore (لأنها موجودة مسبقاً)
5. اضغط **Create repository**

---

## الخطوة 2 — رفع الملفات

افتح Terminal على جهازك وشغّل هذه الأوامر:

```bash
cd mikrotik-admin          # ادخل لمجلد المشروع

git init
git add .
git commit -m "🚀 Initial commit - MikroTik Admin App"

# بدّل YOUR_USERNAME باسم حسابك على GitHub
git remote add origin https://github.com/YOUR_USERNAME/mikrotik-admin.git

git branch -M main
git push -u origin main
```

---

## الخطوة 3 — مشاهدة البناء التلقائي

1. افتح الـ repository على GitHub
2. اضغط تبويب **Actions** ⚡
3. ستجد Job اسمه **"🏗️ Build Android APK"** يعمل تلقائياً
4. انتظر ~10-15 دقيقة حتى ينتهي البناء

---

## الخطوة 4 — تحميل APK

### طريقة 1 — من Releases (الأسهل):
1. في الـ repository اضغط **Releases** (يمين الصفحة)
2. ستجد Release جديد باسم `📱 MikroTik-Admin v1.0.0`
3. اضغط على ملف `.apk` لتحميله مباشرة

### طريقة 2 — من Artifacts:
1. في تبويب **Actions** افتح آخر Run ناجح ✅
2. انزل لأسفل لقسم **Artifacts**
3. حمّل `MikroTik-Admin-APK`

---

## الخطوة 5 — تثبيت APK على الجوال

1. انقل ملف APK لجهازك (واتساب / Email / USB)
2. **الإعدادات ← الأمان** → فعّل "تثبيت تطبيقات من مصادر غير معروفة"
3. افتح الملف من مدير الملفات وثبّته
4. **عند أول تشغيل:** أدخل عنوان السيرفر مثلاً `http://192.168.1.5:8000`

---

## ⚙️ كيف تبني نسخة جديدة؟

أي Push على main يبني APK جديد تلقائياً.

أو يدوياً:
1. في **Actions** → اختر **Build Android APK**
2. اضغط **Run workflow**
3. أدخل رقم الإصدار واضغط **Run**

---

## 🌐 تشغيل الـ Backend

على الجهاز المتصل بالراوتر (كمبيوتر أو Raspberry Pi):

```bash
cd connect-visual
php -S 0.0.0.0:8000
```

ثم في تطبيق الجوال أدخل: `http://عنوان-جهازك:8000`

---

## ❓ مشاكل شائعة

| المشكلة | الحل |
|---------|------|
| Build فشل بـ "Gradle error" | تأكد Java 17 محددة في الـ workflow |
| APK يثبت لكن لا يتصل | تأكد الجوال والسيرفر على نفس الشبكة |
| "السيرفر غير متاح" | شغّل `php -S 0.0.0.0:8000` على الجهاز |
| شاشة الإعداد لا تظهر | اضغط "قسم الإعدادات" ← "تغيير عنوان السيرفر" |
