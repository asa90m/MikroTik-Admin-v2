#!/data/data/com.termux/files/usr/bin/bash
# تشغيل سريع للمشروع داخل Termux (أندرويد)
cd "$(dirname "$0")"
echo "================================================"
echo " الخادم يعمل الآن. اترك Termux مفتوحاً في الخلفية."
echo " افتح Chrome على نفس الجهاز واذهب إلى:"
echo " http://localhost:8000/frontend/index.html"
echo "================================================"
php -S 127.0.0.1:8000
