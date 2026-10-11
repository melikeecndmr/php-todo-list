# TaskFlow – PHP & MySQL Görev Yönetim Sitesi

Kullanıcı kayıt/giriş sistemi olan, her kullanıcının kendi görevlerini yönettiği; koyu mod, animasyonlar ve yapay zeka asistanı içeren bir Yapılacaklar (Todo) uygulaması.

## 🚀 Özellikler
- **Kayıt ol / Giriş yap / Çıkış yap** (kullanıcı adı veya e-posta ile giriş)
- **Kişiye özel görevler:** Her kullanıcı yalnızca kendi görevlerini görür ve düzenler
- **Görev ekleme, düzenleme, silme, tamamlama / geri alma**
- **Öncelik**, **tarih** ve **açıklama** alanları
- **Sekmeler:** Tümü · Bugün · Bekleyen · Geciken · Tamamlanan + arama
- **Dashboard:** istatistikler ve ilerleme çubuğu
- **Profil sekmesi:** bilgi düzenleme, şifre değiştirme, hesabı silme
- **🌙 Koyu / açık mod:** Üstteki düğmeyle değişir, tercih hatırlanır
- **✨ Yapay zeka asistanı:** Sağ alttaki düğmeden açılır; günü planlar, görevleri özetler, istenirse görev ekler / hedefi görevlere böler
- **Animasyonlar:** hareketli giriş ekranı, sayı sayma, kayarak gelen görevler, görev tamamlayınca konfeti, düğme efektleri
- **Güvenlik:** `password_hash`, PDO hazır sorgular, CSRF ve XSS koruması

## ⚙️ Kurulum
1. XAMPP'te Apache ve MySQL'i başlatın.
2. Dosyaları `C:\xampp\htdocs\todo_app\` klasörüne kopyalayın.
3. `http://localhost/phpmyadmin` → **SQL** sekmesinde `setup.sql` içeriğini çalıştırın.
   > ⚠️ Eski `tasks` tablosu silinip yeni yapıyla yeniden oluşturulur.
4. `http://localhost/todo_app/register.php` adresinden hesap oluşturun.

## 🤖 Yapay zeka asistanını etkinleştirme
Asistan API anahtarı olmadan da çalışır (basit yerel mod: "bugün", "geciken", "günümü planla", "ekle: süt al").
Gerçek yapay zeka sohbeti için:
1. <https://console.anthropic.com> adresinden bir API anahtarı alın.
2. `config.php` dosyasını açıp `ANTHROPIC_API_KEY` değerine anahtarı yazın.
3. **`config.php` GitHub'a yüklenmez** (`.gitignore` içinde). Başkalarına `config.example.php` örnek olarak gider.

> XAMPP'te "SSL certificate problem" hatası alırsanız: <https://curl.se/ca/cacert.pem> dosyasını indirip `C:\xampp\php\extras\ssl\cacert.pem` olarak kaydedin, `php.ini` içinde `curl.cainfo = "C:\xampp\php\extras\ssl\cacert.pem"` satırını ayarlayıp Apache'yi yeniden başlatın.

## 📁 Dosya Yapısı
| Dosya | Görev |
|---|---|
| `db.php` | Oturum, veritabanı bağlantısı, yardımcı fonksiyonlar |
| `config.php` / `config.example.php` | API anahtarı ayarı (config.php gizli kalır) |
| `login.php` / `register.php` / `logout.php` | Kimlik doğrulama |
| `index.php` | Görev paneli |
| `edit.php` | Görev düzenleme |
| `profile.php` | Profil ve güvenlik |
| `assistant.php` | Yapay zeka asistanı uç noktası |
| `header.php` / `footer.php` | Ortak şablon (+ asistan penceresi) |
| `style.css` / `app.js` | Tasarım, koyu mod, animasyonlar, asistan arayüzü |
| `setup.sql` | Veritabanı şeması |
