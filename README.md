# PHP & MySQL Todo Listesi (Yapılacaklar Listesi)

Modern ve işlevsel özelliklere sahip, sıfırdan geliştirilmiş web tabanlı bir görev takip (Todo) uygulaması.

## 🚀 Özellikler
- **Görev Ekleme:** Kullanıcı dostu arayüz ile hızlıca yeni görevler ekleme.
- **Durum Yönetimi:** Görevleri "Tamamlandı" olarak işaretleyebilme, üstünü çizme ve dilediğinde "Geri Alabilme".
- **Görev Silme:** İstenmeyen görevleri veritabanından kalıcı olarak silme.
- **Veritabanı Entegrasyonu:** PDO kullanılarak güvenli MySQL veri tabanı bağlantısı.
- **Modern Tasarım:** Google Fonts (Inter) ve özel CSS stilleri ile şık, responsive arayüz.

## 🛠️ Kullanılan Teknolojiler
- **PHP** (PDO Veritabanı Yönetimi)
- **MySQL** (Veritabanı)
- **HTML5 & CSS3** (Arayüz ve Tasarım)
- **XAMPP** (Yerel Geliştirme Sunucusu)

## ⚙️ Kurulum ve Çalıştırma
1. XAMPP'i başlatın (Apache ve MySQL aktif olmalıdır).
2. Proje dosyalarını `C:\xampp\htdocs\todo_app\` klasörünün içine kopyalayın.
3. `phpMyAdmin` (`http://localhost/phpmyadmin`) üzerinden `todo_app` adında bir veritabanı oluşturun.
4. Aşağıdaki SQL kodunu çalıştırarak gerekli tabloyu oluşturun:
   ```sql
   CREATE TABLE tasks (
       id INT AUTO_INCREMENT PRIMARY KEY,
       task_name VARCHAR(255) NOT NULL,
       status TINYINT DEFAULT 0,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );